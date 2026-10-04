<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Marketing\Models\Discount;
use App\Modules\Marketing\Models\DiscountCode;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Redeem codes belong to a code discount in Shopify. Shopify adds codes asynchronously,
 * so the code id is filled in later by the discounts webhook/sync.
 */
class DiscountCodeSyncer extends BaseSyncer
{
    /**
     * @param DiscountCode $model
     */
    public function snapshot(Model $model): array
    {
        return $model->getAttributes() + ['discount_gid' => $this->discountGid($model->discount_id)];
    }

    /**
     * @param DiscountCode $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        $discountGid = $this->discountGid($model->discount_id);

        if (!$discountGid || blank($model->code)) {
            $ctx->note('Code saved locally only: its discount is not in Shopify yet.');
            return;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation RedeemCodeAdd($discountId: ID!, $codes: [DiscountRedeemCodeInput!]!) {
  discountRedeemCodeBulkAdd(discountId: $discountId, codes: $codes) {
    bulkCreation { id }
    userErrors { field message }
  }
}
GQL, ['discountId' => $discountGid, 'codes' => [['code' => (string) $model->code]]], 'discountRedeemCodeBulkAdd');
    }

    /**
     * @param DiscountCode $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $codeChanged = ($before['code'] ?? null) !== $model->code;
        $discountChanged = (string) ($before['discount_id'] ?? '') !== (string) $model->discount_id;

        if (!$codeChanged && !$discountChanged) {
            return;
        }

        $this->delete($before, $ctx);
        $model->forceFill(['shopify_discount_code_id' => null])->saveQuietly();
        $this->create($model, $ctx);
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $codeGid = Gid::make($snapshot['shopify_discount_code_id'] ?? null, 'DiscountRedeemCode');
        $discountGid = $snapshot['discount_gid'] ?? null;

        if (!$codeGid || !$discountGid) {
            return;
        }

        $this->ignoreNotFound(fn () => $ctx->gw->mutate(<<<'GQL'
mutation RedeemCodeDelete($discountId: ID!, $ids: [ID!]) {
  discountCodeRedeemCodeBulkDelete(discountId: $discountId, ids: $ids) {
    job { id }
    userErrors { field message }
  }
}
GQL, ['discountId' => $discountGid, 'ids' => [$codeGid]], 'discountCodeRedeemCodeBulkDelete'));
    }

    private function discountGid(mixed $discountId): ?string
    {
        $raw = $discountId ? Discount::query()->whereKey($discountId)->value('shopify_discount_id') : null;

        return Gid::make($raw, 'DiscountCodeNode');
    }
}
