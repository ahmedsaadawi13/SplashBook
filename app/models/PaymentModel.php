<?php
// FILE: /app/models/PaymentModel.php

/**
 * PaymentModel - Manages payment transactions
 *
 * Handles payment CRUD operations and transaction tracking
 */
class PaymentModel extends Model
{
    protected $table = 'payments';

    /**
     * Get payments by tenant
     *
     * @param int $tenantId Tenant ID
     * @param int $limit Limit
     * @return array Payments list
     */
    public function getPaymentsByTenant($tenantId, $limit = 50)
    {
        $sql = "SELECT p.*, i.invoice_number
                FROM payments p
                JOIN invoices i ON p.invoice_id = i.id
                WHERE p.tenant_id = ?
                ORDER BY p.created_at DESC
                LIMIT ?";

        return $this->db->query($sql, [$tenantId, $limit]);
    }

    /**
     * Get payments for an invoice
     *
     * @param int $invoiceId Invoice ID
     * @return array Payments list
     */
    public function getPaymentsByInvoice($invoiceId)
    {
        return $this->findAll(['invoice_id' => $invoiceId], 'created_at DESC');
    }

    /**
     * Generate transaction ID
     *
     * @return string Transaction ID
     */
    public function generateTransactionId()
    {
        return 'txn_sim_' . uniqid() . '_' . time();
    }

    /**
     * Process a payment (simulated)
     *
     * @param int $tenantId Tenant ID
     * @param int $invoiceId Invoice ID
     * @param float $amount Amount
     * @param string $method Payment method
     * @return array Result with success status and payment ID
     */
    public function processPayment($tenantId, $invoiceId, $amount, $method = 'credit_card')
    {
        try {
            $this->db->beginTransaction();

            // Create payment record
            $paymentId = $this->insert([
                'tenant_id' => $tenantId,
                'invoice_id' => $invoiceId,
                'payment_method' => $method,
                'amount' => $amount,
                'transaction_id' => $this->generateTransactionId(),
                'status' => 'completed',
                'paid_at' => date('Y-m-d H:i:s')
            ]);

            // Update invoice status
            $invoiceModel = new InvoiceModel();
            $invoiceModel->update($invoiceId, [
                'status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();

            return ['success' => true, 'payment_id' => $paymentId];
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Payment processing failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Payment processing failed'];
        }
    }
}
