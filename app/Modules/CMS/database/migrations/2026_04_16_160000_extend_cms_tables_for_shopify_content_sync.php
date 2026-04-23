<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $this->addStoreAndShopifyColumns($table, 'shopify_page_id');
            $this->addContentColumns($table);
            if (!Schema::hasColumn('pages', 'author')) {
                $table->string('author')->nullable()->after('handle');
            }
            if (!Schema::hasColumn('pages', 'body')) {
                $table->longText('body')->nullable()->after('author');
            }
        });

        Schema::table('blogs', function (Blueprint $table) {
            $this->addStoreAndShopifyColumns($table, 'shopify_blog_id');
            $this->addContentColumns($table);
            if (!Schema::hasColumn('blogs', 'comment_policy')) {
                $table->string('comment_policy')->nullable()->after('handle')->index();
            }
            if (!Schema::hasColumn('blogs', 'tags')) {
                $table->json('tags')->nullable()->after('comment_policy');
            }
        });

        Schema::table('articles', function (Blueprint $table) {
            $this->addStoreAndShopifyColumns($table, 'shopify_article_id');
            if (!Schema::hasColumn('articles', 'blog_id')) {
                $table->foreignId('blog_id')->nullable()->after('store_id')->index();
            }
            $this->addContentColumns($table);
            if (!Schema::hasColumn('articles', 'author_name')) {
                $table->string('author_name')->nullable()->after('handle');
            }
            if (!Schema::hasColumn('articles', 'body')) {
                $table->longText('body')->nullable()->after('author_name');
            }
            if (!Schema::hasColumn('articles', 'summary')) {
                $table->longText('summary')->nullable()->after('body');
            }
            if (!Schema::hasColumn('articles', 'tags')) {
                $table->json('tags')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('articles', 'comments_count')) {
                $table->unsignedInteger('comments_count')->default(0)->after('tags');
            }
        });

        Schema::table('menus', function (Blueprint $table) {
            $this->addStoreAndShopifyColumns($table, 'shopify_menu_id');
            if (!Schema::hasColumn('menus', 'handle')) {
                $table->string('handle')->nullable()->after('shopify_menu_id')->index();
            }
            if (!Schema::hasColumn('menus', 'title')) {
                $table->string('title')->nullable()->after('handle');
            }
            if (!Schema::hasColumn('menus', 'items_count')) {
                $table->unsignedInteger('items_count')->default(0)->after('title');
            }
            if (!Schema::hasColumn('menus', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('items_count');
            }
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $this->addStoreAndShopifyColumns($table, 'shopify_menu_item_id');
            if (!Schema::hasColumn('menu_items', 'menu_id')) {
                $table->foreignId('menu_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('menu_items', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('menu_id')->index();
            }
            if (!Schema::hasColumn('menu_items', 'resource_id')) {
                $table->string('resource_id')->nullable()->after('shopify_menu_item_id')->index();
            }
            if (!Schema::hasColumn('menu_items', 'title')) {
                $table->string('title')->nullable()->after('resource_id');
            }
            if (!Schema::hasColumn('menu_items', 'type')) {
                $table->string('type')->nullable()->after('title')->index();
            }
            if (!Schema::hasColumn('menu_items', 'url')) {
                $table->text('url')->nullable()->after('type');
            }
            if (!Schema::hasColumn('menu_items', 'tags')) {
                $table->json('tags')->nullable()->after('url');
            }
            if (!Schema::hasColumn('menu_items', 'position')) {
                $table->unsignedInteger('position')->default(0)->after('tags');
            }
            if (!Schema::hasColumn('menu_items', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('position');
            }
        });

        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->id();
                $table->uuid('store_id')->nullable()->index();
                $table->foreignId('article_id')->nullable()->index();
                $table->string('shopify_comment_id')->nullable()->index();
                $table->string('author')->nullable();
                $table->string('email')->nullable()->index();
                $table->string('ip')->nullable();
                $table->string('status')->nullable()->index();
                $table->longText('body')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamp('shopify_created_at')->nullable()->index();
                $table->timestamp('shopify_updated_at')->nullable()->index();
                $table->timestamps();

                $table->unique(['article_id', 'shopify_comment_id'], 'comments_article_shopify_unique');
                $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }

    private function addStoreAndShopifyColumns(Blueprint $table, string $shopifyColumn): void
    {
        $tableName = $table->getTable();

        if (!Schema::hasColumn($tableName, 'store_id')) {
            $table->uuid('store_id')->nullable()->after('id')->index();
        }
        if (!Schema::hasColumn($tableName, $shopifyColumn)) {
            $table->string($shopifyColumn)->nullable()->after('store_id')->index();
        }
    }

    private function addContentColumns(Blueprint $table): void
    {
        $tableName = $table->getTable();

        foreach ([
            'handle' => fn () => $table->string('handle')->nullable()->after('shopify_' . rtrim($tableName, 's') . '_id')->index(),
            'title' => fn () => $table->string('title')->nullable()->after('handle'),
            'seo_title' => fn () => $table->string('seo_title')->nullable()->after('title'),
            'seo_description' => fn () => $table->text('seo_description')->nullable()->after('seo_title'),
            'template_suffix' => fn () => $table->string('template_suffix')->nullable()->after('seo_description'),
            'is_published' => fn () => $table->boolean('is_published')->default(false)->after('template_suffix')->index(),
            'published_at' => fn () => $table->timestamp('published_at')->nullable()->after('is_published')->index(),
            'raw_payload' => fn () => $table->json('raw_payload')->nullable()->after('published_at'),
            'shopify_created_at' => fn () => $table->timestamp('shopify_created_at')->nullable()->after('raw_payload')->index(),
            'shopify_updated_at' => fn () => $table->timestamp('shopify_updated_at')->nullable()->after('shopify_created_at')->index(),
        ] as $column => $callback) {
            if (!Schema::hasColumn($tableName, $column)) {
                $callback();
            }
        }
    }
};
