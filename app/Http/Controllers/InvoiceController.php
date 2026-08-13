<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoicePdfService;
use App\Services\Payments\PaystackCheckout;
use App\Support\ListFilters;
use App\Support\Money;
use App\Support\PrivateStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $filters = ListFilters::fromRequest($request);
        $status = $filters['status'];

        $invoices = Invoice::query()
            ->with(['smsRequest:id,reference,name'])
            ->when(
                $status !== null && InvoiceStatus::tryFrom($status),
                fn ($query) => $query->where('status', $status),
            )
            ->tap(fn ($query) => ListFilters::applyDateRange(
                $query,
                $filters['from'],
                $filters['to'],
                'issued_at',
            ))
            ->when(
                $filters['q'] !== null,
                function ($query) use ($filters): void {
                    $term = '%'.$filters['q'].'%';

                    $query->where(function ($inner) use ($term): void {
                        $inner->where('number', 'like', $term)
                            ->orWhereHas('smsRequest', function ($campaign) use ($term): void {
                                $campaign->where('reference', 'like', $term)
                                    ->orWhere('name', 'like', $term);
                            });
                    });
                },
            )
            ->latest('issued_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => $this->summary($invoice));

        return Inertia::render('invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                ...$filters,
                'status' => $status !== null && InvoiceStatus::tryFrom($status) ? $status : null,
            ],
            'statusOptions' => collect(InvoiceStatus::cases())
                ->map(fn (InvoiceStatus $case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(Invoice $invoice, PaystackCheckout $paystack): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'items',
            'smsRequest:id,reference,name,status',
            'payments' => fn ($q) => $q->latest(),
            'payments.submitter:id,name',
        ]);

        $pendingPaystack = $invoice->payments
            ->first(fn ($payment) => $payment->status->value === 'pending' && $payment->provider === 'paystack');

        return Inertia::render('invoices/Show', [
            'invoice' => [
                ...$this->summary($invoice),
                'subtotal' => Money::format($invoice->subtotal_pesewas),
                'tax' => Money::format($invoice->tax_pesewas),
                'due_at' => $invoice->due_at?->toDateString(),
                'issued_at' => $invoice->issued_at->toIso8601String(),
                'items' => $invoice->items->map(fn ($item) => [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => Money::format($item->unit_price_pesewas),
                    'amount' => Money::format($item->amount_pesewas),
                ])->all(),
                'payments' => $invoice->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'provider' => $payment->provider,
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'amount' => Money::format($payment->amount_pesewas),
                    'reference' => $payment->provider_reference ?: $payment->momo_reference,
                    'payer' => $payment->payer_number,
                    'rejection_reason' => $payment->rejection_reason,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])->all(),
                'pdf_url' => $this->pdfUrl($invoice),
            ],
            'paystackEnabled' => $paystack->enabled(),
            'hasPendingPayment' => $pendingPaystack !== null,
            'can' => [
                'pay' => request()->user()?->can('pay', $invoice) ?? false,
                'download' => request()->user()?->can('download', $invoice) ?? false,
            ],
        ]);
    }

    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfs): StreamedResponse
    {
        $this->authorize('download', $invoice);

        $path = $pdfs->ensure($invoice);

        abort_unless(PrivateStorage::disk()->exists($path), 404);

        return PrivateStorage::disk()->download(
            $path,
            $invoice->number.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function startPaystack(
        Invoice $invoice,
        PaystackCheckout $checkout,
    ): \Symfony\Component\HttpFoundation\Response {
        $this->authorize('pay', $invoice);

        $url = $checkout->start($invoice, request()->user());

        // Full-page visit to Paystack (Inertia XHR cannot follow external checkout).
        return Inertia::location($url);
    }

    public function paystackCallback(
        Request $request,
        Invoice $invoice,
        PaystackCheckout $checkout,
    ): RedirectResponse {
        $this->authorize('view', $invoice);

        $reference = (string) $request->query('reference', '');

        if ($reference === '') {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Payment reference missing. If you paid, wait a moment and refresh.');
        }

        try {
            $checkout->confirmByReference($reference);
        } catch (ValidationException $e) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', collect($e->errors())->flatten()->first() ?: 'Payment could not be confirmed yet.');
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Payment confirmed. Thank you.');
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'status' => $invoice->status->value,
            'status_label' => $invoice->status->label(),
            'total' => Money::format($invoice->total_pesewas),
            'total_pesewas' => $invoice->total_pesewas,
            'total_major' => Money::toMajor($invoice->total_pesewas),
            'campaign_reference' => $invoice->smsRequest?->reference,
            'campaign_name' => $invoice->smsRequest?->name,
            'issued_at' => $invoice->issued_at->toIso8601String(),
            'pdf_url' => $this->pdfUrl($invoice),
        ];
    }

    private function pdfUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute(
            'invoices.pdf',
            now()->addMinutes(30),
            ['invoice' => $invoice->id],
            absolute: false,
        );
    }
}
