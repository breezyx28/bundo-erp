<?php

namespace App\Http\Controllers;

use App\Models\SalesInvoice;
use App\Models\Tenant;
use App\Support\InvoiceDesign;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class InvoiceDocumentController
{
    /** Printable HTML view (auto-triggers the browser print dialog). */
    public function print(SalesInvoice $invoice): View
    {
        return view(InvoiceDesign::currentView(), $this->data($invoice, print: true));
    }

    /** Streamed PDF download. */
    public function pdf(SalesInvoice $invoice): Response
    {
        $pdf = Pdf::loadView(InvoiceDesign::currentView(), $this->data($invoice))->setPaper('a4');

        return $pdf->download($invoice->invoice_number.'.pdf');
    }

    /** @return array{invoice: SalesInvoice, tenant: ?Tenant, print?: bool} */
    protected function data(SalesInvoice $invoice, bool $print = false): array
    {
        $invoice->load(['branch', 'customer', 'items.product:id,name', 'items.variant']);

        return [
            'invoice' => $invoice,
            'tenant' => Tenant::find($invoice->tenant_id),
            'print' => $print,
        ];
    }
}
