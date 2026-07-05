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
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 11px; margin: 0; padding: 20px; direction: {{ $dir }}; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .brand { font-size: 14px; font-weight: 700; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 4px 6px; border: 1px solid #ddd; }
        th { background: #f5f5f5; font-size: 10px; text-align: {{ $rtl ? 'right' : 'left' }}; }
        .num { text-align: {{ $rtl ? 'left' : 'right' }}; }
        .totals { width: 220px; margin-{{ $rtl ? 'right' : 'left' }}: auto; font-size: 11px; }
        .totals td { border: none; padding: 2px 6px; }
        .grand td { font-weight: 700; border-top: 1px solid #111; }
        .footer { margin-top: 12px; text-align: center; color: #888; font-size: 10px; }
        .notes { margin-top: 8px; }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <div class="brand">{{ $tenant?->name ?? config('app.name') }}</div>
            <div class="muted">{{ $invoice->branch?->name }} · {{ $invoice->invoice_date?->format('Y-m-d') }}</div>
        </div>
        <div class="num">
            <strong>#{{ $invoice->invoice_number }}</strong><br>
            <span class="muted">{{ __('sales.pay.' . $invoice->payment_status) }}</span>
        </div>
    </div>

    <div class="muted" style="margin-bottom: 8px;">
        {{ __('sales.bill_to') }}: <strong style="color:#111">{{ $invoice->customer?->name ?? __('sales.walk_in') }}</strong>
    </div>

    @include('pdf.invoices._body')
</body>
</html>
