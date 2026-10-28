@extends('layouts.app')

@section('title', "Bill {$order->order_number} · Store Billing")
@section('heading', 'Bill')

@section('content')
@if (request()->boolean('new'))
    <div class="alert alert-success no-print">
        Order placed. A confirmation email with the PDF bill has been queued for {{ $order->customer->email }}.
    </div>
@endif

<section class="card">
    <div class="row">
        <div>
            <h2 style="margin-bottom:4px">{{ $order->order_number }}</h2>
            <div class="muted">{{ $order->placed_at->format('d M Y, h:i A') }} · {{ ucfirst($order->status) }}</div>
        </div>
        <div style="text-align:right">
            <strong>{{ $order->customer->name }}</strong><br>
            <span class="muted">{{ $order->customer->email }}</span>
        </div>
    </div>
</section>

<section class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="num">Qty</th>
                    <th class="num">Price</th>
                    <th class="num">Tax</th>
                    <th class="num">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }} <span class="muted">({{ $item->product_code }})</span></td>
                        <td class="num">{{ $item->quantity }}</td>
                        <td class="num">₹{{ $item->unit_price }}</td>
                        <td class="num">₹{{ $item->line_tax }} <span class="muted">({{ rtrim(rtrim($item->tax_percent, '0'), '.') }}%)</span></td>
                        <td class="num">₹{{ $item->line_total }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals" style="max-width:360px;margin:16px 0 0 auto">
        <div><span>Subtotal</span><strong>₹{{ $order->subtotal }}</strong></div>
        <div><span>Tax</span><strong>₹{{ $order->tax_total }}</strong></div>
        <div class="grand"><span>Grand Total</span><span>₹{{ $order->grand_total }}</span></div>
        @if ($order->amount_tendered !== null)
            <div><span>Amount Given</span><span>₹{{ $order->amount_tendered }}</span></div>
            <div><span>Balance Returned</span><strong>₹{{ $order->change_due }}</strong></div>
            @if ($changeBreakdown)
                <div class="muted" style="font-size:13px"><span></span>
                    <span>→ {{ collect($changeBreakdown)->map(fn ($c) => "{$c['count']}×{$c['denomination']}")->implode(' + ') }}</span>
                </div>
            @endif
        @endif
    </div>
</section>

<div class="actions no-print">
    <button type="button" onclick="window.print()">Print</button>
    <a class="btn btn-outline" href="{{ route('bills.pdf', $order) }}">Download PDF</a>
    <a class="btn btn-success" href="{{ route('counter') }}">New Order</a>
</div>
@endsection
