<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PaymentProvider;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectPaymentRequest;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentReviewController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with([
                'company:id,name',
                'invoice:id,number,total_pesewas,sms_request_id',
                'invoice.smsRequest:id,reference',
                'submitter:id,name,email',
            ])
            ->where('status', PaymentStatus::Pending)
            ->latest()
            ->paginate(20)
            ->through(fn (Payment $payment) => $this->row($payment));

        return Inertia::render('admin/payments/Index', [
            'payments' => $payments,
        ]);
    }

    public function report(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $status = $request->string('status')->toString();
        $search = $request->string('q')->trim()->toString();

        $payments = Payment::query()
            ->with([
                'company:id,name',
                'invoice:id,number,total_pesewas,sms_request_id',
                'invoice.smsRequest:id,reference',
                'submitter:id,name,email',
                'verifier:id,name',
            ])
            ->when(
                in_array($status, PaymentStatus::values(), true),
                fn ($q) => $q->where('status', $status),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('momo_reference', 'like', "%{$search}%")
                        ->orWhere('payer_number', 'like', "%{$search}%")
                        ->orWhereHas('company', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('invoice', fn ($i) => $i->where('number', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Payment $payment) => [
                ...$this->row($payment),
                'verified_at' => $payment->verified_at?->toIso8601String(),
                'verifier' => $payment->verifier
                    ? ['name' => $payment->verifier->name]
                    : null,
                'rejection_reason' => $payment->rejection_reason,
            ]);

        return Inertia::render('admin/payments/Report', [
            'payments' => $payments,
            'filters' => [
                'status' => in_array($status, PaymentStatus::values(), true) ? $status : null,
                'q' => $search !== '' ? $search : null,
            ],
            'statuses' => collect(PaymentStatus::cases())->map(fn (PaymentStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ])->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'amount' => Money::format($payment->amount_pesewas),
            'momo_reference' => $payment->momo_reference,
            'payer_number' => $payment->payer_number,
            'company' => [
                'id' => $payment->company->id,
                'name' => $payment->company->name,
            ],
            'invoice' => [
                'id' => $payment->invoice->id,
                'number' => $payment->invoice->number,
                'total' => Money::format($payment->invoice->total_pesewas),
                'campaign_reference' => $payment->invoice->smsRequest?->reference,
            ],
            'submitter' => [
                'name' => $payment->submitter->name,
                'email' => $payment->submitter->email,
            ],
            'proof_url' => $payment->hasProof()
                ? URL::temporarySignedRoute(
                    'admin.payments.proof',
                    now()->addMinutes(30),
                    ['payment' => $payment->id],
                )
                : null,
            'created_at' => $payment->created_at?->toIso8601String(),
        ];
    }

    public function verify(Payment $payment, PaymentProvider $provider): RedirectResponse
    {
        $this->authorize('verify', $payment);
        $provider->verify($payment, request()->user());

        return back()->with('success', 'Payment verified. Campaign marked as paid.');
    }

    public function reject(
        RejectPaymentRequest $request,
        Payment $payment,
        PaymentProvider $provider,
    ): RedirectResponse {
        $provider->reject($payment, $request->user(), $request->validated('rejection_reason'));

        return back()->with('success', 'Payment rejected.');
    }

    public function downloadProof(Payment $payment): StreamedResponse
    {
        $this->authorize('downloadProof', $payment);
        abort_unless($payment->hasProof(), 404);
        abort_unless(Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download(
            $payment->proof_path,
            'payment-proof-'.$payment->id.'.'.pathinfo($payment->proof_path, PATHINFO_EXTENSION),
        );
    }
}
