<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchProvidersRequest;
use App\Http\Resources\ProviderResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Http\Controllers\Controller;

class ProvidersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param SearchProvidersRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        SearchProvidersRequest $request
    ) {
        return ProviderResource::collection(Implementation::searchProviders(
            $request
        )->paginate($request->input('per_page', 10)));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @return ProviderResource
     */
    public function show(Organization $organization)
    {
        return new ProviderResource($organization);
    }
}
