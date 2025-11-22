<!-- FILE: /app/views/bookings/create.php -->
<h1>Create Booking</h1>

<div class="card">
    <form method="POST" action="/bookings/store">
        <input type="hidden" name="csrf_token" value="<?php echo e($session->getCsrfToken()); ?>">

        <div class="form-group">
            <label for="service_id">Service *</label>
            <select name="service_id" id="service_id" class="form-control" required>
                <option value="">Select Service</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo e($service['id']); ?>" data-duration="<?php echo e($service['duration_minutes']); ?>">
                        <?php echo e($service['name']); ?> (<?php echo e($service['duration_minutes']); ?> min - $<?php echo e($service['base_price']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="staff_id">Staff Member</label>
            <select name="staff_id" id="staff_id" class="form-control">
                <option value="">Any Available</option>
                <?php foreach ($staff as $member): ?>
                    <option value="<?php echo e($member['id']); ?>">
                        <?php echo e($member['first_name'] . ' ' . $member['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="booking_date">Date *</label>
                <input type="date" name="booking_date" id="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group">
                <label for="start_time">Start Time *</label>
                <input type="time" name="start_time" id="start_time" class="form-control" required>
            </div>
        </div>

        <h3>Client Information</h3>

        <div class="form-group">
            <label for="client_id">Existing Client</label>
            <select name="client_id" id="client_id" class="form-control">
                <option value="">New Client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo e($client['id']); ?>">
                        <?php echo e($client['first_name'] . ' ' . $client['last_name']); ?> - <?php echo e($client['email']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="client_name">Name *</label>
            <input type="text" name="client_name" id="client_name" class="form-control" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="client_email">Email</label>
                <input type="email" name="client_email" id="client_email" class="form-control">
            </div>

            <div class="form-group">
                <label for="client_phone">Phone</label>
                <input type="tel" name="client_phone" id="client_phone" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="internal_notes">Internal Notes</label>
            <textarea name="internal_notes" id="internal_notes" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Create Booking</button>
            <a href="/bookings" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
