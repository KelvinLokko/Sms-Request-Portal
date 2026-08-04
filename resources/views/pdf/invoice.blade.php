<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .totals { width: 40%; margin-left: auto; margin-top: 16px; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .label { text-align: right; color: #555; }
        .totals .value { text-align: right; font-weight: bold; }
        .header { display: table; width: 100%; }
        .header > div { display: table-cell; vertical-align: top; width: 50%; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>{{ $brand }}</h1>
            <p class="muted">
                @if (! empty($contact['email'])){{ $contact['email'] }}<br>@endif
                @if (! empty($contact['phone'])){{ $contact['phone'] }}@endif
            </p>
        </div>
        <div style="text-align: right;">
            <h1>Invoice</h1>
            <p>
                <strong>{{ $invoice->number }}</strong><br>
                Issued {{ $invoice->issued_at?->format('d M Y') }}<br>
                @if ($invoice->due_at)
                    Due {{ $invoice->due_at->format('d M Y') }}
                @endif
            </p>
        </div>
    </div>

    <p>
        <strong>Bill to</strong><br>
        {{ $invoice->company->name }}<br>
        {{ $invoice->company->email }}
        @if ($invoice->company->phone)<br>{{ $invoice->company->phone }}@endif
    </p>

    <p class="muted">
        Campaign {{ $invoice->smsRequest->reference }}
        @if ($invoice->smsRequest->senderId)
            · Sender ID {{ $invoice->smsRequest->senderId->value }}
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format($item->quantity) }}</td>
                    <td>{{ \App\Support\Money::format($item->unit_price_pesewas, $invoice->currency) }}</td>
                    <td>{{ \App\Support\Money::format($item->amount_pesewas, $invoice->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="value">{{ \App\Support\Money::format($invoice->subtotal_pesewas, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td class="label">Tax</td>
            <td class="value">{{ \App\Support\Money::format($invoice->tax_pesewas, $invoice->currency) }}</td>
        </tr>
        <tr>
            <td class="label">Total</td>
            <td class="value">{{ \App\Support\Money::format($invoice->total_pesewas, $invoice->currency) }}</td>
        </tr>
    </table>

    <p class="muted" style="margin-top: 32px;">
        Pay via Mobile Money and submit your payment reference in the client portal.
        This invoice is immutable; corrections are issued as credit notes.
    </p>
</body>
</html>
