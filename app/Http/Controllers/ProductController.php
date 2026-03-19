<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use App\Models\ProductTax;
use App\Models\ProductTranslation;
use App\Models\ProductVariation;
use App\Models\ProductVariationCombination;
use App\Contracts\ApplicationBootstrap;
use App\Models\Shop;
use App\Models\ShopBrand;
use App\Utility\CategoryUtility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_products'])->only('index');
        $this->middleware(['permission:add_products'])->only('create');
        $this->middleware(['permission:view_products'])->only('show');
        $this->middleware(['permission:edit_products'])->only('edit');
        $this->middleware(['permission:edit_products'])->only('updateTodayDeal');
        $this->middleware(['permission:duplicate_products'])->only('duplicate');
        $this->middleware(['permission:delete_products'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $col_name = null;
        $query = null;
        $sort_search = null;
        $products = Product::orderBy('created_at', 'desc')->where('shop_id', auth()->user()->shop_id);
        if ($request->search != null) {
            $products = $products->where('name', 'like', '%' . $request->search . '%');
            $sort_search = $request->search;
        }
        if ($request->type != null) {
            $var = explode(",", $request->type);
            $col_name = $var[0];
            $query = $var[1];
            $products = $products->orderBy($col_name, $query);
            $sort_type = $request->type;
        }

        $products = $products->paginate(15);
        $type = 'All';

        return view('backend.product.products.index', compact('products', 'type', 'col_name', 'query', 'sort_search'));
    }

    public function adminSearch(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));

        if ($keyword === '') {
            return response()->json([
                'success' => true,
                'query' => '',
                'data' => [],
            ]);
        }

        $needle = '%' . Str::lower($keyword) . '%';

        $products = Product::query()
            ->with([
                'categories:id,name',
                'variations:id,product_id,sku',
            ])
            ->when(auth()->user()->shop_id, function ($query) {
                $query->where('shop_id', auth()->user()->shop_id);
            })
            ->where(function ($query) use ($needle) {
                $query->whereRaw('LOWER(name) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(slug) LIKE ?', [$needle])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$needle])
                    ->orWhereHas('product_translations', function ($translationQuery) use ($needle) {
                        $translationQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$needle])
                            ->orWhereRaw('LOWER(description) LIKE ?', [$needle]);
                    })
                    ->orWhereHas('variations', function ($variationQuery) use ($needle) {
                        $variationQuery->whereRaw('LOWER(sku) LIKE ?', [$needle]);
                    })
                    ->orWhereHas('categories', function ($categoryQuery) use ($needle) {
                        $categoryQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$needle])
                            ->orWhereHas('category_translations', function ($translationQuery) use ($needle) {
                                $translationQuery->whereRaw('LOWER(name) LIKE ?', [$needle]);
                            });
                    });
            })
            ->orderByRaw(
                "CASE
                    WHEN LOWER(name) LIKE ? THEN 0
                    WHEN LOWER(slug) LIKE ? THEN 1
                    ELSE 2
                END",
                [Str::lower($keyword) . '%', Str::lower($keyword) . '%']
            )
            ->latest()
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'query' => $keyword,
            'data' => $products->map(function (Product $product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => optional($product->variations->firstWhere('sku', '!=', null))->sku,
                    'thumbnail_url' => uploaded_asset($product->thumbnail_img) ?: static_asset('assets/img/placeholder.jpg'),
                    'category_names' => $product->categories
                        ->pluck('name')
                        ->filter()
                        ->values()
                        ->all(),
                    'edit_url' => route('product.edit', $product->id),
                ];
            })->values(),
        ]);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::where('level', 0)->where('digital', 0)->get();
        $attributes = Attribute::get();
        return view('backend.product.products.create', compact('categories', 'attributes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        app(ApplicationBootstrap::class)->initialize();
        $this->validateProductCategoryPayload($request);

        if ($request->has('is_variant') && !$request->has('variations')) {
            flash(translate('Invalid product variations'))->error();
            return redirect()->back();
        }
        $shop = $this->resolveShopForProduct($request);

        $product                    = new Product;
        $product->name              = $request->name;
        $product->shop_id           = optional($shop)->id;
        $this->applyProductOwnershipFields($product);
        $product->brand_id          = $request->brand_id;
        $product->unit              = $request->unit;
        $product->min_qty           = $request->min_qty;
        $product->max_qty           = $request->max_qty;
        $product->photos            = $request->photos;
        $product->thumbnail_img     = $request->thumbnail_img;
        $product->description       = $request->description;
        $product->published         = (int) $request->status;
        $product->approved          = $this->resolveApprovalStateForSave();
        $product->digital           = 0;
        $product->main_category     = $request->main_category;

        // SEO meta
        $product->meta_title        = (!is_null($request->meta_title)) ? $request->meta_title : $product->name;
        $product->meta_description  = (!is_null($request->meta_description)) ? $request->meta_description : strip_tags($product->description);
        $product->meta_image          = (!is_null($request->meta_image)) ? $request->meta_image : $product->thumbnail_img;
        $product->slug              = $this->generateUniqueProductSlug($request->name);

        // warranty
        $product->has_warranty      = $request->has('has_warranty') && $request->has_warranty == 'on' ? 1 : 0;

        // tag
        $tags                       = array();
        if ($request->tags != null) {
            foreach (json_decode($request->tags) as $key => $tag) {
                array_push($tags, $tag->value);
            }
        }
        $product->tags              = implode(',', $tags);

        // lowest highest price
        if ($request->has('is_variant') && $request->has('variations')) {
            $product->lowest_price  =  min(array_column($request->variations, 'price'));
            $product->highest_price =  max(array_column($request->variations, 'price'));
        } else {
            $product->lowest_price  =  $request->price;
            $product->highest_price =  $request->price;
        }

        // stock based on all variations
        $product->stock             = ($request->has('is_variant') && $request->has('variations')) ? max(array_column($request->variations, 'stock')) : $request->stock;

        // discount
        $product->discount          = $request->discount;
        $product->discount_type     = $request->discount_type;
        if ($request->date_range != null) {
            $date_var               = explode(" to ", $request->date_range);
            $product->discount_start_date = strtotime($date_var[0]);
            $product->discount_end_date   = strtotime($date_var[1]);
        }

        // Club Point
        if (get_setting('club_point')) {
            $product->earn_point = $request->earn_point;
        }

        // shipping info
        $product->standard_delivery_time    = $request->standard_delivery_time;
        $product->express_delivery_time     = $request->express_delivery_time;
        $product->weight                    = $request->weight;
        $product->height                    = $request->height;
        $product->length                    = $request->length;
        $product->width                     = $request->width;

        $product->save();
        Log::info('Product created: base row saved', [
            'product_id' => $product->id,
            'shop_id' => $product->shop_id,
            'published' => (int) $product->published,
            'approved' => (int) $product->approved,
            'digital' => (int) $product->digital,
            'slug' => $product->slug,
            'shop_status' => [
                'published' => (int) optional($shop)->published,
                'approval' => (int) optional($shop)->approval,
                'verification_status' => (int) optional($shop)->verification_status,
            ],
        ]);

        // Product Translations
        $product_translation = ProductTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'product_id' => $product->id]);
        $product_translation->name = $request->name;
        $product_translation->unit = $request->unit;
        $product_translation->description = $request->description;
        $product_translation->save();
        Log::info('Product created: translation saved', [
            'product_id' => $product->id,
            'lang' => env('DEFAULT_LANGUAGE'),
            'name' => $product_translation->name,
        ]);

        $categorySyncState = $this->syncProductAndShopCategories($product, $request->input('category_ids', []), $shop);
        Log::info('Product created: categories synced', [
            'product_id' => $product->id,
            'selected_category_ids' => $categorySyncState['selected_category_ids'],
            'root_category_ids' => $categorySyncState['root_category_ids'],
            'shop_category_ids' => $categorySyncState['shop_category_ids'],
        ]);

        // shop brand
        if ($request->brand_id && $shop) {
            ShopBrand::updateOrCreate([
                'shop_id' => $shop->id,
                'brand_id' => $request->brand_id,
            ]);
        }


        //taxes
        $tax_data = array();
        $tax_ids = array();
        if ($request->has('taxes')) {
            foreach ($request->taxes as $key => $tax) {
                array_push($tax_data, [
                    'tax' => $tax,
                    'tax_type' => $request->tax_types[$key]
                ]);
            }
            $tax_ids = $request->tax_ids;
        }
        $taxes = array_combine($tax_ids, $tax_data);

        $product->product_taxes()->sync($taxes);


        //product variation
        $product->is_variant        = ($request->has('is_variant') && $request->has('variations')) ? 1 : 0;

        if ($request->has('is_variant') && $request->has('variations')) {
            foreach ($request->variations as $variation) {
                $p_variation              = new ProductVariation;
                $p_variation->product_id  = $product->id;
                $p_variation->code        = $variation['code'];
                $p_variation->price       = $variation['price'];
                $p_variation->stock       = $variation['stock'];
                $p_variation->current_stock       = $variation['current_stock'];
                $p_variation->sku         = $variation['sku'];
                $p_variation->img         = $variation['img'];
                $p_variation->save();

                foreach (array_filter(explode("/", $variation['code'])) as $combination) {
                    $p_variation_comb                         = new ProductVariationCombination;
                    $p_variation_comb->product_id             = $product->id;
                    $p_variation_comb->product_variation_id   = $p_variation->id;
                    $p_variation_comb->attribute_id           = explode(":", $combination)[0];
                    $p_variation_comb->attribute_value_id     = explode(":", $combination)[1];
                    $p_variation_comb->save();
                }
            }
        } else {
            $this->upsertBaseVariationForSimpleProduct($product, $request);
        }

        // attribute
        if ($request->has('product_attributes') && $request->product_attributes[0] != null) {
            foreach ($request->product_attributes as $attr_id) {
                $attribute_values = 'attribute_' . $attr_id . '_values';
                if ($request->has($attribute_values) && $request->$attribute_values != null) {
                    $p_attribute = new ProductAttribute;
                    $p_attribute->product_id = $product->id;
                    $p_attribute->attribute_id = $attr_id;
                    $p_attribute->save();

                    foreach ($request->$attribute_values as $val_id) {
                        $p_attr_value = new ProductAttributeValue;
                        $p_attr_value->product_id = $product->id;
                        $p_attr_value->attribute_id = $attr_id;
                        $p_attr_value->attribute_value_id = $val_id;
                        $p_attr_value->save();
                    }
                }
            }
        }


        $product->save();
        cache_clear();

        flash(translate('Product has been inserted successfully'))->success();
        return redirect()->route('product.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return view('backend.product.products.show', [
            'product' => Product::withCount('reviews', 'wishlists', 'carts')->with('variations.combinations')->findOrFail($id)
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $product = Product::with([
            'attributes',
            'attribute_values',
            'variations.combinations',
            'variation_combinations',
            'product_categories',
            'brand',
        ])->findOrFail($id);
        // if ($product->shop_id != auth()->user()->shop_id) {
        //     abort(403);
        // }

        $lang = $request->lang;
        $categories = Category::where('level', 0)->where('digital', 0)->get();
        $all_attributes = Attribute::get();
        return view('backend.product.products.edit', compact('product', 'categories', 'lang', 'all_attributes'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validateProductCategoryPayload($request);

        if ($request->has('is_variant') && !$request->has('variations')) {
            flash(translate('Invalid product variations'))->error();
            return redirect()->back();
        }

        $product                    = Product::findOrFail($id);
        $shop                       = $this->resolveShopForProduct($request, $product);

        if ($request->filled('shop_id') || (!$product->shop_id && $shop)) {
            $product->shop_id = $shop->id;
        }

        $oldProduct                 = clone $product;

        // if ($product->shop_id != auth()->user()->shop_id) {
        //     abort(403);
        // }

        if ($request->lang == env("DEFAULT_LANGUAGE")) {
            $product->name          = $request->name;
            $product->unit          = $request->unit;
            $product->description   = $request->description;
        }

        $product->brand_id          = $request->brand_id;
        app(ApplicationBootstrap::class)->initialize();
        $product->min_qty           = $request->min_qty;
        $product->max_qty           = $request->max_qty;
        $product->photos            = $request->photos;
        $product->thumbnail_img     = $request->thumbnail_img;
        $product->published         = (int) $request->status;
        if (auth()->user()->user_type !== 'seller' && (int) $product->published === 1) {
            $product->approved = 1;
        }
        $product->digital           = 0;
        $product->main_category     = $request->main_category;

        // Product Translations
        $product_translation                = ProductTranslation::firstOrNew(['lang' => $request->lang, 'product_id' => $product->id]);
        $product_translation->name          = $request->name;
        $product_translation->unit          = $request->unit;
        $product_translation->description   = $request->description;
        $product_translation->save();


        // SEO meta
        $product->meta_title        = (!is_null($request->meta_title)) ? $request->meta_title : $product->name;
        $product->meta_description  = (!is_null($request->meta_description)) ? $request->meta_description : strip_tags($product->description);
        $product->meta_image        = (!is_null($request->meta_image)) ? $request->meta_image : $product->thumbnail_img;
        if (!is_null($request->slug)) {
            $product->slug = $this->generateUniqueProductSlug($request->slug, $product->id);
        } elseif (empty($product->slug)) {
            $product->slug = $this->generateUniqueProductSlug($request->name, $product->id);
        }

        // warranty
        $product->has_warranty      = $request->has('has_warranty') && $request->has_warranty == 'on' ? 1 : 0;
        // pickup
        $product->for_pickup      = $request->has('for_pickup') && $request->for_pickup == 'on' ? 1 : 0;


        // tag
        $tags                       = array();
        if ($request->tags != null) {
            foreach (json_decode($request->tags) as $key => $tag) {
                array_push($tags, $tag->value);
            }
        }
        $product->tags              = implode(',', $tags);

        // lowest highest price
        if ($request->has('is_variant') && $request->has('variations')) {
            $product->lowest_price  =  min(array_column($request->variations, 'price'));
            $product->highest_price =  max(array_column($request->variations, 'price'));
        } else {
            $product->lowest_price  =  $request->price;
            $product->highest_price =  $request->price;
        }

        // stock based on all variations
        $product->stock             = ($request->has('is_variant') && $request->has('variations')) ? max(array_column($request->variations, 'stock')) : $request->stock;

        // discount
        $product->discount          = $request->discount;
        $product->discount_type     = $request->discount_type;
        if ($request->date_range != null) {
            $date_var               = explode(" to ", $request->date_range);
            $product->discount_start_date = strtotime($date_var[0]);
            $product->discount_end_date   = strtotime($date_var[1]);
        }

        // Club Point
        if (get_setting('club_point')) {
            $product->earn_point = $request->earn_point;
        }

        // shipping info
        $product->standard_delivery_time    = $request->standard_delivery_time;
        $product->express_delivery_time     = $request->express_delivery_time;
        $product->weight                    = $request->weight;
        $product->height                    = $request->height;
        $product->length                    = $request->length;
        $product->width                     = $request->width;

        $categorySyncState = $this->syncProductAndShopCategories($product, $request->input('category_ids', []), $shop);
        Log::info('Product updated: categories synced', [
            'product_id' => $product->id,
            'selected_category_ids' => $categorySyncState['selected_category_ids'],
            'root_category_ids' => $categorySyncState['root_category_ids'],
            'shop_category_ids' => $categorySyncState['shop_category_ids'],
        ]);

        // shop brand
        if ($request->brand_id && $shop) {
            ShopBrand::updateOrCreate([
                'shop_id' => $shop->id,
                'brand_id' => $request->brand_id,
            ]);
        }

        // taxes
        $tax_data = array();
        $tax_ids = array();
        if ($request->has('taxes')) {
            foreach ($request->taxes as $key => $tax) {
                array_push($tax_data, [
                    'tax' => $tax,
                    'tax_type' => $request->tax_types[$key]
                ]);
            }
            $tax_ids = $request->tax_ids;
        }
        $taxes = array_combine($tax_ids, $tax_data);

        $product->product_taxes()->sync($taxes);


        //product variation
        $product->is_variant        = ($request->has('is_variant') && $request->has('variations')) ? 1 : 0;

        if ($request->has('is_variant') && $request->has('variations')) {

            $requested_variations = collect($request->variations);
            $requested_variations_code = $requested_variations->pluck('code')->toArray();
            $old_variations_codes = $product->variations->pluck('code')->toArray();
            $old_matched_variations = $requested_variations->whereIn('code', $old_variations_codes);
            $new_variations = $requested_variations->whereNotIn('code', $old_variations_codes);


            // delete old variations that didn't requested
            $product->variations->whereNotIn('code', $requested_variations_code)->each(function ($variation) {
                foreach ($variation->combinations as $comb) {
                    $comb->delete();
                }
                $variation->delete();
            });

            // update old matched variations
            foreach ($old_matched_variations as $variation) {
                $p_variation              = ProductVariation::where('product_id', $product->id)->where('code', $variation['code'])->first();
                $p_variation->price       = $variation['price'];
                $p_variation->stock       = $variation['stock'];
                $p_variation->current_stock       = $variation['current_stock'];
                $p_variation->sku         = $variation['sku'];
                $p_variation->img         = $variation['img'];
                $p_variation->save();
            }


            // insert new requested variations
            foreach ($new_variations as $variation) {
                $p_variation              = new ProductVariation;
                $p_variation->product_id  = $product->id;
                $p_variation->code        = $variation['code'];
                $p_variation->price       = $variation['price'];
                $p_variation->current_stock       = $variation['current_stock'];
                $p_variation->sku         = $variation['sku'];
                $p_variation->img         = $variation['img'];
                $p_variation->save();

                foreach (array_filter(explode("/", $variation['code'])) as $combination) {
                    $p_variation_comb                         = new ProductVariationCombination;
                    $p_variation_comb->product_id             = $product->id;
                    $p_variation_comb->product_variation_id   = $p_variation->id;
                    $p_variation_comb->attribute_id           = explode(":", $combination)[0];
                    $p_variation_comb->attribute_value_id     = explode(":", $combination)[1];
                    $p_variation_comb->save();
                }
            }
        } else {
            // If product switches from variant to non-variant, clear old variant combinations first.
            if ($oldProduct->is_variant) {
                $existingVariationIds = $product->variations()->pluck('id');
                if ($existingVariationIds->isNotEmpty()) {
                    ProductVariationCombination::whereIn('product_variation_id', $existingVariationIds)->delete();
                }
                $product->variations()->delete();
            }

            $this->upsertBaseVariationForSimpleProduct($product, $request);
        }


        // attributes + values
        foreach ($product->attributes as $attr) {
            $attr->delete();
        }
        foreach ($product->attribute_values as $attr_val) {
            $attr_val->delete();
        }
        if ($request->has('product_attributes') && $request->product_attributes[0] != null) {
            foreach ($request->product_attributes as $attr_id) {
                $attribute_values = 'attribute_' . $attr_id . '_values';
                if ($request->has($attribute_values) && $request->$attribute_values != null) {
                    $p_attribute = new ProductAttribute;
                    $p_attribute->product_id = $product->id;
                    $p_attribute->attribute_id = $attr_id;
                    $p_attribute->save();

                    foreach ($request->$attribute_values as $val_id) {
                        $p_attr_value = new ProductAttributeValue;
                        $p_attr_value->product_id = $product->id;
                        $p_attr_value->attribute_id = $attr_id;
                        $p_attr_value->attribute_value_id = $val_id;
                        $p_attr_value->save();
                    }
                }
            }
        }


        $product->save();
        cache_clear();
        Log::info('Product updated: base row saved', [
            'product_id' => $product->id,
            'shop_id' => $product->shop_id,
            'published' => (int) $product->published,
            'approved' => (int) $product->approved,
            'digital' => (int) $product->digital,
            'slug' => $product->slug,
        ]);

        flash(translate('Product has been updated successfully'))->success();
        return redirect()->route('product.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->product_translations()->delete();
        $product->variations()->delete();
        $product->variation_combinations()->delete();
        $product->reviews()->delete();
        $product->product_categories()->delete();
        $product->carts()->delete();
        $product->offers()->delete();
        $product->wishlists()->delete();
        $product->attributes()->delete();
        $product->attribute_values()->delete();
        $product->taxes()->delete();

        if (Product::destroy($id)) {
            flash(translate('Product has been deleted successfully'))->success();
            return redirect()->route('product.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    /**
     * Duplicates the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function duplicate(Request $request, $id)
    {
        $product = Product::find($id);
        $product_new = $product->replicate();
        $product_new->slug = Str::slug($product_new->name, '-') . '-' . strtolower(Str::random(5));

        if ($product_new->save()) {

            // variation duplicate
            foreach ($product->variations as $key => $variation) {
                $p_variation              = new ProductVariation;
                $p_variation->product_id  = $product_new->id;
                $p_variation->code        = $variation->code;
                $p_variation->price       = $variation->price;
                $p_variation->stock       = $variation->stock;
                $p_variation->sku         = $variation->sku;
                $p_variation->img         = $variation->img;
                $p_variation->save();

                // variation combination duplicate
                foreach ($variation->combinations as $key => $combination) {
                    $p_variation_comb                         = new ProductVariationCombination;
                    $p_variation_comb->product_id             = $product_new->id;
                    $p_variation_comb->product_variation_id   = $p_variation->id;
                    $p_variation_comb->attribute_id           = $combination->attribute_id;
                    $p_variation_comb->attribute_value_id     = $combination->attribute_value_id;
                    $p_variation_comb->save();
                }
            }

            // attribute duplicate
            foreach ($product->attributes as $key => $attribute) {
                $p_attribute                = new ProductAttribute;
                $p_attribute->product_id    = $product_new->id;
                $p_attribute->attribute_id  = $attribute->attribute_id;
                $p_attribute->save();
            }

            // attribute value duplicate
            foreach ($product->attribute_values as $key => $attribute_value) {
                $p_attr_value                       = new ProductAttributeValue;
                $p_attr_value->product_id           = $product_new->id;
                $p_attr_value->attribute_id         = $attribute_value->attribute_id;
                $p_attr_value->attribute_value_id   = $attribute_value->attribute_value_id;
                $p_attr_value->save();
            }

            // translation duplicate
            foreach ($product->product_translations as $key => $translation) {
                $product_translation                = new ProductTranslation;
                $product_translation->product_id    = $product_new->id;
                $product_translation->name          = $translation->name;
                $product_translation->unit          = $translation->unit;
                $product_translation->description   = $translation->description;
                $product_translation->lang          = $translation->lang;
                $product_translation->save();
            }

            //categories duplicate
            foreach ($product->product_categories as $key => $category) {
                $p_category                 = new ProductCategory;
                $p_category->product_id     = $product_new->id;
                $p_category->category_id    = $category->category_id;
                $p_category->save();
            }

            // taxes duplicate
            foreach ($product->taxes as $key => $tax) {
                $p_tax                = new ProductTax;
                $p_tax->product_id    = $product_new->id;
                $p_tax->tax_id        = $tax->tax_id;
                $p_tax->tax           = $tax->tax;
                $p_tax->tax_type      = $tax->tax_type;
                $p_tax->save();
            }

            flash(translate('Product has been duplicated successfully'))->success();
            return redirect()->route('product.index');
        } else {
            flash(translate('Something went wrong'))->error();
            return back();
        }
    }

    public function get_products_by_subcategory(Request $request)
    {
        $products = Product::whereHas('product_categories', function ($query) use ($request) {
            $query->where('category_id', $request->subcategory_id);
        })->get();
        return $products;
    }

    public function get_products_by_brand(Request $request)
    {
        $products = Product::where('brand_id', $request->brand_id)->get();
        return view('partials.product_select', compact('products'));
    }

    public function updatePublished(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->published = (int) $request->status;
        if (auth()->user()->user_type !== 'seller' && (int) $request->status === 1) {
            $product->approved = 1;
        }
        $product->save();

        cache_clear();
        Log::info('Product publish status updated', [
            'product_id' => $product->id,
            'published' => (int) $product->published,
            'approved' => (int) $product->approved,
        ]);

        return 1;
    }

    public function updateTodayDeal(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:products,id'],
            'status' => ['required', 'boolean'],
        ]);

        $product = Product::findOrFail($validated['id']);
        $product->today_deal = (int) $validated['status'];
        $product->save();

        cache_clear();
        Log::info('Product Today\'s Deal status updated', [
            'product_id' => $product->id,
            'today_deal' => (int) $product->today_deal,
        ]);

        return 1;
    }

    public function sku_combination(Request $request)
    {
        // dd($request->all());

        $option_choices = array();

        if ($request->has('product_options')) {
            $product_options = $request->product_options;
            sort($product_options, SORT_NUMERIC);

            foreach ($product_options as $key => $option) {

                $option_name = 'option_' . $option . '_choices';
                $choices = array();

                if ($request->has($option_name)) {

                    $product_option_values = $request[$option_name];
                    sort($product_option_values, SORT_NUMERIC);

                    foreach ($product_option_values as $key => $item) {
                        array_push($choices, $item);
                    }
                    $option_choices[$option] =  $choices;
                }
            }
        }

        $combinations = array(array());
        foreach ($option_choices as $property => $property_values) {
            $tmp = array();
            foreach ($combinations as $combination_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = $combination_item + array($property => $property_value);
                }
            }
            $combinations = $tmp;
        }

        // dd($option_choices,$combinations);

        return view('backend.product.products.sku_combinations', compact('combinations'))->render();
    }

    public function new_attribute(Request $request)
    {
        $attributes = Attribute::query();
        if ($request->has('product_attributes')) {
            foreach ($request->product_attributes as $key => $value) {
                if ($value == NULL) {
                    return array(
                        'count' => -1,
                        'view' => view('backend.product.products.new_attribute', compact('attributes'))->render(),
                    );
                }
            }
            $attributes->whereNotIn('id', array_diff($request->product_attributes, [null]));
        }

        $attributes = $attributes->get();

        return array(
            'count' => count($attributes),
            'view' => view('backend.product.products.new_attribute', compact('attributes'))->render(),
        );
    }

    public function get_attribute_values(Request $request)
    {

        $attribute_id = $request->attribute_id;
        $attribute_values = AttributeValue::where('attribute_id', $attribute_id)->get();

        return view('backend.product.products.new_attribute_values', compact('attribute_values', 'attribute_id'));
    }

    public function new_option(Request $request)
    {

        $attributes = Attribute::query();
        if ($request->has('product_options')) {
            foreach ($request->product_options as $key => $value) {
                if ($value == NULL) {
                    return array(
                        'count' => -1,
                        'view' => view('backend.product.products.new_option', compact('attributes'))->render(),
                    );
                }
            }
            $attributes->whereNotIn('id', array_diff($request->product_options, [null]));
            if (count($request->product_options) === 3) {
                return array(
                    'count' => -2,
                    'view' => view('backend.product.products.new_option', compact('attributes'))->render(),
                );
            }
        }

        $attributes = $attributes->get();

        return array(
            'count' => count($attributes),
            'view' => view('backend.product.products.new_option', compact('attributes'))->render(),
        );
    }

    public function get_option_choices(Request $request)
    {

        $attribute_id = $request->attribute_id;
        $attribute_values = AttributeValue::where('attribute_id', $attribute_id)->get();

        return view('backend.product.products.new_option_choices', compact('attribute_values', 'attribute_id'));
    }
    public function updateProductApproval(Request $request)
    {
        $product = Product::findOrFail($request->id);
        $product->approved = $request->approved;
        $shop = $product->shop;

        if ($shop->user->user_type == 'seller') {
            if (
                $shop->package_invalid_at == null
                || Carbon::now()->diffInDays(Carbon::parse($shop->package_invalid_at), false) < 0
                || $shop->product_upload_limit <= $shop->products()->where('published', 1)->count()
            ) {
                return 0;
            }
        }
        $product->save();
        cache_clear();
        return 1;
    }

    public function bulk_product_delete(Request $request)
    {

        if ($request->id) {
            foreach ($request->id as $product_id) {
                $this->destroy($product_id);
            }
        }

        return 1;
    }

    private function validateProductCategoryPayload(Request $request): void
    {
        $request->validate(
            [
                'main_category' => ['required', 'integer', 'exists:categories,id'],
                'category_ids' => ['required', 'array', 'min:1'],
                'category_ids.*' => ['required', 'integer', 'distinct', 'exists:categories,id'],
                'shop_id' => ['nullable', 'integer', 'exists:shops,id'],
            ],
            [
                'main_category.required' => translate('Select a main category'),
                'category_ids.required' => translate('Select at least one category'),
                'category_ids.min' => translate('Select at least one category'),
            ]
        );

        $selectedCategoryIds = collect($request->input('category_ids', []))
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();

        if (!$selectedCategoryIds->contains((int) $request->input('main_category'))) {
            throw ValidationException::withMessages([
                'main_category' => translate('Main category must be one of the selected categories.'),
            ]);
        }

        $request->merge([
            'category_ids' => $selectedCategoryIds->all(),
        ]);
    }

    private function resolveShopForProduct(Request $request, ?Product $product = null): ?Shop
    {
        $shopId = $request->input('shop_id');

        if (!$shopId) {
            $shopId = auth()->user()->shop_id ?: optional($product)->shop_id;
        }

        if (!$shopId) {
            return null;
        }

        $shop = Shop::find($shopId);

        if (!$shop) {
            return null;
        }

        return $shop;
    }

    private function syncProductAndShopCategories(Product $product, array $categoryIds, ?Shop $shop): array
    {
        $selectedCategoryIds = collect($categoryIds)
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        $product->categories()->sync($selectedCategoryIds);

        $rootCategoryIds = collect($selectedCategoryIds)
            ->map(function ($id) {
                return CategoryUtility::get_grand_parent_id($id);
            })
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            });

        $shopCategoryIds = [];

        if ($shop) {
            $existingShopCategoryIds = $shop->shop_categories()
                ->pluck('category_id')
                ->map(function ($id) {
                    return (int) $id;
                });

            $shopCategoryIds = $rootCategoryIds
                ->merge($existingShopCategoryIds)
                ->unique()
                ->values()
                ->all();

            $shop->categories()->sync($shopCategoryIds);
        }

        return [
            'selected_category_ids' => $selectedCategoryIds,
            'root_category_ids' => $rootCategoryIds->values()->all(),
            'shop_category_ids' => $shopCategoryIds,
        ];
    }

    private function upsertBaseVariationForSimpleProduct(Product $product, Request $request): void
    {
        $variation = $product->variations()->whereNull('code')->first();

        if (!$variation) {
            $variation = new ProductVariation;
            $variation->product_id = $product->id;
        }

        $variation->code = null;
        $variation->sku = $request->input('sku');
        $variation->price = (float) $request->input('price', 0);
        $variation->stock = (int) $request->input('stock', 1);
        $variation->current_stock = (int) $request->input('current_stock', 0);
        $variation->save();

        $extraVariationIds = $product->variations()
            ->where('id', '!=', $variation->id)
            ->pluck('id');

        if ($extraVariationIds->isNotEmpty()) {
            ProductVariationCombination::whereIn('product_variation_id', $extraVariationIds)->delete();
            ProductVariation::whereIn('id', $extraVariationIds)->delete();
        }
    }

    private function resolveApprovalStateForSave(): int
    {
        return auth()->user()->user_type === 'seller' ? 0 : 1;
    }

    private function applyProductOwnershipFields(Product $product): void
    {
        $user = auth()->user();

        if (Schema::hasColumn('products', 'user_id')) {
            $product->user_id = $user->id;
        }

        if (Schema::hasColumn('products', 'added_by')) {
            $product->added_by = $user->user_type === 'seller' ? 'seller' : 'admin';
        }
    }

    private function generateUniqueProductSlug(string $slugSource, ?int $exceptProductId = null): string
    {
        $baseSlug = Str::slug($slugSource, '-');
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'product';
        $slug = $baseSlug;

        $alreadyExists = Product::where('slug', $slug)
            ->when($exceptProductId, function ($query) use ($exceptProductId) {
                $query->where('id', '!=', $exceptProductId);
            })
            ->exists();

        while ($alreadyExists) {
            $slug = $baseSlug . '-' . strtolower(Str::random(5));
            $alreadyExists = Product::where('slug', $slug)
                ->when($exceptProductId, function ($query) use ($exceptProductId) {
                    $query->where('id', '!=', $exceptProductId);
                })
                ->exists();
        }

        return $slug;
    }
}
