<?php
// FILE: /app/models/BookingModel.php

/**
 * BookingModel - Manages appointment bookings
 *
 * Handles booking CRUD operations, availability checks, and scheduling
 */
class BookingModel extends Model
{
    protected $table = 'bookings';

    /**
     * Get bookings by tenant with related data
     *
     * @param int $tenantId Tenant ID
     * @param array $filters Filters (status, date_from, date_to, staff_id, service_id)
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Bookings list
     */
    public function getBookingsByTenant($tenantId, $filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT b.*,
                       s.name as service_name,
                       s.color as service_color,
                       CONCAT(u.first_name, ' ', u.last_name) as staff_name,
                       c.first_name as client_first_name,
                       c.last_name as client_last_name
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                LEFT JOIN staff st ON b.staff_id = st.id
                LEFT JOIN users u ON st.user_id = u.id
                LEFT JOIN clients c ON b.client_id = c.id
                WHERE b.tenant_id = ?";

        $params = [$tenantId];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND b.booking_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND b.booking_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['staff_id'])) {
            $sql .= " AND b.staff_id = ?";
            $params[] = $filters['staff_id'];
        }

        if (!empty($filters['service_id'])) {
            $sql .= " AND b.service_id = ?";
            $params[] = $filters['service_id'];
        }

        $sql .= " ORDER BY b.booking_date DESC, b.start_time DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->query($sql, $params);
    }

    /**
     * Get booking with full details
     *
     * @param int $bookingId Booking ID
     * @return array|null Booking data
     */
    public function getBookingWithDetails($bookingId)
    {
        $sql = "SELECT b.*,
                       s.name as service_name,
                       s.duration_minutes,
                       s.base_price,
                       s.color as service_color,
                       st.id as staff_id,
                       CONCAT(u.first_name, ' ', u.last_name) as staff_name,
                       c.id as client_id,
                       c.first_name as client_first_name,
                       c.last_name as client_last_name,
                       c.email as stored_client_email,
                       c.phone as stored_client_phone
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                LEFT JOIN staff st ON b.staff_id = st.id
                LEFT JOIN users u ON st.user_id = u.id
                LEFT JOIN clients c ON b.client_id = c.id
                WHERE b.id = ?
                LIMIT 1";

        return $this->db->queryOne($sql, [$bookingId]);
    }

    /**
     * Get bookings for a specific date
     *
     * @param int $tenantId Tenant ID
     * @param string $date Date (Y-m-d)
     * @param int $staffId Staff ID (optional)
     * @return array Bookings list
     */
    public function getBookingsByDate($tenantId, $date, $staffId = null)
    {
        $sql = "SELECT b.*,
                       s.name as service_name,
                       s.color as service_color,
                       CONCAT(u.first_name, ' ', u.last_name) as staff_name,
                       COALESCE(c.first_name, b.client_name) as client_display_name
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                LEFT JOIN staff st ON b.staff_id = st.id
                LEFT JOIN users u ON st.user_id = u.id
                LEFT JOIN clients c ON b.client_id = c.id
                WHERE b.tenant_id = ? AND b.booking_date = ? AND b.status != 'canceled'";

        $params = [$tenantId, $date];

        if ($staffId) {
            $sql .= " AND b.staff_id = ?";
            $params[] = $staffId;
        }

        $sql .= " ORDER BY b.start_time";

        return $this->db->query($sql, $params);
    }

    /**
     * Check if a time slot is available
     *
     * @param int $tenantId Tenant ID
     * @param int $staffId Staff ID
     * @param string $date Date (Y-m-d)
     * @param string $startTime Start time (H:i:s)
     * @param string $endTime End time (H:i:s)
     * @param int $exceptBookingId Booking ID to exclude (for updates)
     * @return bool True if available
     */
    public function isTimeSlotAvailable($tenantId, $staffId, $date, $startTime, $endTime, $exceptBookingId = null)
    {
        $sql = "SELECT COUNT(*) as count
                FROM bookings
                WHERE tenant_id = ?
                  AND staff_id = ?
                  AND booking_date = ?
                  AND status NOT IN ('canceled', 'no_show')
                  AND (
                    (start_time < ? AND end_time > ?) OR
                    (start_time >= ? AND start_time < ?) OR
                    (end_time > ? AND end_time <= ?)
                  )";

        $params = [$tenantId, $staffId, $date, $endTime, $startTime, $startTime, $endTime, $startTime, $endTime];

        if ($exceptBookingId) {
            $sql .= " AND id != ?";
            $params[] = $exceptBookingId;
        }

        $result = $this->db->queryOne($sql, $params);
        return $result['count'] == 0;
    }

    /**
     * Generate unique reference number
     *
     * @return string Reference number
     */
    public function generateReferenceNumber()
    {
        do {
            $reference = 'BOOK-' . strtoupper(substr(uniqid(), -6));
        } while ($this->findOne(['reference_number' => $reference]));

        return $reference;
    }

    /**
     * Find booking by reference number
     *
     * @param string $reference Reference number
     * @return array|null Booking data
     */
    public function findByReference($reference)
    {
        return $this->findOne(['reference_number' => $reference]);
    }

    /**
     * Get bookings by client
     *
     * @param int $clientId Client ID
     * @return array Bookings list
     */
    public function getBookingsByClient($clientId)
    {
        $sql = "SELECT b.*,
                       s.name as service_name,
                       CONCAT(u.first_name, ' ', u.last_name) as staff_name
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                LEFT JOIN staff st ON b.staff_id = st.id
                LEFT JOIN users u ON st.user_id = u.id
                WHERE b.client_id = ?
                ORDER BY b.booking_date DESC, b.start_time DESC";

        return $this->db->query($sql, [$clientId]);
    }

    /**
     * Get upcoming bookings
     *
     * @param int $tenantId Tenant ID
     * @param int $limit Limit
     * @return array Bookings list
     */
    public function getUpcomingBookings($tenantId, $limit = 10)
    {
        $sql = "SELECT b.*,
                       s.name as service_name,
                       s.color as service_color,
                       CONCAT(u.first_name, ' ', u.last_name) as staff_name,
                       COALESCE(CONCAT(c.first_name, ' ', c.last_name), b.client_name) as client_display_name
                FROM bookings b
                JOIN services s ON b.service_id = s.id
                LEFT JOIN staff st ON b.staff_id = st.id
                LEFT JOIN users u ON st.user_id = u.id
                LEFT JOIN clients c ON b.client_id = c.id
                WHERE b.tenant_id = ?
                  AND b.booking_date >= CURDATE()
                  AND b.status NOT IN ('canceled', 'completed')
                ORDER BY b.booking_date, b.start_time
                LIMIT ?";

        return $this->db->query($sql, [$tenantId, $limit]);
    }

    /**
     * Get booking statistics for tenant
     *
     * @param int $tenantId Tenant ID
     * @param string $dateFrom Date from (optional)
     * @param string $dateTo Date to (optional)
     * @return array Statistics
     */
    public function getStatistics($tenantId, $dateFrom = null, $dateTo = null)
    {
        $sql = "SELECT
                    COUNT(*) as total_bookings,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled_count,
                    SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show_count
                FROM bookings
                WHERE tenant_id = ?";

        $params = [$tenantId];

        if ($dateFrom) {
            $sql .= " AND booking_date >= ?";
            $params[] = $dateFrom;
        }

        if ($dateTo) {
            $sql .= " AND booking_date <= ?";
            $params[] = $dateTo;
        }

        return $this->db->queryOne($sql, $params);
    }

    /**
     * Count bookings for current month
     *
     * @param int $tenantId Tenant ID
     * @return int Count
     */
    public function countCurrentMonth($tenantId)
    {
        $sql = "SELECT COUNT(*) as count
                FROM bookings
                WHERE tenant_id = ?
                  AND booking_date >= DATE_FORMAT(NOW(), '%Y-%m-01')
                  AND booking_date <= LAST_DAY(NOW())";

        $result = $this->db->queryOne($sql, [$tenantId]);
        return (int) $result['count'];
    }
}
