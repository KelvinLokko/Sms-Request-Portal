<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $invoiceId)
    {
        $this->onQueue('pdf');
    }

    public function handle(InvoicePdfService $pdfs): void
    {
        $invoice = Invoice::query()->find($this->invoiceId);

        if ($invoice === null) {
            return;
        }

        $pdfs->generate($invoice);
    }
}
