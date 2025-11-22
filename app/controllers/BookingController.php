<?php
// FILE: /app/controllers/BookingController.php

/**
 * BookingController - Manages bookings
 *
 * Handles booking CRUD operations, status updates, and scheduling
 */
class BookingController extends Controller
{
    private $bookingModel;
    private $serviceModel;
    private $staffModel;
    private $clientModel;
    private $subscriptionModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();

        $this->bookingModel = new BookingModel();
        $this->serviceModel = new ServiceModel();
        $this->staffModel = new StaffModel();
        $this->clientModel = new ClientModel();
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * List bookings
     */
    public function index()
    {
        $tenantId = $this->getTenantId();

        // Get filters
        $filters = [
            'status' => $this->request->get('status'),
            'date_from' => $this->request->get('date_from'),
            'date_to' => $this->request->get('date_to'),
            'staff_id' => $this->request->get('staff_id'),
            'service_id' => $this->request->get('service_id')
        ];

        $page = (int) $this->request->get('page', 1);
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $bookings = $this->bookingModel->getBookingsByTenant($tenantId, $filters, $limit, $offset);
        $staff = $this->staffModel->getStaffByTenant($tenantId);
        $services = $this->serviceModel->getServicesByTenant($tenantId);

        $this->render('bookings/index', [
            'bookings' => $bookings,
            'staff' => $staff,
            'services' => $services,
            'filters' => $filters
        ]);
    }

    /**
     * Show create booking form
     */
    public function create()
    {
        $tenantId = $this->getTenantId();

        // Check subscription limits
        $canCreate = $this->subscriptionModel->canCreateBooking($tenantId);
        if (!$canCreate['allowed']) {
            $this->setFlash('error', $canCreate['message']);
            $this->redirect('/bookings');
        }

        $services = $this->serviceModel->getServicesByTenant($tenantId);
        $staff = $this->staffModel->getStaffByTenant($tenantId);
        $clients = $this->clientModel->getClientsByTenant($tenantId, null, 100);

        $this->render('bookings/create', [
            'services' => $services,
            'staff' => $staff,
            'clients' => $clients
        ]);
    }

    /**
     * Store new booking
     */
    public function store()
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();
        $user = $this->getCurrentUser();

        // Check subscription limits
        $canCreate = $this->subscriptionModel->canCreateBooking($tenantId);
        if (!$canCreate['allowed']) {
            $this->setFlash('error', $canCreate['message']);
            $this->redirect('/bookings');
        }

        // Validate input
        $errors = $this->validate($_POST, [
            'service_id' => 'required|integer',
            'booking_date' => 'required|date',
            'start_time' => 'required',
            'client_name' => 'required',
            'client_email' => 'email',
            'client_phone' => 'phone'
        ]);

        if (!empty($errors)) {
            $this->setFlash('error', 'Please correct the errors and try again.');
            $this->redirect('/bookings/create');
        }

        // Get service details
        $service = $this->serviceModel->findById($this->request->post('service_id'));
        if (!$service) {
            $this->setFlash('error', 'Service not found');
            $this->redirect('/bookings/create');
        }

        // Calculate end time
        $startTime = $this->request->post('start_time');
        $endTime = TimeHelper::addMinutes($startTime, $service['duration_minutes']);

        // Check availability
        $staffId = $this->request->post('staff_id');
        if ($staffId) {
            $isAvailable = $this->bookingModel->isTimeSlotAvailable(
                $tenantId,
                $staffId,
                $this->request->post('booking_date'),
                $startTime,
                $endTime
            );

            if (!$isAvailable) {
                $this->setFlash('error', 'The selected time slot is not available');
                $this->redirect('/bookings/create');
            }
        }

        // Handle client
        $clientId = $this->request->post('client_id');
        if (!$clientId && $this->request->post('client_email')) {
            $clientId = $this->clientModel->findOrCreate($tenantId, [
                'first_name' => $this->request->post('client_name'),
                'last_name' => '',
                'email' => $this->request->post('client_email'),
                'phone' => $this->request->post('client_phone')
            ]);
        }

        // Create booking
        try {
            $bookingId = $this->bookingModel->insert([
                'tenant_id' => $tenantId,
                'service_id' => $this->request->post('service_id'),
                'staff_id' => $staffId ?: null,
                'client_id' => $clientId,
                'client_name' => $this->request->post('client_name'),
                'client_email' => $this->request->post('client_email'),
                'client_phone' => $this->request->post('client_phone'),
                'booking_date' => $this->request->post('booking_date'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => 'confirmed',
                'internal_notes' => $this->request->post('internal_notes'),
                'reference_number' => $this->bookingModel->generateReferenceNumber(),
                'created_by_user_id' => $user['id']
            ]);

            // Send confirmation email
            if ($this->request->post('client_email')) {
                $booking = $this->bookingModel->getBookingWithDetails($bookingId);
                $tenantModel = new TenantModel();
                $tenant = $tenantModel->findById($tenantId);

                $emailHelper = new EmailHelper();
                $emailHelper->sendBookingConfirmation($tenantId, $booking, $tenant);
            }

            $this->setFlash('success', 'Booking created successfully');
            $this->redirect('/bookings');

        } catch (Exception $e) {
            error_log('Booking creation failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to create booking');
            $this->redirect('/bookings/create');
        }
    }

    /**
     * Show booking details
     */
    public function show($id)
    {
        $booking = $this->bookingModel->getBookingWithDetails($id);

        if (!$booking || $booking['tenant_id'] != $this->getTenantId()) {
            $this->setFlash('error', 'Booking not found');
            $this->redirect('/bookings');
        }

        $this->render('bookings/show', ['booking' => $booking]);
    }

    /**
     * Show edit booking form
     */
    public function edit($id)
    {
        $tenantId = $this->getTenantId();
        $booking = $this->bookingModel->getBookingWithDetails($id);

        if (!$booking || $booking['tenant_id'] != $tenantId) {
            $this->setFlash('error', 'Booking not found');
            $this->redirect('/bookings');
        }

        $services = $this->serviceModel->getServicesByTenant($tenantId);
        $staff = $this->staffModel->getStaffByTenant($tenantId);

        $this->render('bookings/edit', [
            'booking' => $booking,
            'services' => $services,
            'staff' => $staff
        ]);
    }

    /**
     * Update booking
     */
    public function update($id)
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();

        $booking = $this->bookingModel->findById($id);
        if (!$booking || $booking['tenant_id'] != $tenantId) {
            $this->setFlash('error', 'Booking not found');
            $this->redirect('/bookings');
        }

        // Get service details
        $service = $this->serviceModel->findById($this->request->post('service_id'));
        if (!$service) {
            $this->setFlash('error', 'Service not found');
            $this->redirect('/bookings/' . $id . '/edit');
        }

        // Calculate end time
        $startTime = $this->request->post('start_time');
        $endTime = TimeHelper::addMinutes($startTime, $service['duration_minutes']);

        // Check availability (excluding current booking)
        $staffId = $this->request->post('staff_id');
        if ($staffId) {
            $isAvailable = $this->bookingModel->isTimeSlotAvailable(
                $tenantId,
                $staffId,
                $this->request->post('booking_date'),
                $startTime,
                $endTime,
                $id
            );

            if (!$isAvailable) {
                $this->setFlash('error', 'The selected time slot is not available');
                $this->redirect('/bookings/' . $id . '/edit');
            }
        }

        // Update booking
        try {
            $this->bookingModel->update($id, [
                'service_id' => $this->request->post('service_id'),
                'staff_id' => $staffId ?: null,
                'booking_date' => $this->request->post('booking_date'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'internal_notes' => $this->request->post('internal_notes')
            ]);

            $this->setFlash('success', 'Booking updated successfully');
            $this->redirect('/bookings/' . $id);

        } catch (Exception $e) {
            error_log('Booking update failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to update booking');
            $this->redirect('/bookings/' . $id . '/edit');
        }
    }

    /**
     * Update booking status
     */
    public function updateStatus($id)
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();

        $booking = $this->bookingModel->findById($id);
        if (!$booking || $booking['tenant_id'] != $tenantId) {
            $this->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $newStatus = $this->request->post('status');
        $allowedStatuses = ['pending', 'confirmed', 'completed', 'canceled', 'no_show'];

        if (!in_array($newStatus, $allowedStatuses)) {
            $this->json(['success' => false, 'message' => 'Invalid status'], 400);
        }

        try {
            $updateData = ['status' => $newStatus];
            if ($newStatus === 'canceled') {
                $updateData['canceled_at'] = date('Y-m-d H:i:s');
            }

            $this->bookingModel->update($id, $updateData);

            // Send status change email
            if (!empty($booking['client_email'])) {
                $bookingDetails = $this->bookingModel->getBookingWithDetails($id);
                $tenantModel = new TenantModel();
                $tenant = $tenantModel->findById($tenantId);

                $emailHelper = new EmailHelper();
                $emailHelper->sendStatusChange($tenantId, $bookingDetails, $tenant, $newStatus);
            }

            $this->json(['success' => true, 'message' => 'Status updated successfully']);

        } catch (Exception $e) {
            error_log('Status update failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Failed to update status'], 500);
        }
    }

    /**
     * Delete booking
     */
    public function delete($id)
    {
        $this->requireCsrf();
        $tenantId = $this->getTenantId();

        $booking = $this->bookingModel->findById($id);
        if (!$booking || $booking['tenant_id'] != $tenantId) {
            $this->setFlash('error', 'Booking not found');
            $this->redirect('/bookings');
        }

        try {
            $this->bookingModel->delete($id);
            $this->setFlash('success', 'Booking deleted successfully');
        } catch (Exception $e) {
            error_log('Booking deletion failed: ' . $e->getMessage());
            $this->setFlash('error', 'Failed to delete booking');
        }

        $this->redirect('/bookings');
    }
}
