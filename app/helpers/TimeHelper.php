<?php
// FILE: /app/helpers/TimeHelper.php

/**
 * TimeHelper - Timezone and datetime utilities
 *
 * Provides helper functions for working with timezones,
 * converting times, and formatting dates.
 */
class TimeHelper
{
    /**
     * Convert datetime from one timezone to another
     *
     * @param string $datetime Datetime string
     * @param string $fromTimezone Source timezone
     * @param string $toTimezone Target timezone
     * @param string $format Output format
     * @return string Converted datetime
     */
    public static function convert($datetime, $fromTimezone, $toTimezone, $format = 'Y-m-d H:i:s')
    {
        try {
            $dt = new DateTime($datetime, new DateTimeZone($fromTimezone));
            $dt->setTimezone(new DateTimeZone($toTimezone));
            return $dt->format($format);
        } catch (Exception $e) {
            return $datetime;
        }
    }

    /**
     * Convert datetime to UTC for storage
     *
     * @param string $datetime Datetime string
     * @param string $fromTimezone Source timezone
     * @return string UTC datetime
     */
    public static function toUtc($datetime, $fromTimezone)
    {
        return self::convert($datetime, $fromTimezone, 'UTC');
    }

    /**
     * Convert datetime from UTC to a specific timezone
     *
     * @param string $datetime UTC datetime string
     * @param string $toTimezone Target timezone
     * @param string $format Output format
     * @return string Converted datetime
     */
    public static function fromUtc($datetime, $toTimezone, $format = 'Y-m-d H:i:s')
    {
        return self::convert($datetime, 'UTC', $toTimezone, $format);
    }

    /**
     * Format date for display
     *
     * @param string $date Date string
     * @param string $format Output format (default: M d, Y)
     * @return string Formatted date
     */
    public static function formatDate($date, $format = 'M d, Y')
    {
        if (empty($date)) {
            return '';
        }

        try {
            $dt = new DateTime($date);
            return $dt->format($format);
        } catch (Exception $e) {
            return $date;
        }
    }

    /**
     * Format time for display
     *
     * @param string $time Time string
     * @param string $format Output format (default: g:i A)
     * @return string Formatted time
     */
    public static function formatTime($time, $format = 'g:i A')
    {
        if (empty($time)) {
            return '';
        }

        try {
            $dt = new DateTime($time);
            return $dt->format($format);
        } catch (Exception $e) {
            return $time;
        }
    }

    /**
     * Format datetime for display
     *
     * @param string $datetime Datetime string
     * @param string $format Output format
     * @return string Formatted datetime
     */
    public static function formatDateTime($datetime, $format = 'M d, Y g:i A')
    {
        if (empty($datetime)) {
            return '';
        }

        try {
            $dt = new DateTime($datetime);
            return $dt->format($format);
        } catch (Exception $e) {
            return $datetime;
        }
    }

    /**
     * Get day of week from date (0=Sunday, 6=Saturday)
     *
     * @param string $date Date string
     * @return int Day of week
     */
    public static function getDayOfWeek($date)
    {
        try {
            $dt = new DateTime($date);
            return (int) $dt->format('w');
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Add minutes to a time
     *
     * @param string $time Time string (HH:MM:SS)
     * @param int $minutes Minutes to add
     * @return string New time
     */
    public static function addMinutes($time, $minutes)
    {
        try {
            $dt = new DateTime($time);
            $dt->modify("+{$minutes} minutes");
            return $dt->format('H:i:s');
        } catch (Exception $e) {
            return $time;
        }
    }

    /**
     * Calculate duration between two times in minutes
     *
     * @param string $startTime Start time (HH:MM:SS)
     * @param string $endTime End time (HH:MM:SS)
     * @return int Duration in minutes
     */
    public static function getDurationMinutes($startTime, $endTime)
    {
        try {
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);
            $diff = $start->diff($end);
            return ($diff->h * 60) + $diff->i;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Check if a time is between two other times
     *
     * @param string $time Time to check
     * @param string $startTime Start time
     * @param string $endTime End time
     * @return bool
     */
    public static function isBetween($time, $startTime, $endTime)
    {
        return $time >= $startTime && $time < $endTime;
    }

    /**
     * Get available timezones for dropdown
     *
     * @return array Timezone list
     */
    public static function getTimezones()
    {
        $timezones = [];
        $identifiers = DateTimeZone::listIdentifiers();

        foreach ($identifiers as $identifier) {
            $timezones[$identifier] = $identifier;
        }

        return $timezones;
    }

    /**
     * Get common US timezones
     *
     * @return array Common timezone list
     */
    public static function getCommonTimezones()
    {
        return [
            'America/New_York' => 'Eastern Time (ET)',
            'America/Chicago' => 'Central Time (CT)',
            'America/Denver' => 'Mountain Time (MT)',
            'America/Phoenix' => 'Mountain Time - Arizona (no DST)',
            'America/Los_Angeles' => 'Pacific Time (PT)',
            'America/Anchorage' => 'Alaska Time (AKT)',
            'Pacific/Honolulu' => 'Hawaii Time (HT)',
            'UTC' => 'UTC (Coordinated Universal Time)',
        ];
    }

    /**
     * Get time slots between start and end time
     *
     * @param string $startTime Start time (HH:MM)
     * @param string $endTime End time (HH:MM)
     * @param int $intervalMinutes Interval in minutes
     * @return array Array of time slots
     */
    public static function getTimeSlots($startTime, $endTime, $intervalMinutes = 30)
    {
        $slots = [];
        $current = new DateTime($startTime);
        $end = new DateTime($endTime);

        while ($current < $end) {
            $slots[] = $current->format('H:i:s');
            $current->modify("+{$intervalMinutes} minutes");
        }

        return $slots;
    }
}
