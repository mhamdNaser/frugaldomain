<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Catalog\Models\Tag;
use App\Modules\Stores\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TagSyncService
{
    /**
     * مزامنة التاجز مع أي نموذج (Product, Collection, Vendor)
     *
     * @param Store $store
     * @param Model $taggableModel
     * @param array<int, array> $tags
     * @param bool $replaceOldTags
     * @return array<int, int> معرفات التاجز التي تمت مزامنتها
     */
    public function sync(Store $store, Model $taggableModel, array $tags, bool $replaceOldTags = true): array
    {
        if ($replaceOldTags) {
            $taggableModel->tags()->detach();
        }

        $syncedTagIds = [];

        foreach ($tags as $tagData) {
            $tag = $this->upsertTag($store, $tagData);

            // تجنب إضافة نفس التاج مرتين
            if (!$replaceOldTags && $taggableModel->tags()->where('tag_id', $tag->id)->exists()) {
                continue;
            }

            $taggableModel->tags()->syncWithoutDetaching([$tag->id]);
            $syncedTagIds[] = $tag->id;
        }

        return $syncedTagIds;
    }

    /**
     * إنشاء أو تحديث تاج واحد
     */
    private function upsertTag(Store $store, $tagData): Tag
    {
        $tag = Tag::updateOrCreate(
            [
                'store_id' => $store->id,
                'slug' => Str::slug($tagData) ?? '',
            ],
            [
                'name' => $tagData ?? '',
            ]
        );

        return $tag;
    }

    /**
     * حذف التاجز غير المستخدمة (لا توجد علاقات)
     */
    public function cleanupOrphanedTags(Store $store): int
    {
        return Tag::where('store_id', $store->id)
            ->whereDoesntHave('taggables')
            ->delete();
    }
}
