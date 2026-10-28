<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Low Stock Threshold
    |--------------------------------------------------------------------------
    |
    | Products whose stock on hand is strictly below this value are reported
    | by GET /api/products/low-stock. Callers may override it per request
    | with the ?threshold= query parameter.
    |
    */

    'low_stock_threshold' => (int) env('LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Order Limits
    |--------------------------------------------------------------------------
    */

    'max_lines' => 50,

    'max_quantity' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Billing
    |--------------------------------------------------------------------------
    |
    | Currency symbol used on bills, and whether the confirmation email
    | carries a PDF copy of the bill.
    |
    */

    'currency_symbol' => '₹',

    'attach_pdf_bill' => (bool) env('BILL_PDF_ATTACHMENT', true),

];
