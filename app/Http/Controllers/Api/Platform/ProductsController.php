<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ProductResource;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request
    ) {
        $this->authorize('indexPublic', Product::class);

        $organizationIds = FundProvider::query()->whereIn(
            'fund_id', Implementation::activeFunds()->pluck('id')
        )->where([
            'state' => 'approved'
        ])->pluck('organization_id');

        $query = Product::query()->whereIn(
            'organization_id', $organizationIds
        )->where('sold_out', false)->where(
            'expire_at', '>', date('Y-m-d')
        )->orderBy('created_at', 'desc');

        if ($request->has('product_category_id')) {
            $query->where('product_category_id', $request->input(
                'product_category_id'
            ));
        }

        if ($request->has('q')) {
            $query->where(function (Builder $query) use($request){
                return $query->where('name', 'LIKE', "%{$request->input('q')}%")
                    ->orWhere('description', 'LIKE', "%{$request->input('q')}%");
            });

        }

        return ProductResource::collection($query->with(
            ProductResource::$load
        )->paginate(15));
    }

    /**
     * Display a listing of the resource
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sample()
    {
        $this->authorize('indexPublic', Product::class);

        $organizationIds = FundProvider::query()->whereIn(
            'fund_id', Implementation::activeFunds()->pluck('id')
        )->where('state', 'approved')->pluck('organization_id');

        $products = Product::query()->select([
            'id', 'organization_id'
        ])->whereIn(
            'organization_id', $organizationIds
        )->where('sold_out', false)->where(
            'expire_at', '>', date('Y-m-d')
        )->has('medias')->get();

        $groupedProducts = $products->groupBy('organization_id');

        $resultProducts = collect($groupedProducts->random(
            min(6, $groupedProducts->count())
        )->map(function($products) {
            return collect($products)->random();
        }));

        if ($resultProducts->count() < 6) {
            $remainingProducts = $groupedProducts->flatten()->diff($resultProducts);
            $resultProducts = $resultProducts->merge(
                $remainingProducts->random(min(6 - $resultProducts->count(), $remainingProducts->count()))
            );
        }

        return ProductResource::collection(Product::query()->whereIn(
            'id', $resultProducts->pluck('id')
        )->get()->load(ProductResource::$load));
    }

    /**
     * @param Product $product
     * @return ProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Product $product)
    {
        $this->authorize('showPublic', $product);

        return new ProductResource($product->load(ProductResource::$load));
    }
}
