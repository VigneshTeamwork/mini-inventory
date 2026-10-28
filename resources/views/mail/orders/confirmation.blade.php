<x-mail::message>
# Thank you, {{ $order->customer->name }}!

Your order **{{ $order->order_number }}** was placed on {{ $order->placed_at->format('d M Y, h:i A') }}.

<x-mail::table>
| Product | Qty | Line Total |
|:--------|----:|-----------:|
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | ₹{{ $item->line_total }} |
@endforeach
| **Subtotal** | | ₹{{ $order->subtotal }} |
| **Tax** | | ₹{{ $order->tax_total }} |
| **Grand Total** | | **₹{{ $order->grand_total }}** |
</x-mail::table>

@if (config('inventory.attach_pdf_bill'))
A PDF copy of your bill is attached.
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
