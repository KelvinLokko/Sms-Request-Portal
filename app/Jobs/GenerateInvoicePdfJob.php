<?php

namespace App\Jobs;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceId)
    {
        $this->onQueue('pdf');
    }

    public function handle(): void
    {
        $invoice = Invoice::query()
            ->with(['company', 'items', 'smsRequest.senderId', 'issuer'])
            ->find($this->invoiceId);

        if ($invoice === null) {
            return;
        }

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'brand' => config('marketing.brand'),
            'contact' => config('marketing.contact'),
        ]);

        $path = sprintf(
            'invoices/%d/%s.pdf',
            $invoice->company_id,
            Str::slug($invoice->number),
        );

        Storage::disk('local')->put($path, $pdf->output());

        $invoice->forceFill(['pdf_path' => $path])->save();
    }
}
