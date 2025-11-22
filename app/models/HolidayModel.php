<?php
// FILE: /app/models/HolidayModel.php

/**
 * HolidayModel - Manages holidays and blackout dates
 *
 * Handles holiday CRUD operations and availability checking
 */
class HolidayModel extends Model
{
    protected $table = 'holidays';

    /**
     * Get holidays by tenant
     *
     * @param int $tenantId Tenant ID
     * @return array Holidays list
     */
    public function getHolidaysByTenant($tenantId)
    {
        return $this->findAll(['tenant_id' => $tenantId], 'date ASC');
    }

    /**
     * Check if a date is a holiday
     *
     * @param int $tenantId Tenant ID
     * @param string $date Date (Y-m-d)
     * @return bool True if holiday
     */
    public function isHoliday($tenantId, $date)
    {
        $sql = "SELECT COUNT(*) as count
                FROM holidays
                WHERE tenant_id = ?
                  AND (
                    date = ? OR
                    (is_recurring = 1 AND DATE_FORMAT(date, '%m-%d') = DATE_FORMAT(?, '%m-%d'))
                  )";

        $result = $this->db->queryOne($sql, [$tenantId, $date, $date]);
        return $result['count'] > 0;
    }

    /**
     * Get upcoming holidays
     *
     * @param int $tenantId Tenant ID
     * @param int $limit Limit
     * @return array Holidays list
     */
    public function getUpcomingHolidays($tenantId, $limit = 10)
    {
        $sql = "SELECT * FROM holidays
                WHERE tenant_id = ?
                  AND date >= CURDATE()
                ORDER BY date ASC
                LIMIT ?";

        return $this->db->query($sql, [$tenantId, $limit]);
    }
}
