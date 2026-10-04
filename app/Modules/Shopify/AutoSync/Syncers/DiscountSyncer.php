<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Marketing\Models\Discount;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class DiscountSyncer extends BaseSyncer
{
    /** Shopify discount typename => [update mutation, input argument, input type] */
    private const TYPES = [
        'DiscountCodeBasic' => ['discountCodeBasicUpdate', 'basicCodeDiscount', 'DiscountCodeBasicInput'],
        'DiscountCodeBxgy' => ['discountCodeBxgyUpdate', 'bxgyCodeDiscount', 'DiscountCodeBxgyInput'],
        'DiscountCodeFreeShipping' => ['discountCodeFreeShippingUpdate', 'freeShippingCodeDiscount', 'DiscountCodeFreeShippingInput'],
        'DiscountCodeApp' => ['discountCodeAppUpdate', 'codeAppDiscount', 'DiscountCodeAppInput'],
        'DiscountAutomaticBasic' => ['discountAutomaticBasicUpdate', 'automaticBasicDiscount', 'DiscountAutomaticBasicInput'],
        'DiscountAutomaticBxgy' => ['discountAutomaticBxgyUpdate', 'automaticBxgyDiscount', 'DiscountAutomaticBxgyInput'],
        'DiscountAutomaticFreeShipping' => ['discountAutomaticFreeShippingUpdate', 'freeShippingAutomaticDiscount', 'DiscountAutomaticFreeShippingInput'],
        'DiscountAutomaticApp' => ['discountAutomaticAppUpdate', 'automaticAppDiscount', 'DiscountAutomaticAppInput'],
    ];

    /**
     * Creates a basic (percentage / fixed amount, whole order) discount. The value and code
     * come from the create request and are kept in raw_payload.create_input until Shopify
     * sends the full discount back.
     *
     * @param Discount $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_discount_id, $this->isAutomatic($model) ? 'DiscountAutomaticNode' : 'DiscountCodeNode')) {
            $this->update($model, [], ['title', 'starts_at', 'ends_at', 'usage_limit', 'status'], $ctx);
            return;
        }

        $create = is_array($model->raw_payload) ? ($model->raw_payload['create_input'] ?? []) : [];

        if (!isset($create['value'])) {
            $ctx->note('Discount saved locally only: send "value" (and "code" for code discounts) to create it in Shopify.');
            return;
        }

        $automatic = $this->isAutomatic($model);
        $value = ($create['value_type'] ?? 'percentage') === 'fixed_amount'
            ? ['discountAmount' => ['amount' => (string) $create['value'], 'appliesOnEachItem' => false]]
            : ['percentage' => min(1, ((float) $create['value']) / 100)];

        $input = array_filter([
            'title' => (string) ($model->title ?: ($create['code'] ?? 'Discount')),
            'startsAt' => $this->isoDate($model->starts_at) ?? now()->toIso8601String(),
            'endsAt' => $this->isoDate($model->ends_at),
            'customerGets' => ['value' => $value, 'items' => ['all' => true]],
        ], fn ($v) => $v !== null);

        if ($automatic) {
            $result = $ctx->gw->mutate(<<<'GQL'
mutation DiscountAutomaticBasicCreate($discount: DiscountAutomaticBasicInput!) {
  discountAutomaticBasicCreate(automaticBasicDiscount: $discount) {
    automaticDiscountNode { id }
    userErrors { field message }
  }
}
GQL, ['discount' => $input], 'discountAutomaticBasicCreate');
            $gid = $result['automaticDiscountNode']['id'] ?? null;
            $type = 'DiscountAutomaticBasic';
        } else {
            if (blank($create['code'] ?? null)) {
                throw ValidationException::withMessages(['code' => 'A code is required to create a code discount in Shopify.']);
            }

            $input += array_filter([
                'code' => (string) $create['code'],
                'usageLimit' => $model->usage_limit,
                'customerSelection' => ['all' => true],
            ], fn ($v) => $v !== null);

            $result = $ctx->gw->mutate(<<<'GQL'
mutation DiscountCodeBasicCreate($discount: DiscountCodeBasicInput!) {
  discountCodeBasicCreate(basicCodeDiscount: $discount) {
    codeDiscountNode { id }
    userErrors { field message }
  }
}
GQL, ['discount' => $input], 'discountCodeBasicCreate');
            $gid = $result['codeDiscountNode']['id'] ?? null;
            $type = 'DiscountCodeBasic';
        }

        $this->persist($model, [
            'shopify_discount_id' => $gid,
            'discount_type' => $type,
            'method' => $automatic ? 'automatic' : 'code',
        ]);
    }

    /**
     * @param Discount $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_discount_id, $this->isAutomatic($model) ? 'DiscountAutomaticNode' : 'DiscountCodeNode');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        [$mutation, $argument, $inputType] = self::TYPES[(string) $model->discount_type] ?? [null, null, null];

        $input = [];
        if ($this->touched($changed, 'title') && filled($model->title)) {
            $input['title'] = (string) $model->title;
        }
        if ($this->touched($changed, 'starts_at') && filled($model->starts_at)) {
            $input['startsAt'] = $this->isoDate($model->starts_at);
        }
        if ($this->touched($changed, 'ends_at')) {
            $input['endsAt'] = $this->isoDate($model->ends_at);
        }
        if ($this->touched($changed, 'usage_limit') && !$this->isAutomatic($model)) {
            $input['usageLimit'] = $model->usage_limit;
        }

        if ($input !== [] && $mutation) {
            $ctx->gw->mutate(<<<GQL
mutation DiscountUpdate(\$id: ID!, \$input: {$inputType}!) {
  {$mutation}(id: \$id, {$argument}: \$input) {
    userErrors { field message }
  }
}
GQL, ['id' => $gid, 'input' => $input], $mutation);
        }

        if ($this->touched($changed, 'status')) {
            $this->toggleActive($model, $gid, $ctx);
        }
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $automatic = $this->isAutomatic($snapshot);
        $gid = Gid::make($snapshot['shopify_discount_id'] ?? null, $automatic ? 'DiscountAutomaticNode' : 'DiscountCodeNode');

        if (!$gid) {
            return;
        }

        $mutation = $automatic ? 'discountAutomaticDelete' : 'discountCodeDelete';
        $field = $automatic ? 'deletedAutomaticDiscountId' : 'deletedCodeDiscountId';

        $this->ignoreNotFound(fn () => $ctx->gw->mutate(
            "mutation DiscountDelete(\$id: ID!) { {$mutation}(id: \$id) { {$field} userErrors { field message } } }",
            ['id' => $gid],
            $mutation,
        ));
    }

    private function toggleActive(Discount $model, string $gid, SyncContext $ctx): void
    {
        $status = strtolower((string) $model->status);
        $activate = $status === 'active';

        if (!$activate && !in_array($status, ['expired', 'inactive', 'disabled', 'deactivated'], true)) {
            return;
        }

        $mutation = ($this->isAutomatic($model) ? 'discountAutomatic' : 'discountCode') . ($activate ? 'Activate' : 'Deactivate');

        $ctx->gw->mutate(
            "mutation DiscountToggle(\$id: ID!) { {$mutation}(id: \$id) { userErrors { field message } } }",
            ['id' => $gid],
            $mutation,
        );
    }

    /**
     * @param Discount|array<string, mixed> $discount
     */
    private function isAutomatic(Discount|array $discount): bool
    {
        $type = (string) ($discount instanceof Discount ? $discount->discount_type : ($discount['discount_type'] ?? ''));
        $method = strtolower((string) ($discount instanceof Discount ? $discount->method : ($discount['method'] ?? '')));

        return str_starts_with($type, 'DiscountAutomatic') || $method === 'automatic';
    }
}
