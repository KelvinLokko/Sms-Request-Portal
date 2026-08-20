<?php

namespace App\Services;

use App\Models\Invoice;
use App\Support\PrivateStorage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

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
     * Return the PDF binary, regenerating and caching when needed.
     */
    public function contents(Invoice $invoice): string
    {
        if ($invoice->hasPdf() && PrivateStorage::disk()->exists($invoice->pdf_path)) {
            $bytes = PrivateStorage::disk()->get($invoice->pdf_path);

            if (is_string($bytes) && $bytes !== '') {
                return $bytes;
            }
        }

        $bytes = $this->render($invoice);

        try {
            $this->store($invoice, $bytes);
        } catch (\Throwable $e) {
            Log::error('invoice.pdf.store_failed', [
                'invoice_id' => $invoice->id,
                'disk' => PrivateStorage::name(),
                'message' => $e->getMessage(),
            ]);
        }

        return $bytes;
    }

    /**
     * Render and store the invoice PDF, returning the private storage path.
     */
    public function generate(Invoice $invoice): string
    {
        $bytes = $this->render($invoice);

        return $this->store($invoice, $bytes);
    }

    /**
     * Render the invoice PDF to a binary string.
     */
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing([
            'company',
            'items',
            'smsRequest.senderId',
            'issuer',
        ]);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'brand' => config('marketing.brand'),
            'contact' => config('marketing.contact'),
        ])->setPaper('a4')->output();
    }

    /**
     * Persist PDF bytes and return the stored path.
     */
    private function store(Invoice $invoice, string $bytes): string
    {
        $path = sprintf(
            'invoices/%d/%s.pdf',
            $invoice->company_id,
            Str::slug($invoice->number),
        );

        $written = PrivateStorage::disk()->put($path, $bytes);

        if ($written !== true && ! PrivateStorage::disk()->exists($path)) {
            Log::error('invoice.pdf.store_failed', [
                'invoice_id' => $invoice->id,
                'path' => $path,
                'disk' => PrivateStorage::name(),
            ]);

            throw new RuntimeException('Unable to store the invoice PDF.');
        }

        $invoice->forceFill(['pdf_path' => $path])->save();

        return $path;
    }
}
