<?php
// FILE: /app/models/SubscriptionModel.php

/**
 * SubscriptionModel - Manages tenant subscriptions
 *
 * Handles subscription operations, status management, and usage limits
 */
class SubscriptionModel extends Model
{
    protected $table = 'tenant_subscriptions';

    /**
     * Get active subscription for tenant
     *
     * @param int $tenantId Tenant ID
     * @return array|null Subscription data
     */
    public function getActiveSubscription($tenantId)
    {
        $sql = "SELECT ts.*, p.name as plan_name, p.max_staff, p.max_services, p.max_bookings_per_month,
                       p.price_monthly, p.price_yearly
                FROM tenant_subscriptions ts
                JOIN plans p ON ts.plan_id = p.id
                WHERE ts.tenant_id = ?
                  AND ts.status IN ('active', 'trialing', 'past_due')
                ORDER BY ts.id DESC
                LIMIT 1";

        return $this->db->queryOne($sql, [$tenantId]);
    }

    /**
     * Check if tenant can add more staff
     *
     * @param int $tenantId Tenant ID
     * @return array Result with 'allowed' boolean and 'message'
     */
    public function canAddStaff($tenantId)
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            return ['allowed' => false, 'message' => 'No active subscription'];
        }

        $staffModel = new StaffModel();
        $currentCount = $staffModel->countByTenant($tenantId);

        if ($currentCount >= $subscription['max_staff']) {
            return [
                'allowed' => false,
                'message' => "You have reached the maximum number of staff members ({$subscription['max_staff']}) for your {$subscription['plan_name']} plan. Please upgrade to add more staff."
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Check if tenant can add more services
     *
     * @param int $tenantId Tenant ID
     * @return array Result with 'allowed' boolean and 'message'
     */
    public function canAddService($tenantId)
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            return ['allowed' => false, 'message' => 'No active subscription'];
        }

        $serviceModel = new ServiceModel();
        $currentCount = $serviceModel->countByTenant($tenantId);

        if ($currentCount >= $subscription['max_services']) {
            return [
                'allowed' => false,
                'message' => "You have reached the maximum number of services ({$subscription['max_services']}) for your {$subscription['plan_name']} plan. Please upgrade to add more services."
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Check if tenant can create more bookings this month
     *
     * @param int $tenantId Tenant ID
     * @return array Result with 'allowed' boolean and 'message'
     */
    public function canCreateBooking($tenantId)
    {
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            return ['allowed' => false, 'message' => 'No active subscription'];
        }

        $bookingModel = new BookingModel();
        $currentCount = $bookingModel->countCurrentMonth($tenantId);

        if ($currentCount >= $subscription['max_bookings_per_month']) {
            return [
                'allowed' => false,
                'message' => "You have reached the maximum number of bookings ({$subscription['max_bookings_per_month']}) for this month. Please upgrade your plan."
            ];
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Get subscription history for tenant
     *
     * @param int $tenantId Tenant ID
     * @return array Subscriptions list
     */
    public function getSubscriptionHistory($tenantId)
    {
        $sql = "SELECT ts.*, p.name as plan_name
                FROM tenant_subscriptions ts
                JOIN plans p ON ts.plan_id = p.id
                WHERE ts.tenant_id = ?
                ORDER BY ts.created_at DESC";

        return $this->db->query($sql, [$tenantId]);
    }
}
