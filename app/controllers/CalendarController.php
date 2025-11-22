<?php
// FILE: /app/controllers/CalendarController.php

class CalendarController extends Controller
{
    private $bookingModel;
    private $staffModel;
    private $serviceModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->bookingModel = new BookingModel();
        $this->staffModel = new StaffModel();
        $this->serviceModel = new ServiceModel();
    }

    public function index()
    {
        $staff = $this->staffModel->getStaffByTenant($this->getTenantId());
        $services = $this->serviceModel->getServicesByTenant($this->getTenantId());
        $this->render('calendar/index', compact('staff', 'services'));
    }

    public function day()
    {
        $date = $this->request->get('date', date('Y-m-d'));
        $staffId = $this->request->get('staff_id');

        $bookings = $this->bookingModel->getBookingsByDate($this->getTenantId(), $date, $staffId);
        $staff = $this->staffModel->getStaffByTenant($this->getTenantId());

        $this->render('calendar/day', compact('date', 'bookings', 'staff', 'staffId'));
    }

    public function week()
    {
        $date = $this->request->get('date', date('Y-m-d'));
        $weekStart = date('Y-m-d', strtotime('monday this week', strtotime($date)));

        $bookings = [];
        for ($i = 0; $i < 7; $i++) {
            $currentDate = date('Y-m-d', strtotime("+$i days", strtotime($weekStart)));
            $bookings[$currentDate] = $this->bookingModel->getBookingsByDate($this->getTenantId(), $currentDate);
        }

        $staff = $this->staffModel->getStaffByTenant($this->getTenantId());

        $this->render('calendar/week', compact('weekStart', 'bookings', 'staff'));
    }

    public function data()
    {
        $start = $this->request->get('start');
        $end = $this->request->get('end');
        $staffId = $this->request->get('staff_id');

        $filters = [
            'date_from' => $start,
            'date_to' => $end
        ];

        if ($staffId) {
            $filters['staff_id'] = $staffId;
        }

        $bookings = $this->bookingModel->getBookingsByTenant($this->getTenantId(), $filters, 500);

        // Format for FullCalendar
        $events = [];
        foreach ($bookings as $booking) {
            $events[] = [
                'id' => $booking['id'],
                'title' => $booking['client_display_name'] . ' - ' . $booking['service_name'],
                'start' => $booking['booking_date'] . 'T' . $booking['start_time'],
                'end' => $booking['booking_date'] . 'T' . $booking['end_time'],
                'backgroundColor' => $booking['service_color'],
                'url' => '/bookings/' . $booking['id']
            ];
        }

        $this->json($events);
    }
}
