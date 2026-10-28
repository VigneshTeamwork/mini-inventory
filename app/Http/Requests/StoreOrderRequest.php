<?php

namespace App\Http\Requests;

use App\DTOs\OrderData;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $customer = $this->input('customer');

        if (is_array($customer) && is_string($customer['email'] ?? null)) {
            $customer['email'] = Customer::normalizeEmail($customer['email']);
            $this->merge(['customer' => $customer]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],
            'customer.email' => ['required', 'string', 'email:rfc', 'max:191'],
            'customer.name' => ['nullable', 'string', 'max:150'],
            'items' => ['required', 'array', 'min:1', 'max:'.config('inventory.max_lines')],
            'items.*' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.config('inventory.max_quantity')],
            'amount_tendered' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer.email' => 'email',
            'customer.name' => 'name',
            'items.*.product_id' => 'product',
            'items.*.quantity' => 'quantity',
            'amount_tendered' => 'amount given',
        ];
    }

    /**
     * Checks that need the database. The stock check here only gives fast
     * feedback; CreateOrderAction re-checks against locked rows.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $data = OrderData::fromArray($validator->getData());

                if ($data->name === null && ! Customer::query()->byEmail($data->email)->exists()) {
                    $validator->errors()->add('customer.name', 'The name field is required for a new customer.');
                }

                $products = Product::query()->whereIn('id', $data->productIds())->get()->keyBy('id');

                foreach ($data->lines as $line) {
                    $product = $products[$line->productId];

                    if ($product->stock_on_hand < $line->quantity) {
                        $validator->errors()->add(
                            "items.{$line->index}.quantity",
                            InsufficientStockException::messageFor($product, $line->quantity),
                        );
                    }
                }
            },
        ];
    }

    public function toOrderData(): OrderData
    {
        return OrderData::fromArray($this->validated());
    }
}
