<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\CMS\Models\Article;
use App\Modules\CMS\Models\Blog;
use App\Modules\CMS\Models\Comment;
use App\Modules\CMS\Models\Menu;
use App\Modules\CMS\Models\MenuItem;
use App\Modules\CMS\Models\Page;
use App\Modules\Shopify\DTOs\ArticleData;
use App\Modules\Shopify\DTOs\BlogData;
use App\Modules\Shopify\DTOs\CmsPageData;
use App\Modules\Shopify\DTOs\CommentData;
use App\Modules\Shopify\DTOs\MenuData;
use App\Modules\Shopify\DTOs\MenuItemData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class ContentSyncService
{
    private const PAGE_SIZE = 50;
    private const BLOG_PAGE_SIZE = 5;
    private const ARTICLE_PAGE_SIZE = 10;
    private const COMMENT_PAGE_SIZE = 50;

    public function sync(Store $store): array
    {
        $client = new ShopifyClient($store);
        $pages = $this->syncPages($store, $client);
        $blogSync = $this->syncBlogs($store, $client);
        $menuSync = $this->syncMenus($store, $client);

        return [
            'pages' => $pages,
            'blogs' => $blogSync['blogs'],
            'articles' => $blogSync['articles'],
            'comments' => $blogSync['comments'],
            'menus' => $menuSync['menus'],
            'menu_items' => $menuSync['menu_items'],
        ];
    }

    private function syncPages(Store $store, ShopifyClient $client): int
    {
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->pagesQuery(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ]),
            );

            $connection = $response['data']['pages'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (is_array($node)) {
                    $this->upsertPage($store, $this->pageData($node));
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function syncBlogs(Store $store, ShopifyClient $client): array
    {
        $blogCount = 0;
        $articleCount = 0;
        $commentCount = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->blogsQuery(),
                variables: array_filter([
                    'first' => self::BLOG_PAGE_SIZE,
                    'after' => $after,
                    'articleFirst' => self::ARTICLE_PAGE_SIZE,
                    'commentFirst' => self::COMMENT_PAGE_SIZE,
                ]),
            );

            $connection = $response['data']['blogs'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (is_array($node)) {
                    $summary = $this->upsertBlog($store, $this->blogData($node));
                    $blogCount++;
                    $articleCount += $summary['articles'];
                    $commentCount += $summary['comments'];
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return [
            'blogs' => $blogCount,
            'articles' => $articleCount,
            'comments' => $commentCount,
        ];
    }

    private function syncMenus(Store $store, ShopifyClient $client): array
    {
        $menuCount = 0;
        $menuItemCount = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->menusQuery(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                ]),
            );

            $connection = $response['data']['menus'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (is_array($node)) {
                    $menuItemCount += $this->upsertMenu($store, $this->menuData($node));
                    $menuCount++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return [
            'menus' => $menuCount,
            'menu_items' => $menuItemCount,
        ];
    }

    private function upsertPage(Store $store, CmsPageData $data): Page
    {
        return Page::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_page_id' => $data->shopifyPageId,
            ],
            [
                'handle' => $data->handle,
                'title' => $data->title,
                'author' => $data->author,
                'body' => $data->body,
                'seo_title' => $data->seoTitle,
                'seo_description' => $data->seoDescription,
                'template_suffix' => $data->templateSuffix,
                'is_published' => $data->isPublished,
                'published_at' => $data->publishedAt,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ],
        );
    }

    private function upsertBlog(Store $store, BlogData $data): array
    {
        $blog = Blog::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_blog_id' => $data->shopifyBlogId,
            ],
            [
                'handle' => $data->handle,
                'title' => $data->title,
                'comment_policy' => $data->commentPolicy,
                'tags' => $data->tags,
                'seo_title' => $data->seoTitle,
                'seo_description' => $data->seoDescription,
                'template_suffix' => $data->templateSuffix,
                'is_published' => $data->isPublished,
                'published_at' => $data->publishedAt,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ],
        );

        $articleCount = 0;
        $commentCount = 0;
        foreach ($data->articles as $articleData) {
            if ($articleData instanceof ArticleData) {
                $summary = $this->upsertArticle($store, $blog, $articleData);
                $articleCount++;
                $commentCount += $summary['comments'];
            }
        }

        return [
            'blog' => $blog,
            'articles' => $articleCount,
            'comments' => $commentCount,
        ];
    }

    private function upsertArticle(Store $store, Blog $blog, ArticleData $data): array
    {
        $article = Article::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_article_id' => $data->shopifyArticleId,
            ],
            [
                'blog_id' => $blog->id,
                'handle' => $data->handle,
                'title' => $data->title,
                'author_name' => $data->authorName,
                'body' => $data->body,
                'summary' => $data->summary,
                'tags' => $data->tags,
                'comments_count' => $data->commentsCount,
                'seo_title' => $data->seoTitle,
                'seo_description' => $data->seoDescription,
                'template_suffix' => $data->templateSuffix,
                'is_published' => $data->isPublished,
                'published_at' => $data->publishedAt,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ],
        );

        $savedComments = 0;
        foreach ($data->comments as $commentData) {
            if ($commentData instanceof CommentData) {
                $this->upsertComment($store, $article, $commentData);
                $savedComments++;
            }
        }

        return [
            'article' => $article,
            'comments' => $savedComments,
        ];
    }

    private function upsertComment(Store $store, Article $article, CommentData $data): Comment
    {
        return Comment::query()->updateOrCreate(
            [
                'article_id' => $article->id,
                'shopify_comment_id' => $data->shopifyCommentId,
            ],
            [
                'store_id' => $store->id,
                'author' => $data->author,
                'email' => $data->email,
                'ip' => $data->ip,
                'status' => $data->status,
                'body' => $data->body,
                'published_at' => $data->publishedAt,
                'raw_payload' => $data->rawPayload,
                'shopify_created_at' => $data->shopifyCreatedAt,
                'shopify_updated_at' => $data->shopifyUpdatedAt,
            ],
        );
    }

    private function upsertMenu(Store $store, MenuData $data): int
    {
        $menu = Menu::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_menu_id' => $data->shopifyMenuId,
            ],
            [
                'handle' => $data->handle,
                'title' => $data->title,
                'items_count' => $data->itemsCount,
                'raw_payload' => $data->rawPayload,
            ],
        );

        return $this->upsertMenuItems($store, $menu, $data->items);
    }

    private function upsertMenuItems(Store $store, Menu $menu, array $items, ?MenuItem $parent = null): int
    {
        $count = 0;

        foreach ($items as $itemData) {
            if (!$itemData instanceof MenuItemData) {
                continue;
            }

            $item = MenuItem::query()->updateOrCreate(
                [
                    'menu_id' => $menu->id,
                    'shopify_menu_item_id' => $itemData->shopifyMenuItemId,
                ],
                [
                    'store_id' => $store->id,
                    'parent_id' => $parent?->id,
                    'resource_id' => $itemData->resourceId,
                    'title' => $itemData->title,
                    'type' => $itemData->type,
                    'url' => $itemData->url,
                    'tags' => $itemData->tags,
                    'position' => $itemData->position,
                    'raw_payload' => $itemData->rawPayload,
                ],
            );

            $count++;
            $count += $this->upsertMenuItems($store, $menu, $itemData->items, $item);
        }

        return $count;
    }

    private function pageData(array $node): CmsPageData
    {
        return new CmsPageData(
            shopifyPageId: $node['id'],
            handle: $node['handle'] ?? null,
            title: $node['title'] ?? '',
            author: null,
            body: $node['body'] ?? null,
            seoTitle: null,
            seoDescription: $node['bodySummary'] ?? null,
            templateSuffix: $node['templateSuffix'] ?? null,
            isPublished: (bool) ($node['isPublished'] ?? false),
            publishedAt: $node['publishedAt'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
        );
    }

    private function blogData(array $node): BlogData
    {
        return new BlogData(
            shopifyBlogId: $node['id'],
            handle: $node['handle'] ?? null,
            title: $node['title'] ?? '',
            commentPolicy: $node['commentPolicy'] ?? null,
            tags: $node['tags'] ?? [],
            seoTitle: null,
            seoDescription: null,
            templateSuffix: $node['templateSuffix'] ?? null,
            isPublished: true,
            publishedAt: $node['createdAt'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            articles: array_values(array_filter(array_map(
                fn (array $edge): ?ArticleData => $this->articleData($edge['node'] ?? null),
                $node['articles']['edges'] ?? [],
            ))),
        );
    }

    private function articleData(mixed $node): ?ArticleData
    {
        if (!is_array($node) || empty($node['id'])) {
            return null;
        }

        return new ArticleData(
            shopifyArticleId: $node['id'],
            handle: $node['handle'] ?? null,
            title: $node['title'] ?? '',
            authorName: $node['author']['name'] ?? null,
            body: $node['body'] ?? null,
            summary: $node['summary'] ?? null,
            tags: $node['tags'] ?? [],
            seoTitle: null,
            seoDescription: null,
            templateSuffix: $node['templateSuffix'] ?? null,
            isPublished: (bool) ($node['isPublished'] ?? false),
            publishedAt: $node['publishedAt'] ?? null,
            commentsCount: (int) ($node['commentsCount']['count'] ?? 0),
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            comments: array_values(array_filter(array_map(
                fn (array $edge): ?CommentData => $this->commentData($edge['node'] ?? null),
                $node['comments']['edges'] ?? [],
            ))),
        );
    }

    private function commentData(mixed $node): ?CommentData
    {
        if (!is_array($node) || empty($node['id'])) {
            return null;
        }

        return new CommentData(
            shopifyCommentId: $node['id'],
            author: $node['author']['name'] ?? null,
            email: $node['author']['email'] ?? null,
            ip: $node['ip'] ?? null,
            status: strtolower((string) ($node['status'] ?? '')),
            body: $node['bodyHtml'] ?? $node['body'] ?? null,
            publishedAt: $node['publishedAt'] ?? null,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
        );
    }

    private function menuData(array $node): MenuData
    {
        $items = $this->menuItems($node['items'] ?? []);

        return new MenuData(
            shopifyMenuId: $node['id'],
            handle: $node['handle'] ?? null,
            title: $node['title'] ?? '',
            itemsCount: count($items),
            rawPayload: $node,
            items: $items,
        );
    }

    private function menuItems(array $items): array
    {
        return array_values(array_filter(array_map(
            fn (array $item, int $index): ?MenuItemData => $this->menuItemData($item, $index + 1),
            $items,
            array_keys($items),
        )));
    }

    private function menuItemData(array $node, int $position): ?MenuItemData
    {
        if (empty($node['id'])) {
            return null;
        }

        return new MenuItemData(
            shopifyMenuItemId: $node['id'],
            resourceId: $node['resourceId'] ?? null,
            title: $node['title'] ?? '',
            type: $node['type'] ?? null,
            url: $node['url'] ?? null,
            tags: $node['tags'] ?? [],
            position: $position,
            rawPayload: $node,
            items: $this->menuItems($node['items'] ?? []),
        );
    }

    private function pagesQuery(): string
    {
        return <<<'GRAPHQL'
query GetPages($first: Int!, $after: String) {
  pages(first: $first, after: $after, sortKey: UPDATED_AT, reverse: true) {
    edges {
      node {
        id
        handle
        title
        body
        bodySummary
        isPublished
        publishedAt
        templateSuffix
        createdAt
        updatedAt
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
GRAPHQL;
    }

    private function blogsQuery(): string
    {
        return <<<'GRAPHQL'
query GetBlogs($first: Int!, $after: String, $articleFirst: Int!, $commentFirst: Int!) {
  blogs(first: $first, after: $after) {
    edges {
      node {
        id
        handle
        title
        commentPolicy
        tags
        templateSuffix
        createdAt
        updatedAt
        articles(first: $articleFirst, reverse: true) {
          edges {
            node {
              id
              handle
              title
              author {
                name
              }
              body
              summary
              tags
              templateSuffix
              isPublished
              publishedAt
              createdAt
              updatedAt
              commentsCount {
                count
              }
              comments(first: $commentFirst, reverse: true) {
                edges {
                  node {
                    id
                    body
                    bodyHtml
                    ip
                    status
                    publishedAt
                    createdAt
                    updatedAt
                    author {
                      name
                      email
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}
GRAPHQL;
    }

    private function menusQuery(): string
    {
        return <<<'GRAPHQL'
query GetMenus($first: Int!, $after: String) {
  menus(first: $first, after: $after) {
    edges {
      node {
        id
        handle
        title
        items {
          ...MenuItemFields
          items {
            ...MenuItemFields
            items {
              ...MenuItemFields
            }
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}

fragment MenuItemFields on MenuItem {
  id
  resourceId
  title
  type
  url
  tags
}
GRAPHQL;
    }
}
