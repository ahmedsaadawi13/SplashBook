<?php
// FILE: /app/models/TenantModel.php

/**
 * TenantModel - Manages tenant (business) data
 *
 * Handles CRUD operations for tenants/businesses in the system
 */
class TenantModel extends Model
{
    protected $table = 'tenants';

    /**
     * Find tenant by slug
     *
     * @param string $slug Tenant slug
     * @return array|null Tenant data
     */
    public function findBySlug($slug)
    {
        return $this->findOne(['slug' => $slug]);
    }

    /**
     * Find tenant by API key
     *
     * @param string $apiKey API key
     * @return array|null Tenant data
     */
    public function findByApiKey($apiKey)
    {
        return $this->findOne(['api_key' => $apiKey]);
    }

    /**
     * Get active tenants
     *
     * @return array Tenants list
     */
    public function getActiveTenants()
    {
        return $this->findAll(['status' => 'active'], 'business_name ASC');
    }

    /**
     * Generate unique slug from business name
     *
     * @param string $businessName Business name
     * @return string Unique slug
     */
    public function generateSlug($businessName)
    {
        $slug = strtolower(trim($businessName));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');

        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;

        while ($this->findBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Generate API key
     *
     * @return string Unique API key
     */
    public function generateApiKey()
    {
        do {
            $apiKey = bin2hex(random_bytes(32));
        } while ($this->findByApiKey($apiKey));

        return $apiKey;
    }

    /**
     * Get tenant with current subscription
     *
     * @param int $tenantId Tenant ID
     * @return array|null Tenant with subscription data
     */
    public function getTenantWithSubscription($tenantId)
    {
        $sql = "SELECT t.*,
                       ts.id as subscription_id,
                       ts.status as subscription_status,
                       ts.current_period_end,
                       p.name as plan_name,
                       p.max_staff,
                       p.max_services,
                       p.max_bookings_per_month
                FROM tenants t
                LEFT JOIN tenant_subscriptions ts ON t.id = ts.tenant_id AND ts.status IN ('active', 'trialing', 'past_due')
                LEFT JOIN plans p ON ts.plan_id = p.id
                WHERE t.id = ?
                LIMIT 1";

        return $this->db->queryOne($sql, [$tenantId]);
    }
}
