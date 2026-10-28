<?php

use App\Models\Customer;
use App\Models\Product;

it('lists products alphabetically for the counter dropdown', function () {
    Product::factory()->create(['name' => 'Milk 1L']);
    Product::factory()->create(['name' => 'Bread']);

    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonPath('data.*.name', ['Bread', 'Milk 1L']);
});

it('looks up a returning customer by email, case-insensitively', function () {
    Customer::factory()->create(['name' => 'Thomas', 'email' => 'thomas@example.com']);

    $this->getJson('/api/customers/lookup?email='.urlencode(' Thomas@Example.com '))
        ->assertOk()
        ->assertJsonPath('data.name', 'Thomas')
        ->assertJsonPath('data.email', 'thomas@example.com');
});

it('returns 404 when the customer is new', function () {
    $this->getJson('/api/customers/lookup?email=new@example.com')
        ->assertNotFound()
        ->assertJsonPath('message', 'Customer not found.');
});

it('requires an email for lookup', function () {
    $this->getJson('/api/customers/lookup')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
