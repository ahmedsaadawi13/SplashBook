<!-- FILE: /app/views/home/index.php -->
<div class="hero">
    <div class="container">
        <h1>Modern Appointment Booking for Service Businesses</h1>
        <p class="lead">Streamline your scheduling, delight your clients, and grow your business with SplashBook.</p>
        <a href="/register" class="btn btn-primary btn-lg">Get Started Free</a>
        <a href="/login" class="btn btn-secondary btn-lg">Login</a>
    </div>
</div>

<div class="features">
    <div class="container">
        <h2>Everything You Need to Manage Bookings</h2>
        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon">📅</div>
                <h3>Smart Scheduling</h3>
                <p>Automated availability, conflict detection, and staff assignment</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🌐</div>
                <h3>Online Booking</h3>
                <p>Let clients book 24/7 through your custom booking page</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📧</div>
                <h3>Automatic Reminders</h3>
                <p>Reduce no-shows with email notifications and reminders</p>
            </div>
            <div class="feature">
                <div class="feature-icon">👥</div>
                <h3>Multi-Staff Support</h3>
                <p>Manage multiple staff members with individual schedules</p>
            </div>
            <div class="feature">
                <div class="feature-icon">📊</div>
                <h3>Analytics</h3>
                <p>Track bookings, revenue, and client trends</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🔌</div>
                <h3>API Access</h3>
                <p>Integrate with your website and other tools</p>
            </div>
        </div>
    </div>
</div>

<div class="pricing">
    <div class="container">
        <h2>Simple, Transparent Pricing</h2>
        <div class="pricing-grid">
            <?php foreach ($plans as $plan): ?>
                <div class="pricing-card">
                    <h3><?php echo e($plan['name']); ?></h3>
                    <div class="price">
                        <span class="amount">$<?php echo e(number_format($plan['price_monthly'], 2)); ?></span>
                        <span class="period">/month</span>
                    </div>
                    <p><?php echo e($plan['description']); ?></p>
                    <ul class="features-list">
                        <li>Up to <?php echo e($plan['max_staff']); ?> staff members</li>
                        <li>Up to <?php echo e($plan['max_services']); ?> services</li>
                        <li><?php echo e($plan['max_bookings_per_month']); ?> bookings/month</li>
                    </ul>
                    <a href="/register" class="btn btn-primary">Get Started</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
