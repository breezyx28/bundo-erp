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
        body { font-family: DejaVu Sans, sans-serif; color: #1d2a2a; font-size: 13px; margin: 0; padding: 32px; direction: {{ $dir }}; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #39c6a0; padding-bottom: 16px; margin-bottom: 24px; }
        .brand { font-size: 22px; font-weight: 700; color: #228c70; }
        .muted { color: #8d9a9a; }
        .doc-title { font-size: 20px; font-weight: 700; text-align: {{ $rtl ? 'left' : 'right' }}; }
        .meta { margin: 4px 0; }
        .parties { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .box { background: #f7faf9; border-radius: 8px; padding: 12px 16px; min-width: 45%; }
        .label { font-size: 11px; text-transform: uppercase; color: #8d9a9a; letter-spacing: .5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #eef2f1; text-align: {{ $rtl ? 'right' : 'left' }}; padding: 8px 10px; font-size: 11px; text-transform: uppercase; color: #555; }
        td { padding: 8px 10px; border-bottom: 1px solid #eef2f1; }
        .num { text-align: {{ $rtl ? 'left' : 'right' }}; font-variant-numeric: tabular-nums; }
        .totals { width: 280px; margin-{{ $rtl ? 'right' : 'left' }}: auto; }
        .totals td { border: none; padding: 4px 10px; }
        .totals .grand { border-top: 2px solid #1d2a2a; font-weight: 700; font-size: 15px; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
        .paid { background: #dcfce7; color: #166534; }
        .partial { background: #fef9c3; color: #854d0e; }
        .unpaid { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 32px; text-align: center; color: #8d9a9a; font-size: 11px; }
        .notes { margin-top: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="brand">{{ $tenant?->name ?? config('app.name') }}</div>
            <div class="muted">{{ $invoice->branch?->name }}</div>
            @if ($invoice->branch?->phone)<div class="muted">{{ $invoice->branch->phone }}</div>@endif
        </div>
        <div>
            <div class="doc-title">{{ __('sales.invoice') }}</div>
            <div class="meta"># {{ $invoice->invoice_number }}</div>
            <div class="meta muted">{{ $invoice->invoice_date?->format('Y-m-d') }}</div>
            <span class="badge {{ $invoice->payment_status }}">{{ __('sales.pay.' . $invoice->payment_status) }}</span>
        </div>
    </div>

    <div class="parties">
        <div class="box">
            <div class="label">{{ __('sales.bill_to') }}</div>
            <div style="font-weight:600">{{ $invoice->customer?->name ?? __('sales.walk_in') }}</div>
            @if ($invoice->customer?->phone)<div class="muted">{{ $invoice->customer->phone }}</div>@endif
        </div>
        <div class="box">
            <div class="label">{{ __('sales.details') }}</div>
            <div>{{ __('sales.sale_type') }}: {{ __('sales.type.' . $invoice->sale_type) }}</div>
            @if ($invoice->due_date)<div>{{ __('sales.due_date') }}: {{ $invoice->due_date->format('Y-m-d') }}</div>@endif
            <div>{{ __('sales.exchange_rate') }}: {{ number_format((float) $invoice->exchange_rate, 2) }}</div>
        </div>
    </div>

    @include('pdf.invoices._body')
</body>
</html>
