<!-- FILE: /app/views/dashboard/index.php -->
<h1>Dashboard</h1>

<div class="dashboard-grid">
    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-info">
                <h3><?php echo e($stats['today_bookings']); ?></h3>
                <p>Today's Bookings</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-info">
                <h3><?php echo e($stats['upcoming_bookings']); ?></h3>
                <p>Upcoming</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?php echo e($stats['total_clients']); ?></h3>
                <p>Clients</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">👨‍💼</div>
            <div class="stat-info">
                <h3><?php echo e($stats['total_staff']); ?></h3>
                <p>Staff Members</p>
            </div>
        </div>
    </div>

    <!-- Booking Statistics -->
    <div class="card">
        <h2>This Month's Bookings</h2>
        <div class="booking-stats">
            <div class="stat-item">
                <span class="label">Total:</span>
                <span class="value"><?php echo e($bookingStats['total_bookings']); ?></span>
            </div>
            <div class="stat-item">
                <span class="label">Confirmed:</span>
                <span class="value"><?php echo e($bookingStats['confirmed_count']); ?></span>
            </div>
            <div class="stat-item">
                <span class="label">Completed:</span>
                <span class="value"><?php echo e($bookingStats['completed_count']); ?></span>
            </div>
            <div class="stat-item">
                <span class="label">Canceled:</span>
                <span class="value"><?php echo e($bookingStats['canceled_count']); ?></span>
            </div>
        </div>
    </div>

    <!-- Today's Bookings -->
    <div class="card">
        <h2>Today's Bookings</h2>
        <?php if (empty($todayBookings)): ?>
            <p class="text-muted">No bookings scheduled for today</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Staff</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todayBookings as $booking): ?>
                        <tr>
                            <td><?php echo e(TimeHelper::formatTime($booking['start_time'])); ?></td>
                            <td><?php echo e($booking['client_display_name']); ?></td>
                            <td><?php echo e($booking['service_name']); ?></td>
                            <td><?php echo e($booking['staff_name'] ?? 'Any'); ?></td>
                            <td><span class="badge badge-<?php echo e($booking['status']); ?>"><?php echo e(ucfirst($booking['status'])); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Upcoming Bookings -->
    <div class="card">
        <h2>Upcoming Bookings</h2>
        <?php if (empty($upcomingBookings)): ?>
            <p class="text-muted">No upcoming bookings</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Client</th>
                        <th>Service</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcomingBookings as $booking): ?>
                        <tr>
                            <td><?php echo e(TimeHelper::formatDate($booking['booking_date'])); ?></td>
                            <td><?php echo e(TimeHelper::formatTime($booking['start_time'])); ?></td>
                            <td><?php echo e($booking['client_display_name']); ?></td>
                            <td><?php echo e($booking['service_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Subscription Info -->
    <?php if (isset($subscription)): ?>
        <div class="card">
            <h2>Subscription</h2>
            <p><strong>Plan:</strong> <?php echo e($subscription['plan_name']); ?></p>
            <p><strong>Status:</strong> <span class="badge badge-<?php echo e($subscription['status']); ?>"><?php echo e(ucfirst($subscription['status'])); ?></span></p>
            <p><strong>Period Ends:</strong> <?php echo e(TimeHelper::formatDate($subscription['current_period_end'])); ?></p>
            <a href="/subscription" class="btn btn-secondary">Manage Subscription</a>
        </div>
    <?php endif; ?>
</div>
