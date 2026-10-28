<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LowStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(Product::query()->orderBy('name')->get());
    }

    /**
     * Products strictly below the low-stock threshold, lowest stock first.
     */
    public function lowStock(LowStockRequest $request): AnonymousResourceCollection
    {
        $threshold = $request->threshold();

        $products = Product::query()
            ->belowStock($threshold)
            ->orderBy('stock_on_hand')
            ->orderBy('name')
            ->get();

        return ProductResource::collection($products)
            ->additional(['meta' => ['threshold' => $threshold]]);
    }
}
