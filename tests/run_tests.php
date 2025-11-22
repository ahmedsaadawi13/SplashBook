<?php
// FILE: /tests/run_tests.php

/**
 * Simple functional test runner for SplashBook
 *
 * Run: php tests/run_tests.php
 */

require_once __DIR__ . '/../bootstrap/autoload.php';

class TestRunner
{
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function test($name, $callback)
    {
        echo "Testing: $name ... ";
        try {
            $callback();
            echo "✓ PASS\n";
            $this->passed++;
        } catch (Exception $e) {
            echo "✗ FAIL: " . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    public function assert($condition, $message = 'Assertion failed')
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected '$expected', got '$actual'";
            throw new Exception($msg);
        }
    }

    public function summary()
    {
        echo "\n" . str_repeat('-', 50) . "\n";
        echo "Tests Passed: {$this->passed}\n";
        echo "Tests Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo str_repeat('-', 50) . "\n";

        return $this->failed === 0;
    }
}

$test = new TestRunner();

// ============================================================================
// Database Connection Tests
// ============================================================================

$test->test('Database connection', function() use ($test) {
    $db = Database::getInstance();
    $test->assert($db !== null, 'Database instance should not be null');
});

// ============================================================================
// Model Tests
// ============================================================================

$test->test('TenantModel - Find by slug', function() use ($test) {
    $tenantModel = new TenantModel();
    $tenant = $tenantModel->findBySlug('bella-beauty');
    $test->assert($tenant !== null, 'Should find tenant by slug');
    $test->assertEquals('Bella Beauty Salon', $tenant['business_name']);
});

$test->test('TenantModel - Generate slug', function() use ($test) {
    $tenantModel = new TenantModel();
    $slug = $tenantModel->generateSlug('Test Business Name');
    $test->assert(preg_match('/^[a-z0-9-]+$/', $slug), 'Slug should be URL-friendly');
});

$test->test('UserModel - Find by email and tenant', function() use ($test) {
    $userModel = new UserModel();
    $user = $userModel->findByEmailAndTenant('admin@bellasalon.example', 1);
    $test->assert($user !== null, 'Should find user');
    $test->assertEquals('tenant_admin', $user['role']);
});

$test->test('ServiceModel - Get services by tenant', function() use ($test) {
    $serviceModel = new ServiceModel();
    $services = $serviceModel->getServicesByTenant(1);
    $test->assert(count($services) > 0, 'Should have services');
});

$test->test('BookingModel - Generate reference number', function() use ($test) {
    $bookingModel = new BookingModel();
    $ref = $bookingModel->generateReferenceNumber();
    $test->assert(strpos($ref, 'BOOK-') === 0, 'Reference should start with BOOK-');
});

$test->test('ClientModel - Find or create', function() use ($test) {
    $clientModel = new ClientModel();
    $clientId = $clientModel->findOrCreate(1, [
        'first_name' => 'Test',
        'last_name' => 'Client',
        'email' => 'test' . time() . '@example.com',
        'phone' => '+1-555-9999'
    ]);
    $test->assert($clientId > 0, 'Should create client and return ID');
});

// ============================================================================
// Helper Tests
// ============================================================================

$test->test('TimeHelper - Format date', function() use ($test) {
    $formatted = TimeHelper::formatDate('2025-12-25');
    $test->assert(strlen($formatted) > 0, 'Should format date');
});

$test->test('TimeHelper - Add minutes', function() use ($test) {
    $newTime = TimeHelper::addMinutes('09:00:00', 30);
    $test->assertEquals('09:30:00', $newTime);
});

$test->test('TimeHelper - Get day of week', function() use ($test) {
    $day = TimeHelper::getDayOfWeek('2025-01-01'); // Wednesday
    $test->assert($day >= 0 && $day <= 6, 'Day should be between 0-6');
});

$test->test('Validator - Email validation', function() use ($test) {
    $errors = Validator::validate(['email' => 'invalid'], ['email' => 'required|email']);
    $test->assert(!empty($errors), 'Should have validation errors for invalid email');

    $errors = Validator::validate(['email' => 'valid@example.com'], ['email' => 'required|email']);
    $test->assert(empty($errors), 'Should pass for valid email');
});

$test->test('Validator - Required validation', function() use ($test) {
    $errors = Validator::validate(['name' => ''], ['name' => 'required']);
    $test->assert(!empty($errors), 'Should have errors for empty required field');
});

// ============================================================================
// Availability Tests
// ============================================================================

$test->test('AvailabilityModel - Get available slots', function() use ($test) {
    $availabilityModel = new AvailabilityModel();

    // Use a future date to avoid past date issues
    $futureDate = date('Y-m-d', strtotime('+7 days'));

    // Ensure it's a weekday (Monday = 1)
    while (date('N', strtotime($futureDate)) >= 6) {
        $futureDate = date('Y-m-d', strtotime($futureDate . ' +1 day'));
    }

    $slots = $availabilityModel->getAvailableSlots(1, 1, 1, $futureDate);
    // Note: slots may be empty if it's a holiday or outside working hours
    $test->assert(is_array($slots), 'Should return array of slots');
});

// ============================================================================
// Booking Tests
// ============================================================================

$test->test('BookingModel - Check time slot availability', function() use ($test) {
    $bookingModel = new BookingModel();

    // Check a time slot far in the future
    $futureDate = date('Y-m-d', strtotime('+30 days'));
    $isAvailable = $bookingModel->isTimeSlotAvailable(
        1,
        1,
        $futureDate,
        '14:00:00',
        '15:00:00'
    );

    $test->assert(is_bool($isAvailable), 'Should return boolean');
});

// ============================================================================
// Subscription Tests
// ============================================================================

$test->test('SubscriptionModel - Get active subscription', function() use ($test) {
    $subscriptionModel = new SubscriptionModel();
    $subscription = $subscriptionModel->getActiveSubscription(1);
    $test->assert($subscription !== null, 'Should have active subscription');
});

$test->test('SubscriptionModel - Check limits', function() use ($test) {
    $subscriptionModel = new SubscriptionModel();

    $canAddStaff = $subscriptionModel->canAddStaff(1);
    $test->assert(isset($canAddStaff['allowed']), 'Should return array with allowed key');

    $canAddService = $subscriptionModel->canAddService(1);
    $test->assert(isset($canAddService['allowed']), 'Should return array with allowed key');
});

// ============================================================================
// Security Tests
// ============================================================================

$test->test('Password hashing', function() use ($test) {
    $password = 'testpassword123';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $test->assert(password_verify($password, $hash), 'Password should verify');
    $test->assert(!password_verify('wrongpassword', $hash), 'Wrong password should not verify');
});

$test->test('SQL injection prevention', function() use ($test) {
    $bookingModel = new BookingModel();

    // Try to inject SQL (should be safely handled by prepared statements)
    $maliciousInput = "1' OR '1'='1";
    $result = $bookingModel->findById($maliciousInput);

    // Should return null or handle gracefully, not cause SQL error
    $test->assert($result === null, 'Should handle malicious input safely');
});

// ============================================================================
// Print Summary
// ============================================================================

$success = $test->summary();
exit($success ? 0 : 1);
