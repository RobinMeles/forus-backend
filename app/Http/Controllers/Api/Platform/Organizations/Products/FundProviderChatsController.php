<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Products;

use App\Http\Controllers\Controller;
use App\Http\Resources\FundProviderChatResource;
use App\Models\FundProviderChat;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Http\Request;

class FundProviderChatsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Product $product
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization,
        Product $product
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        return FundProviderChatResource::collection(FundProviderChat::where([
            'product_id' => $product->id
        ])->paginate($request->input('per_page')));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Product $product
     * @param FundProviderChat $fundProviderChat
     * @return FundProviderChatResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Product $product,
        FundProviderChat $fundProviderChat
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        return new FundProviderChatResource($fundProviderChat);
    }
}
