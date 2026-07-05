@php
    $rtl = app()->getLocale() === 'ar';
    $dir = $rtl ? 'rtl' : 'ltr';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #222; font-size: 13px; margin: 0; padding: 40px; direction: {{ $dir }}; }
        .top { text-align: center; margin-bottom: 28px; border-bottom: 1px solid #ddd; padding-bottom: 16px; }
        .brand { font-size: 24px; font-weight: 700; letter-spacing: .5px; }
        .meta { color: #666; margin-top: 6px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 24px; gap: 16px; }
        .muted { color: #777; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #eee; }
        th { font-size: 11px; text-transform: uppercase; color: #666; text-align: {{ $rtl ? 'right' : 'left' }}; }
        .num { text-align: {{ $rtl ? 'left' : 'right' }}; }
        .totals { width: 260px; margin-{{ $rtl ? 'right' : 'left' }}: auto; }
        .totals td { border: none; }
        .grand td { font-weight: 700; font-size: 15px; border-top: 1px solid #222; padding-top: 8px; }
        .footer { margin-top: 28px; text-align: center; color: #888; font-size: 11px; }
        .notes { margin-top: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="top">
        <div class="brand">{{ $tenant?->name ?? config('app.name') }}</div>
        <div class="meta">{{ __('sales.invoice') }} #{{ $invoice->invoice_number }} · {{ $invoice->invoice_date?->format('Y-m-d') }}</div>
    </div>

    <div class="row">
        <div>
            <div class="muted">{{ __('sales.bill_to') }}</div>
            <strong>{{ $invoice->customer?->name ?? __('sales.walk_in') }}</strong>
        </div>
        <div style="text-align: {{ $rtl ? 'left' : 'right' }}">
            <div class="muted">{{ __('sales.pay.' . $invoice->payment_status) }}</div>
            <div>{{ __('sales.exchange_rate') }}: {{ number_format((float) $invoice->exchange_rate, 2) }}</div>
        </div>
    </div>

    @include('pdf.invoices._body')
</body>
</html>
