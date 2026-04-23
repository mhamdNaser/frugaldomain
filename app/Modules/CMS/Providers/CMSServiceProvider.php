<?php

namespace App\Modules\CMS\Providers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Repositories\Eloquent\FrontendArticlesRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendBlogsRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendFilesRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendMenusRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendMenuItemsRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendMetafieldMetaobjectsRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendMetafieldsRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendMetaobjectsRepository;
use App\Modules\CMS\Repositories\Eloquent\FrontendPagesRepository;
use App\Modules\CMS\Repositories\Eloquent\MetaDefinitionsFiledsRepository;
use App\Modules\CMS\Repositories\Eloquent\MetaDefinitionsRepository;
use App\Modules\CMS\Repositories\Interfaces\ArticlesRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\BlogsRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\FilesRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\MenusRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\MenuItemsRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\MetaDefinitionsFiledsRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\MetaDefinitionsRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\MetafieldMetaobjectsRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\MetafieldsRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\MetaobjectsRepositoryInterface;
use App\Modules\CMS\Repositories\Interfaces\PagesRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CMSServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FilesRepositoryInterface::class, FrontendFilesRepository::class);
        $this->app->bind(ArticlesRepositoryInterface::class, FrontendArticlesRepository::class);
        $this->app->bind(BlogsRepositoryInterface::class, FrontendBlogsRepository::class);
        $this->app->bind(MenusRepositoryInterface::class, FrontendMenusRepository::class);
        $this->app->bind(MenuItemsRepositoryInterface::class, FrontendMenuItemsRepository::class);
        $this->app->bind(MetafieldsRepositoryInterface::class, FrontendMetafieldsRepository::class);
        $this->app->bind(MetafieldMetaobjectsRepositoryInterface::class, FrontendMetafieldMetaobjectsRepository::class);
        $this->app->bind(MetaobjectsRepositoryInterface::class, FrontendMetaobjectsRepository::class);
        $this->app->bind(PagesRepositoryInterface::class, FrontendPagesRepository::class);
        $this->app->bind(MetaDefinitionsRepositoryInterface::class, MetaDefinitionsRepository::class);
        $this->app->bind(MetaDefinitionsFiledsRepositoryInterface::class, MetaDefinitionsFiledsRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        Relation::morphMap([
            'product' => Product::class,
            'variant' => ProductVariant::class,
        ]);
    }
}
