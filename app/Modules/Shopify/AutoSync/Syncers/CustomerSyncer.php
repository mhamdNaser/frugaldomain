<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use App\Modules\User\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CustomerSyncer extends BaseSyncer
{
    /**
     * @param Customer $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_customer_id, 'Customer')) {
            $this->update($model, [], ['first_name', 'last_name', 'email', 'phone', 'tags', 'note', 'tax_exempt'], $ctx);
            return;
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation CustomerCreate($input: CustomerInput!) {
  customerCreate(input: $input) {
    customer { id }
    userErrors { field message }
  }
}
GQL, ['input' => array_filter($this->input($model, null), fn ($value) => $value !== null)], 'customerCreate');

        $this->persist($model, ['shopify_customer_id' => $result['customer']['id'] ?? null]);
    }

    /**
     * @param Customer $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_customer_id, 'Customer');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        $input = $this->input($model, $changed);

        if ($input === []) {
            return;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation CustomerUpdate($input: CustomerInput!) {
  customerUpdate(input: $input) {
    customer { id }
    userErrors { field message }
  }
}
GQL, ['input' => ['id' => $gid] + $input], 'customerUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_customer_id'] ?? null, 'Customer');

        if ($gid) {
            $this->ignoreNotFound(fn () => $ctx->gw->mutate(
                'mutation CustomerDelete($input: CustomerDeleteInput!) { customerDelete(input: $input) { deletedCustomerId userErrors { field message } } }',
                ['input' => ['id' => $gid]],
                'customerDelete',
            ));
        }
    }

    /**
     * @param array<int, string>|null $changed
     * @return array<string, mixed>
     */
    private function input(Customer $model, ?array $changed): array
    {
        $input = [];

        if ($this->touched($changed, 'first_name')) {
            $input['firstName'] = $model->first_name;
        }
        if ($this->touched($changed, 'last_name')) {
            $input['lastName'] = $model->last_name;
        }
        if ($this->touched($changed, 'email')) {
            $input['email'] = $model->email;
        }
        if ($this->touched($changed, 'phone')) {
            $input['phone'] = filled($model->phone) ? (string) $model->phone : null;
        }
        if ($this->touched($changed, 'tags')) {
            $input['tags'] = $this->tagList($model->tags);
        }
        if ($this->touched($changed, 'note')) {
            $input['note'] = $model->note;
        }
        if ($this->touched($changed, 'tax_exempt') && $model->tax_exempt !== null) {
            $input['taxExempt'] = (bool) $model->tax_exempt;
        }

        return $input;
    }
}
