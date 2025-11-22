<?php
// FILE: /app/controllers/DashboardController.php

/**
 * DashboardController - Main dashboard
 *
 * Displays overview and statistics for tenants
 */
class DashboardController extends Controller
{
    private $bookingModel;
    private $clientModel;
    private $staffModel;
    private $serviceModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();

        $this->bookingModel = new BookingModel();
        $this->clientModel = new ClientModel();
        $this->staffModel = new StaffModel();
        $this->serviceModel = new ServiceModel();
    }

    /**
     * Dashboard index
     */
    public function index()
    {
        $user = $this->getCurrentUser();

        // Platform admin dashboard
        if ($user['role'] === 'platform_admin') {
            $this->platformAdminDashboard();
            return;
        }

        // Tenant dashboard
        $tenantId = $this->getTenantId();

        // Get today's date
        $today = date('Y-m-d');

        // Get statistics
        $stats = [
            'today_bookings' => count($this->bookingModel->getBookingsByDate($tenantId, $today)),
            'upcoming_bookings' => count($this->bookingModel->getUpcomingBookings($tenantId)),
            'total_clients' => $this->clientModel->countByTenant($tenantId),
            'total_staff' => $this->staffModel->countByTenant($tenantId),
            'total_services' => $this->serviceModel->countByTenant($tenantId)
        ];

        // Get booking statistics
        $bookingStats = $this->bookingModel->getStatistics($tenantId, date('Y-m-01'), date('Y-m-t'));

        // Get today's bookings
        $todayBookings = $this->bookingModel->getBookingsByDate($tenantId, $today);

        // Get upcoming bookings
        $upcomingBookings = $this->bookingModel->getUpcomingBookings($tenantId, 5);

        // Get recent clients
        $recentClients = $this->clientModel->getRecentClients($tenantId, 5);

        // Get subscription info
        $subscriptionModel = new SubscriptionModel();
        $subscription = $subscriptionModel->getActiveSubscription($tenantId);

        $this->render('dashboard/index', [
            'stats' => $stats,
            'bookingStats' => $bookingStats,
            'todayBookings' => $todayBookings,
            'upcomingBookings' => $upcomingBookings,
            'recentClients' => $recentClients,
            'subscription' => $subscription
        ]);
    }

    /**
     * Platform admin dashboard
     */
    private function platformAdminDashboard()
    {
        $tenantModel = new TenantModel();
        $tenants = $tenantModel->getActiveTenants();

        $this->render('dashboard/platform_admin', [
            'tenants' => $tenants
        ]);
    }
}
