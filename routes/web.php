<?php

use App\Http\Controllers\Web\BillController;
use App\Http\Controllers\Web\CounterController;
use Illuminate\Support\Facades\Route;

Route::get('/', CounterController::class)->name('counter');

Route::get('/orders/{order}', [BillController::class, 'show'])->name('bills.show');
Route::get('/orders/{order}/pdf', [BillController::class, 'pdf'])->name('bills.pdf');
