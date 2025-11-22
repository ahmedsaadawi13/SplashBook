<?php
// FILE: /app/models/ClientModel.php

/**
 * ClientModel - Manages client/customer data
 *
 * Handles client CRUD operations and booking history
 */
class ClientModel extends Model
{
    protected $table = 'clients';

    /**
     * Get clients by tenant
     *
     * @param int $tenantId Tenant ID
     * @param string $search Search term (optional)
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Clients list
     */
    public function getClientsByTenant($tenantId, $search = null, $limit = 50, $offset = 0)
    {
        $sql = "SELECT c.*,
                       COUNT(b.id) as total_bookings,
                       SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings
                FROM clients c
                LEFT JOIN bookings b ON c.id = b.client_id
                WHERE c.tenant_id = ?";

        $params = [$tenantId];

        if ($search) {
            $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " GROUP BY c.id ORDER BY c.last_name, c.first_name LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->query($sql, $params);
    }

    /**
     * Get client with statistics
     *
     * @param int $clientId Client ID
     * @return array|null Client data
     */
    public function getClientWithStats($clientId)
    {
        $sql = "SELECT c.*,
                       COUNT(b.id) as total_bookings,
                       SUM(CASE WHEN b.status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                       SUM(CASE WHEN b.status = 'no_show' THEN 1 ELSE 0 END) as no_show_count,
                       MAX(b.booking_date) as last_visit_date
                FROM clients c
                LEFT JOIN bookings b ON c.id = b.client_id
                WHERE c.id = ?
                GROUP BY c.id";

        return $this->db->queryOne($sql, [$clientId]);
    }

    /**
     * Find client by email and tenant
     *
     * @param string $email Email address
     * @param int $tenantId Tenant ID
     * @return array|null Client data
     */
    public function findByEmailAndTenant($email, $tenantId)
    {
        return $this->findOne(['email' => $email, 'tenant_id' => $tenantId]);
    }

    /**
     * Find client by phone and tenant
     *
     * @param string $phone Phone number
     * @param int $tenantId Tenant ID
     * @return array|null Client data
     */
    public function findByPhoneAndTenant($phone, $tenantId)
    {
        return $this->findOne(['phone' => $phone, 'tenant_id' => $tenantId]);
    }

    /**
     * Find or create client
     *
     * @param int $tenantId Tenant ID
     * @param array $clientData Client data (email, phone, first_name, last_name)
     * @return int Client ID
     */
    public function findOrCreate($tenantId, $clientData)
    {
        // Try to find by email first
        if (!empty($clientData['email'])) {
            $client = $this->findByEmailAndTenant($clientData['email'], $tenantId);
            if ($client) {
                return $client['id'];
            }
        }

        // Try to find by phone
        if (!empty($clientData['phone'])) {
            $client = $this->findByPhoneAndTenant($clientData['phone'], $tenantId);
            if ($client) {
                return $client['id'];
            }
        }

        // Create new client
        $clientData['tenant_id'] = $tenantId;
        return $this->insert($clientData);
    }

    /**
     * Count clients for tenant
     *
     * @param int $tenantId Tenant ID
     * @return int Count
     */
    public function countByTenant($tenantId)
    {
        return $this->count(['tenant_id' => $tenantId]);
    }

    /**
     * Get recent clients
     *
     * @param int $tenantId Tenant ID
     * @param int $limit Limit
     * @return array Clients list
     */
    public function getRecentClients($tenantId, $limit = 10)
    {
        return $this->findAll(['tenant_id' => $tenantId], 'created_at DESC', $limit);
    }
}
