<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\PrivateStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class InvoicePdfService
{
    /**
     * Ensure a professional PDF exists for the invoice and return its storage path.
     */
    public function ensure(Invoice $invoice): string
    {
        if ($invoice->hasPdf() && PrivateStorage::disk()->exists($invoice->pdf_path)) {
            return $invoice->pdf_path;
        }

        return $this->generate($invoice);
    }

    /**
     * Render and store the invoice PDF, returning the private storage path.
     */
    public function generate(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'company',
            'items',
            'smsRequest.senderId',
            'issuer',
        ]);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'brand' => config('marketing.brand'),
            'contact' => config('marketing.contact'),
        ])->setPaper('a4');

        $path = sprintf(
            'invoices/%d/%s.pdf',
            $invoice->company_id,
            Str::slug($invoice->number),
        );

        PrivateStorage::disk()->put($path, $pdf->output());

        $invoice->forceFill(['pdf_path' => $path])->save();

        return $path;
    }
}
