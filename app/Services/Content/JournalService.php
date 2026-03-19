<?php

namespace App\Services\Content;

use App\Http\Resources\Api\V1\Content\BlogCollection;
use App\Http\Resources\Api\V1\Content\BlogDetailResource;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Product;
use App\Utility\CategoryUtility;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class JournalService
{
    private bool $journalSectionResolved = false;

    private ?PageSection $journalSection = null;

    public function __construct(
        private readonly ContentMedia $contentMedia
    ) {
    }

    public function categoriesPayload(): array
    {
        return [
            'success' => true,
            'data' => BlogCategory::query()->latest()->get(),
            'recentBlogs' => new BlogCollection($this->publishedBlogsQuery()->take(5)->get()),
        ];
    }

    public function feed(?string $categorySlug, ?string $searchKeyword, int $page, int $perPage): array
    {
        $category = $categorySlug ? BlogCategory::query()->where('slug', $categorySlug)->first() : null;
        $categoryId = optional($category)->id;

        $query = $this->publishedBlogsQuery();

        if ($searchKeyword) {
            $query->where(function ($builder) use ($searchKeyword): void {
                foreach (explode(' ', trim($searchKeyword)) as $word) {
                    if ($word !== '') {
                        $builder->where('title', 'like', '%' . $word . '%');
                    }
                }
            });
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $collection = new BlogCollection($paginator);

        return [
            'success' => true,
            'metaTitle' => $category ? $category->meta_title : get_setting('meta_title'),
            'blogs' => $collection,
            'totalPage' => $collection->lastPage(),
            'currentPage' => $collection->currentPage(),
            'total' => $collection->total(),
            'currentCategory' => $category,
            'journal' => !$searchKeyword && !$categoryId ? $this->buildJournalPayload(collect($paginator->items())) : null,
        ];
    }

    public function recent(int $limit): array
    {
        return [
            'success' => true,
            'blogs' => new BlogCollection($this->publishedBlogsQuery()->take($limit)->get()),
        ];
    }

    public function detail(string $slug): array
    {
        $blog = $this->publishedBlogsQuery()->where('slug', $slug)->first();

        if (!$blog) {
            return [
                'success' => false,
                'data' => null,
                'message' => 'Blog not found',
            ];
        }

        return [
            'success' => true,
            'data' => array_merge(
                (new BlogDetailResource($blog))->resolve(),
                [
                    'related_products' => $this->formatProducts($this->resolveBlogProducts($blog)),
                    'videos' => $this->extractYoutubeVideos($blog),
                ]
            ),
            'recentBlogs' => new BlogCollection($this->publishedBlogsQuery()->where('id', '!=', $blog->id)->take(5)->get()),
        ];
    }

    private function publishedBlogsQuery()
    {
        return Blog::query()
            ->with(['category', 'author', 'productCategory', 'productBrand'])
            ->where('status', 1)
            ->orderByRaw('COALESCE(published_at, created_at) desc');
    }

    private function buildJournalPayload(Collection $blogs): array
    {
        $heroPosts = $blogs->take(5)->values();
        $hero = $heroPosts->first();
        $firstGrid = $blogs->slice(1, 6)->values();
        $secondGrid = $blogs->slice(7, 6)->values();
        $journalSectionData = $this->buildJournalSectionData($this->resolveJournalSection());

        return [
            'hero_posts' => (new BlogCollection($heroPosts))->resolve()['data'] ?? [],
            'mixed_section' => $journalSectionData['mixed_section'] ?? ($hero ? [
                'source_post_slug' => $hero->slug,
                'image' => $this->contentMedia->asset($hero->editorial_image),
                'title' => $hero->getTranslation('editorial_title') ?: $hero->getTranslation('title'),
                'content' => $hero->getTranslation('editorial_content') ?: $hero->getTranslation('short_description'),
                'products' => $this->formatProducts($this->resolveBlogProducts($hero)),
            ] : null),
            'first_grid' => (new BlogCollection($firstGrid))->resolve()['data'] ?? [],
            'second_grid' => (new BlogCollection($secondGrid))->resolve()['data'] ?? [],
            'videos' => $journalSectionData['videos'] ?? $this->collectJournalVideos(
                $this->publishedBlogsQuery()->whereNotNull('youtube_urls')->take(8)->get()
            ),
        ];
    }

    private function resolveBlogProducts(Blog $blog): Collection
    {
        $limit = max(1, min((int) ($blog->related_products_limit ?: 4), 12));
        $manualIds = collect($blog->related_product_ids ?? [])->filter()->map(fn ($id) => (int) $id)->unique()->values();

        if ($manualIds->isNotEmpty()) {
            return filter_products(Product::query())
                ->whereIn('id', $manualIds)
                ->get()
                ->sortBy(fn ($product) => $manualIds->search($product->id))
                ->values()
                ->take($limit);
        }

        $query = filter_products(Product::query());

        if ($blog->product_source_type === 'category' && $blog->product_category_id) {
            $categoryIds = CategoryUtility::children_ids($blog->product_category_id);
            $categoryIds[] = (int) $blog->product_category_id;

            $query->whereHas('product_categories', function ($productQuery) use ($categoryIds): void {
                $productQuery->whereIn('category_id', $categoryIds);
            });
        } elseif ($blog->product_source_type === 'brand' && $blog->product_brand_id) {
            $query->where('brand_id', $blog->product_brand_id);
        }

        $products = $query->inRandomOrder()->take($limit)->get();

        return $products->isNotEmpty()
            ? $products
            : $this->resolvePageSectionProducts($this->resolveJournalSection());
    }

    private function resolveJournalSection(): ?PageSection
    {
        if ($this->journalSectionResolved) {
            return $this->journalSection;
        }

        $this->journalSectionResolved = true;

        $page = Page::query()
            ->with(['visibleSections' => function ($query) {
                $query->where('type', 'journal_editorial');
            }])
            ->published()
            ->where('slug', 'journal')
            ->first();

        $this->journalSection = $page?->visibleSections?->first();

        return $this->journalSection;
    }

    private function buildJournalSectionData(?PageSection $section): array
    {
        if (!$section) {
            return [];
        }

        return [
            'mixed_section' => [
                'source' => 'journal_page',
                'image' => $this->contentMedia->asset($section->image),
                'title' => $section->title,
                'content' => $section->content,
                'products' => $this->formatProducts($this->resolvePageSectionProducts($section)),
            ],
            'videos' => $this->formatYoutubeVideos(
                collect($section->settings_json['youtube_urls'] ?? [])->all(),
                $section->title ?: 'Journal',
                'journal'
            ),
        ];
    }

    private function resolvePageSectionProducts(?PageSection $section): Collection
    {
        $settings = $section?->settings_json ?? [];
        $limit = max(1, min((int) ($settings['related_products_limit'] ?? 4), 12));
        $query = filter_products(Product::query());

        if (($settings['product_source_type'] ?? null) === 'category' && !empty($settings['product_category_id'])) {
            $categoryIds = CategoryUtility::children_ids((int) $settings['product_category_id']);
            $categoryIds[] = (int) $settings['product_category_id'];

            $query->whereHas('product_categories', function ($productQuery) use ($categoryIds): void {
                $productQuery->whereIn('category_id', $categoryIds);
            });
        } elseif (($settings['product_source_type'] ?? null) === 'brand' && !empty($settings['product_brand_id'])) {
            $query->where('brand_id', (int) $settings['product_brand_id']);
        }

        $products = $query->inRandomOrder()->take($limit)->get();

        return $products->isNotEmpty()
            ? $products
            : filter_products(Product::query())->inRandomOrder()->take($limit)->get();
    }

    private function formatProducts(Collection $products): array
    {
        return $products->map(function ($product) {
            $description = method_exists(str(''), 'stripTags')
                ? str($product->getTranslation('description'))->stripTags()->squish()->limit(70)->toString()
                : strip_tags((string) $product->getTranslation('description'));

            return [
                'id' => (int) $product->id,
                'name' => $product->getTranslation('name'),
                'slug' => $product->slug,
                'image' => $this->contentMedia->asset($product->thumbnail_img),
                'description' => $description,
                'price' => (double) product_discounted_base_price($product),
                'formatted_price' => 'NGN ' . number_format((double) product_discounted_base_price($product)),
            ];
        })->values()->all();
    }

    private function collectJournalVideos(Collection $blogs): array
    {
        $videos = collect();

        foreach ($blogs as $blog) {
            foreach ($this->extractYoutubeVideos($blog) as $video) {
                $videos->push($video);
                if ($videos->count() >= 4) {
                    break 2;
                }
            }
        }

        return $videos->values()->all();
    }

    private function extractYoutubeVideos(Blog $blog): array
    {
        return $this->formatYoutubeVideos(
            collect($blog->youtube_urls ?? [])->all(),
            $blog->getTranslation('title'),
            $blog->slug
        );
    }

    private function formatYoutubeVideos(array $urls, string $title, string $slug): array
    {
        return collect($urls)->map(function ($url) use ($title, $slug) {
            $id = $this->extractYoutubeId($url);

            if (!$id) {
                return null;
            }

            return [
                'blog_slug' => $slug,
                'blog_title' => $title,
                'title' => $title,
                'video_id' => $id,
                'url' => $url,
                'embed_url' => "https://www.youtube-nocookie.com/embed/{$id}?autoplay=1&rel=0&modestbranding=1&playsinline=1",
                'thumbnail' => "https://img.youtube.com/vi/{$id}/hqdefault.jpg",
            ];
        })->filter()->values()->all();
    }

    private function extractYoutubeId(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $path = trim($parts['path'] ?? '', '/');

        if (in_array($host, ['youtu.be'], true) && $path !== '') {
            return $path;
        }

        parse_str($parts['query'] ?? '', $query);

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
            if (!empty($query['v'])) {
                return $query['v'];
            }

            if (str_starts_with($path, 'embed/')) {
                return trim(substr($path, 6));
            }

            if (str_starts_with($path, 'shorts/')) {
                return trim(substr($path, 7));
            }
        }

        return null;
    }
}
