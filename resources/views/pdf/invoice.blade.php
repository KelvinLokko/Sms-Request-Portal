<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        @page { margin: 36px 40px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            line-height: 1.45;
        }
        h1 { font-size: 20px; margin: 0 0 2px; letter-spacing: -0.02em; }
        h2 { font-size: 13px; margin: 0 0 6px; text-transform: uppercase; letter-spacing: 0.04em; color: #444; }
        .muted { color: #666; }
        .small { font-size: 10px; }
        .rule { border: 0; border-top: 1px solid #ddd; margin: 18px 0; }
        .header { width: 100%; }
        .header td { vertical-align: top; }
        .brand { font-size: 18px; font-weight: bold; }
        .invoice-meta { text-align: right; }
        .invoice-meta .label { color: #666; }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border: 1px solid #ccc;
            border-radius: 999px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .party { width: 100%; margin-top: 8px; }
        .party td { width: 50%; vertical-align: top; padding-right: 16px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.items th {
            text-align: left;
            padding: 8px 6px;
            border-bottom: 2px solid #222;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #444;
        }
        table.items td {
            padding: 9px 6px;
            border-bottom: 1px solid #e5e5e5;
            vertical-align: top;
        }
        table.items .num { text-align: right; white-space: nowrap; }
        table.totals { width: 42%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        table.totals td { padding: 5px 0; }
        table.totals .label { text-align: right; color: #666; padding-right: 16px; }
        table.totals .value { text-align: right; white-space: nowrap; }
        table.totals .grand td {
            border-top: 2px solid #222;
            padding-top: 8px;
            font-size: 13px;
            font-weight: bold;
        }
        .footer {
            margin-top: 28px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">{{ $brand }}</div>
                <div class="muted small" style="margin-top: 6px;">
                    @if (! empty($contact['email'])){{ $contact['email'] }}<br>@endif
                    @if (! empty($contact['phone'])){{ $contact['phone'] }}@endif
                </div>
            </td>
            <td class="invoice-meta">
                <h1>Invoice</h1>
                <div style="margin-top: 8px;">
                    <strong>{{ $invoice->number }}</strong>
                </div>
                <div class="muted" style="margin-top: 6px;">
                    <span class="label">Issued</span>
                    {{ $invoice->issued_at?->format('d M Y') ?? '—' }}<br>
                    @if ($invoice->due_at)
                        <span class="label">Due</span>
                        {{ $invoice->due_at->format('d M Y') }}<br>
                    @endif
                    <span class="badge" style="margin-top: 8px;">{{ $invoice->status->label() }}</span>
                </div>
            </td>
        </tr>
    </table>

    <hr class="rule">

    <table class="party">
        <tr>
            <td>
                <h2>Bill to</h2>
                <strong>{{ $invoice->company->name }}</strong><br>
                {{ $invoice->company->email }}
                @if ($invoice->company->phone)
                    <br>{{ $invoice->company->phone }}
                @endif
            </td>
            <td>
                <h2>Campaign</h2>
                <strong>{{ $invoice->smsRequest->reference }}</strong>
                @if ($invoice->smsRequest->name)
                    <br>{{ $invoice->smsRequest->name }}
                @endif
                @if ($invoice->smsRequest->senderId)
                    <br class="muted">Sender ID: {{ $invoice->smsRequest->senderId->value }}
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 48%;">Description</th>
                <th class="num" style="width: 12%;">Qty</th>
                <th class="num" style="width: 20%;">Unit price</th>
                <th class="num" style="width: 20%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ number_format($item->quantity) }}</td>
                    <td class="num">{{ \App\Support\Money::format($item->unit_price_pesewas, $invoice->currency) }}</td>
                    <td class="num">{{ \App\Support\Money::format($item->amount_pesewas, $invoice->currency) }}</td>
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
        <tr class="grand">
            <td class="label">Total due</td>
            <td class="value">{{ \App\Support\Money::format($invoice->total_pesewas, $invoice->currency) }}</td>
        </tr>
    </table>

    <div class="footer">
        <strong>Payment instructions</strong><br>
        Pay via Mobile Money and submit your payment reference in the client portal.
        This invoice is immutable; corrections are issued as credit notes.
        @if ($invoice->issuer)
            <br><br>Issued by {{ $invoice->issuer->name }}.
        @endif
    </div>
</body>
</html>
