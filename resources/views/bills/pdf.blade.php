<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bill {{ $order->order_number }}</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 12px; color: #1f2937; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 6px; border-bottom: 1px solid #d1d5db; text-align: left; }
        th { background: #f3f4f6; }
        .num { text-align: right; }
        .totals { width: 45%; margin-left: 55%; margin-top: 12px; }
        .totals td { border: 0; padding: 3px 6px; }
        .grand td { font-weight: bold; font-size: 14px; border-top: 1px dashed #9ca3af; }
    </style>
</head>
<body>
    <h1>Store Billing</h1>
    <div class="muted">Bill {{ $order->order_number }} · {{ $order->placed_at->format('d M Y, h:i A') }}</div>
    <p><strong>{{ $order->customer->name }}</strong><br><span class="muted">{{ $order->customer->email }}</span></p>

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
                    <td>{{ $item->product_name }} ({{ $item->product_code }})</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">₹{{ $item->unit_price }}</td>
                    <td class="num">₹{{ $item->line_tax }}</td>
                    <td class="num">₹{{ $item->line_total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">₹{{ $order->subtotal }}</td></tr>
        <tr><td>Tax</td><td class="num">₹{{ $order->tax_total }}</td></tr>
        <tr class="grand"><td>Grand Total</td><td class="num">₹{{ $order->grand_total }}</td></tr>
        @if ($order->amount_tendered !== null)
            <tr><td>Amount Given</td><td class="num">₹{{ $order->amount_tendered }}</td></tr>
            <tr><td>Balance Returned</td><td class="num">₹{{ $order->change_due }}</td></tr>
        @endif
    </table>

    <p class="muted" style="margin-top:24px">Thank you for shopping with us.</p>
</body>
</html>
