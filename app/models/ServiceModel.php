<?php
// FILE: /app/models/ServiceModel.php

/**
 * ServiceModel - Manages services offered by businesses
 *
 * Handles service CRUD operations and category relationships
 */
class ServiceModel extends Model
{
    protected $table = 'services';

    /**
     * Get services by tenant
     *
     * @param int $tenantId Tenant ID
     * @param bool $activeOnly Only active services
     * @return array Services list
     */
    public function getServicesByTenant($tenantId, $activeOnly = true)
    {
        $conditions = ['tenant_id' => $tenantId];
        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        $sql = "SELECT s.*, sc.name as category_name
                FROM services s
                LEFT JOIN service_categories sc ON s.category_id = sc.id
                WHERE s.tenant_id = ?" . ($activeOnly ? " AND s.is_active = 1" : "") . "
                ORDER BY sc.display_order, sc.name, s.name";

        return $this->db->query($sql, [$tenantId]);
    }

    /**
     * Get service with category
     *
     * @param int $serviceId Service ID
     * @return array|null Service data
     */
    public function getServiceWithCategory($serviceId)
    {
        $sql = "SELECT s.*, sc.name as category_name
                FROM services s
                LEFT JOIN service_categories sc ON s.category_id = sc.id
                WHERE s.id = ?
                LIMIT 1";

        return $this->db->queryOne($sql, [$serviceId]);
    }

    /**
     * Get services by category
     *
     * @param int $categoryId Category ID
     * @param int $tenantId Tenant ID
     * @return array Services list
     */
    public function getServicesByCategory($categoryId, $tenantId)
    {
        return $this->findAll([
            'category_id' => $categoryId,
            'tenant_id' => $tenantId,
            'is_active' => 1
        ], 'name');
    }

    /**
     * Get services grouped by category
     *
     * @param int $tenantId Tenant ID
     * @return array Services grouped by category
     */
    public function getServicesGroupedByCategory($tenantId)
    {
        $sql = "SELECT s.*, sc.name as category_name, sc.id as category_id
                FROM services s
                LEFT JOIN service_categories sc ON s.category_id = sc.id
                WHERE s.tenant_id = ? AND s.is_active = 1
                ORDER BY sc.display_order, sc.name, s.name";

        $services = $this->db->query($sql, [$tenantId]);

        // Group by category
        $grouped = [];
        foreach ($services as $service) {
            $categoryName = $service['category_name'] ?: 'Uncategorized';
            if (!isset($grouped[$categoryName])) {
                $grouped[$categoryName] = [];
            }
            $grouped[$categoryName][] = $service;
        }

        return $grouped;
    }

    /**
     * Count services for tenant
     *
     * @param int $tenantId Tenant ID
     * @return int Count
     */
    public function countByTenant($tenantId)
    {
        return $this->count(['tenant_id' => $tenantId, 'is_active' => 1]);
    }
}
