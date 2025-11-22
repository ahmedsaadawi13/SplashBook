<?php
// FILE: /app/models/InvoiceModel.php

/**
 * InvoiceModel - Manages billing invoices
 *
 * Handles invoice CRUD operations and payment tracking
 */
class InvoiceModel extends Model
{
    protected $table = 'invoices';

    /**
     * Get invoices by tenant
     *
     * @param int $tenantId Tenant ID
     * @param int $limit Limit
     * @return array Invoices list
     */
    public function getInvoicesByTenant($tenantId, $limit = 50)
    {
        $sql = "SELECT i.*, p.name as plan_name
                FROM invoices i
                JOIN tenant_subscriptions ts ON i.subscription_id = ts.id
                JOIN plans p ON ts.plan_id = p.id
                WHERE i.tenant_id = ?
                ORDER BY i.issued_at DESC
                LIMIT ?";

        return $this->db->query($sql, [$tenantId, $limit]);
    }

    /**
     * Get invoice with details
     *
     * @param int $invoiceId Invoice ID
     * @return array|null Invoice data
     */
    public function getInvoiceWithDetails($invoiceId)
    {
        $sql = "SELECT i.*,
                       t.business_name,
                       t.address,
                       t.city,
                       t.state,
                       t.postal_code,
                       p.name as plan_name
                FROM invoices i
                JOIN tenants t ON i.tenant_id = t.id
                JOIN tenant_subscriptions ts ON i.subscription_id = ts.id
                JOIN plans p ON ts.plan_id = p.id
                WHERE i.id = ?";

        return $this->db->queryOne($sql, [$invoiceId]);
    }

    /**
     * Generate unique invoice number
     *
     * @return string Invoice number
     */
    public function generateInvoiceNumber()
    {
        $year = date('Y');
        $sql = "SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1";
        $result = $this->db->queryOne($sql, ["INV-{$year}-%"]);

        if ($result) {
            preg_match('/INV-\d+-(\d+)/', $result['invoice_number'], $matches);
            $number = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
        } else {
            $number = 1;
        }

        return sprintf('INV-%s-%03d', $year, $number);
    }

    /**
     * Get unpaid invoices for tenant
     *
     * @param int $tenantId Tenant ID
     * @return array Invoices list
     */
    public function getUnpaidInvoices($tenantId)
    {
        return $this->findAll([
            'tenant_id' => $tenantId,
            'status' => 'pending'
        ], 'due_at ASC');
    }

    /**
     * Get overdue invoices for tenant
     *
     * @param int $tenantId Tenant ID
     * @return array Invoices list
     */
    public function getOverdueInvoices($tenantId)
    {
        $sql = "SELECT * FROM invoices
                WHERE tenant_id = ?
                  AND status IN ('pending', 'overdue')
                  AND due_at < NOW()
                ORDER BY due_at ASC";

        return $this->db->query($sql, [$tenantId]);
    }
}
