<?php
// FILE: /app/models/ServiceCategoryModel.php

/**
 * ServiceCategoryModel - Manages service categories
 *
 * Handles category CRUD operations and ordering
 */
class ServiceCategoryModel extends Model
{
    protected $table = 'service_categories';

    /**
     * Get categories by tenant
     *
     * @param int $tenantId Tenant ID
     * @param bool $activeOnly Only active categories
     * @return array Categories list
     */
    public function getCategoriesByTenant($tenantId, $activeOnly = true)
    {
        $conditions = ['tenant_id' => $tenantId];
        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        return $this->findAll($conditions, 'display_order, name');
    }

    /**
     * Get category with service count
     *
     * @param int $categoryId Category ID
     * @return array|null Category data
     */
    public function getCategoryWithServiceCount($categoryId)
    {
        $sql = "SELECT sc.*, COUNT(s.id) as service_count
                FROM service_categories sc
                LEFT JOIN services s ON sc.id = s.category_id AND s.is_active = 1
                WHERE sc.id = ?
                GROUP BY sc.id";

        return $this->db->queryOne($sql, [$categoryId]);
    }

    /**
     * Get all categories with service counts
     *
     * @param int $tenantId Tenant ID
     * @return array Categories with counts
     */
    public function getCategoriesWithCounts($tenantId)
    {
        $sql = "SELECT sc.*, COUNT(s.id) as service_count
                FROM service_categories sc
                LEFT JOIN services s ON sc.id = s.category_id AND s.is_active = 1
                WHERE sc.tenant_id = ?
                GROUP BY sc.id
                ORDER BY sc.display_order, sc.name";

        return $this->db->query($sql, [$tenantId]);
    }

    /**
     * Get next display order for new category
     *
     * @param int $tenantId Tenant ID
     * @return int Next order number
     */
    public function getNextDisplayOrder($tenantId)
    {
        $sql = "SELECT MAX(display_order) as max_order FROM service_categories WHERE tenant_id = ?";
        $result = $this->db->queryOne($sql, [$tenantId]);
        return ($result['max_order'] ?? 0) + 1;
    }
}
