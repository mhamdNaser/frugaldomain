<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Orders\Models\Order;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;

/**
 * Orders with line items are created through OrderController::createFromAdmin
 * (AdminOrderShopifySyncService). Here only editable fields and deletion are pushed.
 */
class OrderSyncer extends BaseSyncer
{
    public function create(Model $model, SyncContext $ctx): void
    {
        $ctx->note('Order saved locally only: use the admin order form (with items) to create orders in Shopify.');
    }

    /**
     * @param Order $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_order_id, 'Order');

        if (!$gid || !in_array('email', $changed, true) || blank($model->email)) {
            return;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation OrderUpdate($input: OrderInput!) {
  orderUpdate(input: $input) {
    order { id }
    userErrors { field message }
  }
}
GQL, ['input' => ['id' => $gid, 'email' => (string) $model->email]], 'orderUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_order_id'] ?? null, 'Order');

        if ($gid) {
            $this->ignoreNotFound(fn () => $ctx->gw->mutate(
                'mutation OrderDelete($orderId: ID!) { orderDelete(orderId: $orderId) { deletedId userErrors { field message } } }',
                ['orderId' => $gid],
                'orderDelete',
            ));
        }
    }
}
