<?php
// FILE: /app/controllers/ApiController.php

class ApiController extends Controller
{
    private $tenantModel;
    private $serviceModel;
    private $availabilityModel;
    private $bookingModel;
    private $clientModel;

    public function __construct()
    {
        parent::__construct();
        $this->tenantModel = new TenantModel();
        $this->serviceModel = new ServiceModel();
        $this->availabilityModel = new AvailabilityModel();
        $this->bookingModel = new BookingModel();
        $this->clientModel = new ClientModel();
    }

    private function authenticateApi()
    {
        $apiKey = $this->request->server('HTTP_X_API_KEY');
        if (!$apiKey) {
            $this->json(['error' => 'API key required'], 401);
        }

        $tenant = $this->tenantModel->findByApiKey($apiKey);
        if (!$tenant) {
            $this->json(['error' => 'Invalid API key'], 401);
        }

        return $tenant;
    }

    public function availability()
    {
        $tenant = $this->authenticateApi();

        $serviceId = $this->request->get('service_id');
        $staffId = $this->request->get('staff_id');
        $date = $this->request->get('date');
        $dateFrom = $this->request->get('date_from');
        $dateTo = $this->request->get('date_to');

        if (!$serviceId) {
            $this->json(['error' => 'service_id is required'], 400);
        }

        if (!$date && (!$dateFrom || !$dateTo)) {
            $this->json(['error' => 'date or date_from/date_to is required'], 400);
        }

        try {
            if ($date) {
                // Single date
                if ($staffId) {
                    $slots = $this->availabilityModel->getAvailableSlots($tenant['id'], $staffId, $serviceId, $date);
                    $this->json([
                        'date' => $date,
                        'service_id' => $serviceId,
                        'staff_id' => $staffId,
                        'slots' => $slots
                    ]);
                } else {
                    $slotsGrouped = $this->availabilityModel->getAvailableSlotsForService($tenant['id'], $serviceId, $date);
                    $this->json([
                        'date' => $date,
                        'service_id' => $serviceId,
                        'availability' => $slotsGrouped
                    ]);
                }
            } else {
                // Date range
                $result = [];
                $currentDate = $dateFrom;
                while ($currentDate <= $dateTo) {
                    if ($staffId) {
                        $slots = $this->availabilityModel->getAvailableSlots($tenant['id'], $staffId, $serviceId, $currentDate);
                    } else {
                        $slots = $this->availabilityModel->getAvailableSlotsForService($tenant['id'], $serviceId, $currentDate);
                    }

                    $result[] = [
                        'date' => $currentDate,
                        'slots' => $slots
                    ];

                    $currentDate = date('Y-m-d', strtotime('+1 day', strtotime($currentDate)));
                }

                $this->json([
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'service_id' => $serviceId,
                    'availability' => $result
                ]);
            }
        } catch (Exception $e) {
            error_log('API availability error: ' . $e->getMessage());
            $this->json(['error' => 'Internal server error'], 500);
        }
    }

    public function createBooking()
    {
        $tenant = $this->authenticateApi();

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            $this->json(['error' => 'Invalid JSON'], 400);
        }

        $requiredFields = ['service_id', 'date', 'start_time', 'client_name', 'client_email'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                $this->json(['error' => "$field is required"], 400);
            }
        }

        try {
            $serviceId = $input['service_id'];
            $staffId = !empty($input['staff_id']) ? $input['staff_id'] : null;
            $date = $input['date'];
            $startTime = $input['start_time'];

            // Get service
            $service = $this->serviceModel->findById($serviceId);
            if (!$service || $service['tenant_id'] != $tenant['id']) {
                $this->json(['error' => 'Invalid service_id'], 400);
            }

            $endTime = TimeHelper::addMinutes($startTime, $service['duration_minutes']);

            // Check availability
            if ($staffId) {
                $isAvailable = $this->bookingModel->isTimeSlotAvailable($tenant['id'], $staffId, $date, $startTime, $endTime);
                if (!$isAvailable) {
                    $this->json(['error' => 'Time slot not available'], 400);
                }
            }

            // Find or create client
            $clientId = $this->clientModel->findOrCreate($tenant['id'], [
                'first_name' => $input['client_name'],
                'last_name' => '',
                'email' => $input['client_email'],
                'phone' => $input['client_phone'] ?? ''
            ]);

            // Create booking
            $bookingId = $this->bookingModel->insert([
                'tenant_id' => $tenant['id'],
                'service_id' => $serviceId,
                'staff_id' => $staffId,
                'client_id' => $clientId,
                'client_name' => $input['client_name'],
                'client_email' => $input['client_email'],
                'client_phone' => $input['client_phone'] ?? '',
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'pending',
                'public_notes' => $input['notes'] ?? '',
                'reference_number' => $this->bookingModel->generateReferenceNumber()
            ]);

            $booking = $this->bookingModel->getBookingWithDetails($bookingId);

            // Send confirmation
            $emailHelper = new EmailHelper();
            $emailHelper->sendBookingConfirmation($tenant['id'], $booking, $tenant);

            $this->json([
                'success' => true,
                'booking_id' => $bookingId,
                'reference_number' => $booking['reference_number'],
                'status' => 'pending'
            ], 201);

        } catch (Exception $e) {
            error_log('API booking error: ' . $e->getMessage());
            $this->json(['error' => 'Booking creation failed'], 500);
        }
    }
}
