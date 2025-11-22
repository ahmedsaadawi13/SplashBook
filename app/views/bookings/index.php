<!-- FILE: /app/views/bookings/index.php -->
<div class="page-header">
    <h1>Bookings</h1>
    <a href="/bookings/create" class="btn btn-primary">New Booking</a>
</div>

<div class="card">
    <!-- Filters -->
    <form method="GET" action="/bookings" class="filters-form">
        <div class="form-row">
            <div class="form-group">
                <select name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="confirmed" <?php echo ($filters['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="canceled" <?php echo ($filters['status'] ?? '') === 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                </select>
            </div>
            <div class="form-group">
                <input type="date" name="date_from" class="form-control" placeholder="From Date" value="<?php echo e($filters['date_from'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <input type="date" name="date_to" class="form-control" placeholder="To Date" value="<?php echo e($filters['date_to'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    </form>

    <!-- Bookings Table -->
    <?php if (empty($bookings)): ?>
        <p class="text-muted">No bookings found</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Ref#</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Client</th>
                    <th>Service</th>
                    <th>Staff</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo e($booking['reference_number']); ?></td>
                        <td><?php echo e(TimeHelper::formatDate($booking['booking_date'])); ?></td>
                        <td><?php echo e(TimeHelper::formatTime($booking['start_time'])); ?></td>
                        <td><?php echo e($booking['client_display_name'] ?? $booking['client_name']); ?></td>
                        <td><span class="badge" style="background-color: <?php echo e($booking['service_color']); ?>"><?php echo e($booking['service_name']); ?></span></td>
                        <td><?php echo e($booking['staff_name'] ?? 'Any'); ?></td>
                        <td><span class="badge badge-<?php echo e($booking['status']); ?>"><?php echo e(ucfirst($booking['status'])); ?></span></td>
                        <td>
                            <a href="/bookings/<?php echo e($booking['id']); ?>" class="btn btn-sm btn-secondary">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
