<!-- FILE: /app/views/public/booking/confirmation.php -->
<div class="confirmation-page">
    <div class="container">
        <div class="confirmation-box">
            <div class="success-icon">✓</div>
            <h1>Booking Confirmed!</h1>
            <p>Your appointment has been successfully booked.</p>

            <div class="booking-details">
                <h3>Booking Details</h3>
                <p><strong>Reference Number:</strong> <?php echo e($booking['reference_number']); ?></p>
                <p><strong>Service:</strong> <?php echo e($booking['service_name']); ?></p>
                <p><strong>Date:</strong> <?php echo e(TimeHelper::formatDate($booking['booking_date'])); ?></p>
                <p><strong>Time:</strong> <?php echo e(TimeHelper::formatTime($booking['start_time'])); ?></p>
                <?php if (!empty($booking['staff_name'])): ?>
                    <p><strong>Staff:</strong> <?php echo e($booking['staff_name']); ?></p>
                <?php endif; ?>
            </div>

            <div class="business-details">
                <h3><?php echo e($tenant['business_name']); ?></h3>
                <?php if (!empty($tenant['address'])): ?>
                    <p><?php echo e($tenant['address']); ?><br>
                    <?php echo e($tenant['city']); ?>, <?php echo e($tenant['state']); ?> <?php echo e($tenant['postal_code']); ?></p>
                <?php endif; ?>
                <?php if (!empty($tenant['phone'])): ?>
                    <p>Phone: <?php echo e($tenant['phone']); ?></p>
                <?php endif; ?>
            </div>

            <p class="text-muted">A confirmation email has been sent to <?php echo e($booking['client_email']); ?></p>
        </div>
    </div>
</div>
