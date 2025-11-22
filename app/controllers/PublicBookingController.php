<?php
// FILE: /app/controllers/PublicBookingController.php

class PublicBookingController extends Controller
{
    private $tenantModel;
    private $serviceModel;
    private $staffModel;
    private $bookingModel;
    private $availabilityModel;
    private $clientModel;

    public function __construct()
    {
        parent::__construct();
        $this->tenantModel = new TenantModel();
        $this->serviceModel = new ServiceModel();
        $this->staffModel = new StaffModel();
        $this->bookingModel = new BookingModel();
        $this->availabilityModel = new AvailabilityModel();
        $this->clientModel = new ClientModel();
    }

    public function index($slug)
    {
        $tenant = $this->tenantModel->findBySlug($slug);
        if (!$tenant || $tenant['status'] != 'active') {
            die('Business not found');
        }

        $servicesGrouped = $this->serviceModel->getServicesGroupedByCategory($tenant['id']);

        $this->render('public/booking/index', [
            'tenant' => $tenant,
            'servicesGrouped' => $servicesGrouped
        ], 'layout_public');
    }

    public function services($slug)
    {
        $tenant = $this->tenantModel->findBySlug($slug);
        if (!$tenant) {
            $this->json(['error' => 'Business not found'], 404);
        }

        $services = $this->serviceModel->getServicesByTenant($tenant['id']);
        $this->json($services);
    }

    public function availability($slug)
    {
        $tenant = $this->tenantModel->findBySlug($slug);
        if (!$tenant) {
            $this->json(['error' => 'Business not found'], 404);
        }

        $serviceId = $this->request->post('service_id');
        $staffId = $this->request->post('staff_id');
        $date = $this->request->post('date');

        if (!$serviceId || !$date) {
            $this->json(['error' => 'Missing parameters'], 400);
        }

        if ($staffId) {
            $slots = $this->availabilityModel->getAvailableSlots($tenant['id'], $staffId, $serviceId, $date);
            $this->json(['slots' => $slots]);
        } else {
            $slotsGrouped = $this->availabilityModel->getAvailableSlotsForService($tenant['id'], $serviceId, $date);
            $this->json(['staffSlots' => $slotsGrouped]);
        }
    }

    public function create($slug)
    {
        $tenant = $this->tenantModel->findBySlug($slug);
        if (!$tenant) {
            $this->json(['error' => 'Business not found'], 404);
        }

        $serviceId = $this->request->post('service_id');
        $staffId = $this->request->post('staff_id');
        $date = $this->request->post('date');
        $startTime = $this->request->post('start_time');
        $clientName = $this->request->post('client_name');
        $clientEmail = $this->request->post('client_email');
        $clientPhone = $this->request->post('client_phone');

        // Validate
        if (!$serviceId || !$date || !$startTime || !$clientName || !$clientEmail) {
            $this->json(['error' => 'Missing required fields'], 400);
        }

        // Get service
        $service = $this->serviceModel->findById($serviceId);
        if (!$service || $service['tenant_id'] != $tenant['id']) {
            $this->json(['error' => 'Invalid service'], 400);
        }

        $endTime = TimeHelper::addMinutes($startTime, $service['duration_minutes']);

        // Check availability
        if ($staffId) {
            $isAvailable = $this->bookingModel->isTimeSlotAvailable($tenant['id'], $staffId, $date, $startTime, $endTime);
            if (!$isAvailable) {
                $this->json(['error' => 'Time slot no longer available'], 400);
            }
        }

        try {
            // Find or create client
            $clientId = $this->clientModel->findOrCreate($tenant['id'], [
                'first_name' => $clientName,
                'last_name' => '',
                'email' => $clientEmail,
                'phone' => $clientPhone
            ]);

            // Create booking
            $bookingId = $this->bookingModel->insert([
                'tenant_id' => $tenant['id'],
                'service_id' => $serviceId,
                'staff_id' => $staffId ?: null,
                'client_id' => $clientId,
                'client_name' => $clientName,
                'client_email' => $clientEmail,
                'client_phone' => $clientPhone,
                'booking_date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'pending',
                'reference_number' => $this->bookingModel->generateReferenceNumber()
            ]);

            // Send confirmation email
            $booking = $this->bookingModel->getBookingWithDetails($bookingId);
            $emailHelper = new EmailHelper();
            $emailHelper->sendBookingConfirmation($tenant['id'], $booking, $tenant);

            $this->json([
                'success' => true,
                'reference' => $booking['reference_number']
            ]);

        } catch (Exception $e) {
            error_log('Public booking failed: ' . $e->getMessage());
            $this->json(['error' => 'Booking failed'], 500);
        }
    }

    public function confirmation($slug, $reference)
    {
        $tenant = $this->tenantModel->findBySlug($slug);
        if (!$tenant) {
            die('Business not found');
        }

        $booking = $this->bookingModel->findByReference($reference);
        if (!$booking || $booking['tenant_id'] != $tenant['id']) {
            die('Booking not found');
        }

        $bookingDetails = $this->bookingModel->getBookingWithDetails($booking['id']);

        $this->render('public/booking/confirmation', [
            'tenant' => $tenant,
            'booking' => $bookingDetails
        ], 'layout_public');
    }
}
