<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerLookupController extends Controller
{
    /**
     * Used by the counter screen to auto-fill a returning customer's name.
     */
    public function __invoke(Request $request): CustomerResource
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:191'],
        ]);

        $customer = Customer::query()->byEmail($validated['email'])->first();

        abort_if($customer === null, 404, 'Customer not found.');

        return new CustomerResource($customer);
    }
}
