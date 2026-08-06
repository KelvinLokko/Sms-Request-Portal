<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentProvider;
use App\Http\Requests\Payments\StorePaymentRequest;
use App\Models\Invoice;
use App\Support\Money;
use App\Support\PrivateStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['smsRequest:id,reference,name'])
            ->latest('issued_at')
            ->paginate(15)
            ->through(fn (Invoice $invoice) => $this->summary($invoice));

        return Inertia::render('invoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load([
            'items',
            'smsRequest:id,reference,name,status',
            'payments' => fn ($q) => $q->latest(),
            'payments.submitter:id,name',
        ]);

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
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'amount' => Money::format($payment->amount_pesewas),
                    'momo_reference' => $payment->momo_reference,
                    'payer_number' => $payment->payer_number,
                    'rejection_reason' => $payment->rejection_reason,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ])->all(),
                'pdf_url' => $invoice->hasPdf()
                    ? URL::temporarySignedRoute(
                        'invoices.pdf',
                        now()->addMinutes(30),
                        ['invoice' => $invoice->id],
                    )
                    : null,
            ],
            'can' => [
                'pay' => request()->user()?->can('pay', $invoice) ?? false,
            ],
        ]);
    }

    public function downloadPdf(Invoice $invoice): StreamedResponse
    {
        $this->authorize('download', $invoice);
        abort_unless($invoice->hasPdf(), 404);
        abort_unless(PrivateStorage::disk()->exists($invoice->pdf_path), 404);

        return PrivateStorage::disk()->download(
            $invoice->pdf_path,
            $invoice->number.'.pdf',
        );
    }

    public function storePayment(
        StorePaymentRequest $request,
        Invoice $invoice,
        PaymentProvider $provider,
    ): RedirectResponse {
        $proofPath = null;
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $proofPath = $file->storeAs(
                'payment-proofs/'.$invoice->company_id.'/'.$invoice->id,
                Str::uuid()->toString().'.'.$file->getClientOriginalExtension(),
                PrivateStorage::name(),
            );
        }

        $provider->submit($invoice, $request->user(), [
            'amount_pesewas' => Money::fromMajor($request->validated('amount')),
            'momo_reference' => $request->validated('momo_reference'),
            'payer_number' => $request->validated('payer_number'),
            'proof_path' => $proofPath,
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Payment submitted for verification.');
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
        ];
    }
}
