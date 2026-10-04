<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\CMS\Models\Menu;
use App\Modules\CMS\Models\MenuItem;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class MenuSyncer extends BaseSyncer
{
    private const ITEM_TYPES = ['FRONTPAGE', 'COLLECTION', 'COLLECTIONS', 'PRODUCT', 'CATALOG', 'PAGE', 'BLOG', 'ARTICLE', 'SEARCH', 'SHOP_POLICY', 'HTTP', 'METAOBJECT', 'CUSTOMER_ACCOUNT_PAGE'];

    /**
     * @param Menu $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        if (Gid::make($model->shopify_menu_id, 'Menu')) {
            $this->update($model, [], ['title', 'handle'], $ctx);
            return;
        }

        $handle = (string) ($model->handle ?: Str::slug((string) $model->title));

        $result = $ctx->gw->mutate(<<<'GQL'
mutation MenuCreate($title: String!, $handle: String!, $items: [MenuItemCreateInput!]!) {
  menuCreate(title: $title, handle: $handle, items: $items) {
    menu { id handle }
    userErrors { field message }
  }
}
GQL, [
            'title' => (string) ($model->title ?: $handle),
            'handle' => $handle,
            'items' => $this->items($model, withIds: false),
        ], 'menuCreate');

        $this->persist($model, [
            'shopify_menu_id' => $result['menu']['id'] ?? null,
            'handle' => blank($model->handle) ? ($result['menu']['handle'] ?? null) : null,
        ]);
    }

    /**
     * menuUpdate replaces the whole item tree, so the current local items are always sent.
     *
     * @param Menu $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $gid = Gid::make($model->shopify_menu_id, 'Menu');

        if (!$gid) {
            $this->create($model, $ctx);
            return;
        }

        if (!$this->touched($changed, 'title', 'handle')) {
            return;
        }

        $variables = [
            'id' => $gid,
            'title' => (string) ($model->title ?: $model->handle),
            'items' => $this->items($model, withIds: true),
        ];

        if (in_array('handle', $changed, true) && filled($model->handle)) {
            $variables['handle'] = (string) $model->handle;
        }

        $ctx->gw->mutate(<<<'GQL'
mutation MenuUpdate($id: ID!, $title: String!, $handle: String, $items: [MenuItemUpdateInput!]!) {
  menuUpdate(id: $id, title: $title, handle: $handle, items: $items) {
    menu { id }
    userErrors { field message }
  }
}
GQL, $variables, 'menuUpdate');
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        $gid = Gid::make($snapshot['shopify_menu_id'] ?? null, 'Menu');

        if ($gid) {
            $this->ignoreNotFound(fn () => $ctx->gw->mutate(
                'mutation MenuDelete($id: ID!) { menuDelete(id: $id) { deletedMenuId userErrors { field message } } }',
                ['id' => $gid],
                'menuDelete',
            ));
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function items(Menu $menu, bool $withIds): array
    {
        $all = MenuItem::query()->where('menu_id', $menu->id)->orderBy('position')->get();

        return $this->branch($all, null, $withIds);
    }

    /**
     * @param SupportCollection<int, MenuItem> $all
     * @return array<int, array<string, mixed>>
     */
    private function branch(SupportCollection $all, ?int $parentId, bool $withIds): array
    {
        return $all
            ->filter(fn (MenuItem $item) => ($item->parent_id ? (int) $item->parent_id : null) === $parentId)
            ->map(function (MenuItem $item) use ($all, $withIds) {
                $type = strtoupper((string) $item->type);
                $node = [
                    'title' => (string) $item->title,
                    'type' => in_array($type, self::ITEM_TYPES, true) ? $type : 'HTTP',
                    'url' => $item->url,
                    'resourceId' => filled($item->resource_id) ? (string) $item->resource_id : null,
                    'tags' => $this->tagList($item->tags),
                    'items' => $this->branch($all, (int) $item->id, $withIds),
                ];

                if ($withIds && ($id = Gid::make($item->shopify_menu_item_id, 'MenuItem'))) {
                    $node = ['id' => $id] + $node;
                }

                return $node;
            })
            ->values()
            ->all();
    }
}
