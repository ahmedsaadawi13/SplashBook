<?php
// FILE: /app/helpers/EmailHelper.php

/**
 * EmailHelper - Simulated email sending
 *
 * Logs emails to database instead of sending them.
 * Can be extended to use real SMTP in the future.
 */
class EmailHelper
{
    private $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Send (log) an email
     *
     * @param int $tenantId Tenant ID (null for platform emails)
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string $type Email type
     * @param int $bookingId Related booking ID (optional)
     * @return bool Success status
     */
    public function send($tenantId, $to, $subject, $body, $type = null, $bookingId = null)
    {
        try {
            $sql = "INSERT INTO email_logs (tenant_id, to_email, subject, body, type, related_booking_id)
                    VALUES (?, ?, ?, ?, ?, ?)";

            $this->db->execute($sql, [
                $tenantId,
                $to,
                $subject,
                $body,
                $type,
                $bookingId
            ]);

            return true;
        } catch (Exception $e) {
            error_log('Failed to log email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send booking confirmation email
     *
     * @param int $tenantId Tenant ID
     * @param array $booking Booking data
     * @param array $tenant Tenant data
     * @return bool Success status
     */
    public function sendBookingConfirmation($tenantId, $booking, $tenant)
    {
        $to = $booking['client_email'];
        $subject = 'Booking Confirmation - ' . $booking['service_name'];

        $body = "Dear {$booking['client_name']},\n\n";
        $body .= "Your appointment has been confirmed!\n\n";
        $body .= "Service: {$booking['service_name']}\n";
        $body .= "Date: " . TimeHelper::formatDate($booking['booking_date']) . "\n";
        $body .= "Time: " . TimeHelper::formatTime($booking['start_time']) . "\n";

        if (!empty($booking['staff_name'])) {
            $body .= "Staff: {$booking['staff_name']}\n";
        }

        $body .= "\nReference Number: {$booking['reference_number']}\n\n";
        $body .= "Location:\n{$tenant['business_name']}\n";
        if (!empty($tenant['address'])) {
            $body .= "{$tenant['address']}\n";
            $body .= "{$tenant['city']}, {$tenant['state']} {$tenant['postal_code']}\n";
        }
        if (!empty($tenant['phone'])) {
            $body .= "Phone: {$tenant['phone']}\n";
        }

        $body .= "\nThank you for choosing {$tenant['business_name']}!\n";

        return $this->send($tenantId, $to, $subject, $body, 'booking_confirmation', $booking['id']);
    }

    /**
     * Send booking reminder email
     *
     * @param int $tenantId Tenant ID
     * @param array $booking Booking data
     * @param array $tenant Tenant data
     * @return bool Success status
     */
    public function sendBookingReminder($tenantId, $booking, $tenant)
    {
        $to = $booking['client_email'];
        $subject = 'Reminder: Upcoming Appointment';

        $body = "Dear {$booking['client_name']},\n\n";
        $body .= "This is a reminder of your upcoming appointment:\n\n";
        $body .= "Service: {$booking['service_name']}\n";
        $body .= "Date: " . TimeHelper::formatDate($booking['booking_date']) . "\n";
        $body .= "Time: " . TimeHelper::formatTime($booking['start_time']) . "\n";

        if (!empty($booking['staff_name'])) {
            $body .= "Staff: {$booking['staff_name']}\n";
        }

        $body .= "\nReference Number: {$booking['reference_number']}\n\n";
        $body .= "We look forward to seeing you!\n\n";
        $body .= "{$tenant['business_name']}\n";

        if (!empty($tenant['phone'])) {
            $body .= "Phone: {$tenant['phone']}\n";
        }

        return $this->send($tenantId, $to, $subject, $body, 'reminder', $booking['id']);
    }

    /**
     * Send booking status change email
     *
     * @param int $tenantId Tenant ID
     * @param array $booking Booking data
     * @param array $tenant Tenant data
     * @param string $newStatus New status
     * @return bool Success status
     */
    public function sendStatusChange($tenantId, $booking, $tenant, $newStatus)
    {
        $to = $booking['client_email'];
        $subject = 'Booking Status Update - ' . ucfirst($newStatus);

        $statusMessages = [
            'confirmed' => 'Your appointment has been confirmed.',
            'canceled' => 'Your appointment has been canceled.',
            'completed' => 'Thank you for your visit!',
        ];

        $body = "Dear {$booking['client_name']},\n\n";
        $body .= $statusMessages[$newStatus] ?? "Your appointment status has been updated to: " . ucfirst($newStatus) . ".\n";
        $body .= "\nAppointment Details:\n";
        $body .= "Service: {$booking['service_name']}\n";
        $body .= "Date: " . TimeHelper::formatDate($booking['booking_date']) . "\n";
        $body .= "Time: " . TimeHelper::formatTime($booking['start_time']) . "\n";
        $body .= "Reference Number: {$booking['reference_number']}\n\n";

        if ($newStatus === 'canceled') {
            $body .= "If you did not request this cancellation or have any questions, please contact us.\n\n";
        }

        $body .= "{$tenant['business_name']}\n";
        if (!empty($tenant['phone'])) {
            $body .= "Phone: {$tenant['phone']}\n";
        }

        return $this->send($tenantId, $to, $subject, $body, 'status_change', $booking['id']);
    }

    /**
     * Get email logs for a tenant
     *
     * @param int $tenantId Tenant ID
     * @param int $limit Number of records
     * @return array Email logs
     */
    public function getEmailLogs($tenantId, $limit = 50)
    {
        $sql = "SELECT * FROM email_logs WHERE tenant_id = ? ORDER BY sent_at DESC LIMIT ?";
        return $this->db->query($sql, [$tenantId, $limit]);
    }
}
