<?php
// FILE: /app/controllers/InvoiceController.php

class InvoiceController extends Controller
{
    private $invoiceModel;
    private $paymentModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['tenant_admin']);
        $this->invoiceModel = new InvoiceModel();
        $this->paymentModel = new PaymentModel();
    }

    public function index()
    {
        $invoices = $this->invoiceModel->getInvoicesByTenant($this->getTenantId());
        $this->render('invoices/index', ['invoices' => $invoices]);
    }

    public function show($id)
    {
        $invoice = $this->invoiceModel->getInvoiceWithDetails($id);

        if (!$invoice || $invoice['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Invoice not found');
            $this->redirect('/invoices');
        }

        $payments = $this->paymentModel->getPaymentsByInvoice($id);

        $this->render('invoices/show', compact('invoice', 'payments'));
    }

    public function pay($id)
    {
        $this->requireCsrf();
        $invoice = $this->invoiceModel->findById($id);

        if (!$invoice || $invoice['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Invoice not found');
            $this->redirect('/invoices');
        }

        if ($invoice['status'] === 'paid') {
            $this->setFlash('warning', 'Invoice already paid');
            $this->redirect('/invoices/' . $id);
        }

        try {
            $result = $this->paymentModel->processPayment(
                $this->getTenantId(),
                $id,
                $invoice['amount'],
                'credit_card'
            );

            if ($result['success']) {
                $this->setFlash('success', 'Payment processed successfully');
            } else {
                $this->setFlash('error', $result['message']);
            }

        } catch (Exception $e) {
            error_log('Payment failed: ' . $e->getMessage());
            $this->setFlash('error', 'Payment failed');
        }

        $this->redirect('/invoices/' . $id);
    }
}
