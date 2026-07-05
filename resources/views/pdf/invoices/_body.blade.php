@php use App\Support\Money; @endphp
<table>
    <thead>
        <tr>
            <th>{{ __('fields.name') }}</th>
            <th class="num">{{ __('inventory.quantity') }}</th>
            <th class="num">{{ __('sales.unit_price') }}</th>
            <th class="num">{{ __('purchasing.total') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->items as $item)
            <tr>
                <td>{{ $item->product?->name }}@if ($item->variant) <span class="muted">({{ $item->variant->label() }})</span>@endif</td>
                <td class="num">{{ number_format($item->quantity) }}</td>
                <td class="num">{{ Money::format($item->unit_price) }}</td>
                <td class="num">{{ Money::format($item->total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>{{ __('sales.subtotal') }}</td><td class="num">{{ Money::format($invoice->total_amount) }}</td></tr>
    @if ($invoice->discount_amount > 0)
        <tr><td>{{ __('sales.discount') }}</td><td class="num">- {{ Money::format($invoice->discount_amount) }}</td></tr>
    @endif
    <tr class="grand"><td>{{ __('sales.net') }}</td><td class="num">{{ Money::format($invoice->net_amount) }}</td></tr>
    <tr><td class="muted">{{ __('purchasing.paid') }}</td><td class="num">{{ Money::format($invoice->paid_amount) }}</td></tr>
    <tr><td class="muted">{{ __('sales.balance') }}</td><td class="num">{{ Money::format($invoice->balance) }}</td></tr>
    <tr><td class="muted">≈ USD</td><td class="num">$ {{ number_format((float) $invoice->net_amount_usd, 2) }}</td></tr>
</table>

@if ($invoice->notes)
    <div class="muted notes">{{ $invoice->notes }}</div>
@endif

<div class="footer">{{ $footer ?? __('sales.thank_you') }}</div>

@if (! empty($print))
    <script>window.onload = () => window.print();</script>
@endif
