<?php
// FILE: /app/models/AvailabilityModel.php

/**
 * AvailabilityModel - Calculates staff availability for bookings
 *
 * Handles complex availability logic including working hours,
 * breaks, existing bookings, and holidays
 */
class AvailabilityModel
{
    private $db;
    private $staffModel;
    private $bookingModel;
    private $holidayModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->staffModel = new StaffModel();
        $this->bookingModel = new BookingModel();
        $this->holidayModel = new HolidayModel();
    }

    /**
     * Get available time slots for a staff member on a specific date
     *
     * @param int $tenantId Tenant ID
     * @param int $staffId Staff ID
     * @param int $serviceId Service ID (for duration)
     * @param string $date Date (Y-m-d)
     * @return array Available time slots
     */
    public function getAvailableSlots($tenantId, $staffId, $serviceId, $date)
    {
        // Check if date is a holiday
        if ($this->holidayModel->isHoliday($tenantId, $date)) {
            return [];
        }

        // Get service duration
        $serviceModel = new ServiceModel();
        $service = $serviceModel->findById($serviceId);
        if (!$service) {
            return [];
        }

        $durationMinutes = $service['duration_minutes'];

        // Get day of week (0=Sunday, 6=Saturday)
        $dayOfWeek = TimeHelper::getDayOfWeek($date);

        // Get staff working hours for this day
        $workingHours = $this->getWorkingHoursForDay($staffId, $dayOfWeek);
        if (empty($workingHours)) {
            return [];
        }

        // Get staff breaks for this day
        $breaks = $this->getBreaksForDay($staffId, $dayOfWeek);

        // Get existing bookings for this day
        $existingBookings = $this->bookingModel->getBookingsByDate($tenantId, $date, $staffId);

        // Generate all possible slots
        $slots = [];
        foreach ($workingHours as $hours) {
            $slots = array_merge($slots, $this->generateSlots(
                $hours['start_time'],
                $hours['end_time'],
                $durationMinutes,
                $breaks,
                $existingBookings
            ));
        }

        return $slots;
    }

    /**
     * Get working hours for a specific day
     *
     * @param int $staffId Staff ID
     * @param int $dayOfWeek Day of week (0-6)
     * @return array Working hours
     */
    private function getWorkingHoursForDay($staffId, $dayOfWeek)
    {
        $sql = "SELECT * FROM staff_working_hours
                WHERE staff_id = ? AND day_of_week = ? AND is_active = 1
                ORDER BY start_time";

        return $this->db->query($sql, [$staffId, $dayOfWeek]);
    }

    /**
     * Get breaks for a specific day
     *
     * @param int $staffId Staff ID
     * @param int $dayOfWeek Day of week (0-6)
     * @return array Breaks
     */
    private function getBreaksForDay($staffId, $dayOfWeek)
    {
        $sql = "SELECT * FROM staff_breaks
                WHERE staff_id = ? AND day_of_week = ? AND is_active = 1
                ORDER BY start_time";

        return $this->db->query($sql, [$staffId, $dayOfWeek]);
    }

    /**
     * Generate time slots within working hours, excluding breaks and bookings
     *
     * @param string $startTime Start time (H:i:s)
     * @param string $endTime End time (H:i:s)
     * @param int $durationMinutes Duration in minutes
     * @param array $breaks Break periods
     * @param array $existingBookings Existing bookings
     * @return array Available slots
     */
    private function generateSlots($startTime, $endTime, $durationMinutes, $breaks, $existingBookings)
    {
        $slots = [];
        $current = new DateTime($startTime);
        $end = new DateTime($endTime);

        while ($current < $end) {
            $slotStart = $current->format('H:i:s');
            $slotEnd = (clone $current)->modify("+{$durationMinutes} minutes")->format('H:i:s');

            // Check if slot end doesn't exceed working hours
            if ($slotEnd > $endTime) {
                break;
            }

            // Check if slot doesn't overlap with breaks
            if (!$this->overlapsBreak($slotStart, $slotEnd, $breaks)) {
                // Check if slot doesn't overlap with existing bookings
                if (!$this->overlapsBooking($slotStart, $slotEnd, $existingBookings)) {
                    $slots[] = [
                        'start_time' => $slotStart,
                        'end_time' => $slotEnd,
                        'display_time' => TimeHelper::formatTime($slotStart)
                    ];
                }
            }

            // Move to next slot (15-minute intervals for flexibility)
            $current->modify('+15 minutes');
        }

        return $slots;
    }

    /**
     * Check if time slot overlaps with a break
     *
     * @param string $slotStart Slot start time
     * @param string $slotEnd Slot end time
     * @param array $breaks Break periods
     * @return bool True if overlaps
     */
    private function overlapsBreak($slotStart, $slotEnd, $breaks)
    {
        foreach ($breaks as $break) {
            if ($slotStart < $break['end_time'] && $slotEnd > $break['start_time']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if time slot overlaps with an existing booking
     *
     * @param string $slotStart Slot start time
     * @param string $slotEnd Slot end time
     * @param array $bookings Existing bookings
     * @return bool True if overlaps
     */
    private function overlapsBooking($slotStart, $slotEnd, $bookings)
    {
        foreach ($bookings as $booking) {
            if ($slotStart < $booking['end_time'] && $slotEnd > $booking['start_time']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get available slots for any staff member who can provide a service
     *
     * @param int $tenantId Tenant ID
     * @param int $serviceId Service ID
     * @param string $date Date (Y-m-d)
     * @return array Available slots grouped by staff
     */
    public function getAvailableSlotsForService($tenantId, $serviceId, $date)
    {
        $staffMembers = $this->staffModel->getStaffForService($serviceId, $tenantId);

        $result = [];
        foreach ($staffMembers as $staff) {
            $slots = $this->getAvailableSlots($tenantId, $staff['id'], $serviceId, $date);
            if (!empty($slots)) {
                $result[] = [
                    'staff_id' => $staff['id'],
                    'staff_name' => $staff['first_name'] . ' ' . $staff['last_name'],
                    'slots' => $slots
                ];
            }
        }

        return $result;
    }
}
