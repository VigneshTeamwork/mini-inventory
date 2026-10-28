<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ChangeCalculator;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class BillController extends Controller
{
    public function show(Order $order, ChangeCalculator $change): View
    {
        $order->load(['customer', 'items']);

        return view('bills.show', [
            'order' => $order,
            'changeBreakdown' => $order->change_due === null ? [] : $change->breakdown(Money::toPaise($order->change_due)),
        ]);
    }

    public function pdf(Order $order): Response
    {
        $order->load(['customer', 'items']);

        return Pdf::loadView('bills.pdf', ['order' => $order])
            ->download("bill-{$order->order_number}.pdf");
    }
}
