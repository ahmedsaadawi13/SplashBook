<?php
// FILE: /app/models/StaffModel.php

/**
 * StaffModel - Manages staff members
 *
 * Handles staff CRUD operations, schedules, and service assignments
 */
class StaffModel extends Model
{
    protected $table = 'staff';

    /**
     * Get staff by tenant
     *
     * @param int $tenantId Tenant ID
     * @param bool $activeOnly Only active staff
     * @return array Staff list
     */
    public function getStaffByTenant($tenantId, $activeOnly = true)
    {
        $conditions = ['tenant_id' => $tenantId];
        if ($activeOnly) {
            $conditions['is_active'] = 1;
        }

        $sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone
                FROM staff s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.tenant_id = ?" . ($activeOnly ? " AND s.is_active = 1" : "") . "
                ORDER BY u.first_name, u.last_name";

        return $this->db->query($sql, [$tenantId]);
    }

    /**
     * Get staff with user details
     *
     * @param int $staffId Staff ID
     * @return array|null Staff data
     */
    public function getStaffWithUser($staffId)
    {
        $sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.timezone
                FROM staff s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = ?
                LIMIT 1";

        return $this->db->queryOne($sql, [$staffId]);
    }

    /**
     * Get staff working hours
     *
     * @param int $staffId Staff ID
     * @return array Working hours by day
     */
    public function getWorkingHours($staffId)
    {
        $sql = "SELECT * FROM staff_working_hours
                WHERE staff_id = ? AND is_active = 1
                ORDER BY day_of_week, start_time";

        return $this->db->query($sql, [$staffId]);
    }

    /**
     * Get staff breaks
     *
     * @param int $staffId Staff ID
     * @return array Breaks by day
     */
    public function getBreaks($staffId)
    {
        $sql = "SELECT * FROM staff_breaks
                WHERE staff_id = ? AND is_active = 1
                ORDER BY day_of_week, start_time";

        return $this->db->query($sql, [$staffId]);
    }

    /**
     * Get services assigned to staff
     *
     * @param int $staffId Staff ID
     * @return array Services list
     */
    public function getStaffServices($staffId)
    {
        $sql = "SELECT s.*, sc.name as category_name
                FROM staff_services ss
                JOIN services s ON ss.service_id = s.id
                LEFT JOIN service_categories sc ON s.category_id = sc.id
                WHERE ss.staff_id = ? AND s.is_active = 1
                ORDER BY sc.display_order, s.name";

        return $this->db->query($sql, [$staffId]);
    }

    /**
     * Update staff services
     *
     * @param int $staffId Staff ID
     * @param array $serviceIds Array of service IDs
     * @return bool Success
     */
    public function updateStaffServices($staffId, $serviceIds)
    {
        try {
            // Delete existing assignments
            $this->db->execute("DELETE FROM staff_services WHERE staff_id = ?", [$staffId]);

            // Insert new assignments
            if (!empty($serviceIds)) {
                $sql = "INSERT INTO staff_services (staff_id, service_id) VALUES (?, ?)";
                foreach ($serviceIds as $serviceId) {
                    $this->db->execute($sql, [$staffId, $serviceId]);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('Failed to update staff services: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update staff working hours
     *
     * @param int $staffId Staff ID
     * @param array $schedule Schedule data
     * @return bool Success
     */
    public function updateWorkingHours($staffId, $schedule)
    {
        try {
            // Delete existing hours
            $this->db->execute("DELETE FROM staff_working_hours WHERE staff_id = ?", [$staffId]);

            // Insert new hours
            $sql = "INSERT INTO staff_working_hours (staff_id, day_of_week, start_time, end_time)
                    VALUES (?, ?, ?, ?)";

            foreach ($schedule as $day => $hours) {
                if (!empty($hours['enabled']) && !empty($hours['start']) && !empty($hours['end'])) {
                    $this->db->execute($sql, [$staffId, $day, $hours['start'], $hours['end']]);
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('Failed to update working hours: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get staff members who can provide a specific service
     *
     * @param int $serviceId Service ID
     * @param int $tenantId Tenant ID
     * @return array Staff list
     */
    public function getStaffForService($serviceId, $tenantId)
    {
        $sql = "SELECT DISTINCT s.*, u.first_name, u.last_name
                FROM staff s
                JOIN staff_services ss ON s.id = ss.staff_id
                LEFT JOIN users u ON s.user_id = u.id
                WHERE ss.service_id = ?
                  AND s.tenant_id = ?
                  AND s.is_active = 1
                  AND s.accepts_bookings = 1
                ORDER BY u.first_name, u.last_name";

        return $this->db->query($sql, [$serviceId, $tenantId]);
    }

    /**
     * Count staff for tenant
     *
     * @param int $tenantId Tenant ID
     * @return int Count
     */
    public function countByTenant($tenantId)
    {
        return $this->count(['tenant_id' => $tenantId, 'is_active' => 1]);
    }
}
