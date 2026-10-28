<?php

namespace App\Http\Controllers\Api;

use App\Actions\CreateOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request, CreateOrderAction $createOrder): JsonResponse
    {
        $order = $createOrder->execute($request->toOrderData());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }
}
