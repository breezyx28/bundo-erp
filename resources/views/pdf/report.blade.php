@php $rtl = app()->getLocale() === 'ar'; $dir = $rtl ? 'rtl' : 'ltr'; @endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1d2a2a; padding: 32px; direction: {{ $dir }}; }
        h1 { color: #228c70; border-bottom: 3px solid #39c6a0; padding-bottom: 10px; font-size: 22px; }
        .muted { color: #8d9a9a; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #eef2f1; text-align: {{ $rtl ? 'right' : 'left' }}; padding: 8px 12px; font-size: 12px; }
        td { padding: 8px 12px; border-bottom: 1px solid #eef2f1; }
        td.num { text-align: {{ $rtl ? 'left' : 'right' }}; font-variant-numeric: tabular-nums; }
        .footer { margin-top: 30px; color: #8d9a9a; font-size: 11px; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — {{ $title }}</h1>
    <div class="muted">{{ $from }} → {{ $to }} · {{ $generatedAt }}</div>

    <table>
        <thead><tr><th>{{ __('fields.name') }}</th><th class="num">{{ __('purchasing.amount') }}</th></tr></thead>
        <tbody>
            @foreach ($rows as $row)
                <tr><td>{{ $row['label'] }}</td><td class="num">{{ $row['value'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">{{ config('app.name') }}</div>
</body>
</html>
