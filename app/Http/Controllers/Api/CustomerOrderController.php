<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerOrdersRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerOrderController extends Controller
{
    /**
     * A customer's order history, newest first.
     */
    public function index(CustomerOrdersRequest $request, string $email): AnonymousResourceCollection
    {
        $customer = Customer::query()->byEmail($email)->first();

        abort_if($customer === null, 404, 'Customer not found.');

        $orders = $customer->orders()
            ->with('items')
            ->latest('placed_at')
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return OrderResource::collection($orders)
            ->additional(['customer' => new CustomerResource($customer)]);
    }
}
