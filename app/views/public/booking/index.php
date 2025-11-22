<!-- FILE: /app/views/public/booking/index.php -->
<div class="booking-page">
    <div class="container">
        <div class="booking-header">
            <?php if (!empty($tenant['logo'])): ?>
                <img src="/storage/uploads/<?php echo e($tenant['logo']); ?>" alt="<?php echo e($tenant['business_name']); ?>" class="business-logo">
            <?php endif; ?>
            <h1><?php echo e($tenant['business_name']); ?></h1>
            <p><?php echo e($tenant['description']); ?></p>
        </div>

        <div class="booking-wizard">
            <div class="wizard-steps">
                <div class="step active" data-step="1">
                    <span class="step-number">1</span>
                    <span class="step-label">Select Service</span>
                </div>
                <div class="step" data-step="2">
                    <span class="step-number">2</span>
                    <span class="step-label">Choose Date & Time</span>
                </div>
                <div class="step" data-step="3">
                    <span class="step-number">3</span>
                    <span class="step-label">Your Information</span>
                </div>
                <div class="step" data-step="4">
                    <span class="step-number">4</span>
                    <span class="step-label">Confirm</span>
                </div>
            </div>

            <!-- Step 1: Select Service -->
            <div class="wizard-content" id="step-1">
                <h2>Select a Service</h2>
                <?php foreach ($servicesGrouped as $categoryName => $services): ?>
                    <div class="service-category">
                        <h3><?php echo e($categoryName); ?></h3>
                        <div class="services-grid">
                            <?php foreach ($services as $service): ?>
                                <div class="service-card" data-service-id="<?php echo e($service['id']); ?>" data-duration="<?php echo e($service['duration_minutes']); ?>">
                                    <h4><?php echo e($service['name']); ?></h4>
                                    <p><?php echo e($service['description']); ?></p>
                                    <div class="service-meta">
                                        <span><?php echo e($service['duration_minutes']); ?> min</span>
                                        <span>$<?php echo e(number_format($service['base_price'], 2)); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Step 2: Date & Time -->
            <div class="wizard-content hidden" id="step-2">
                <h2>Choose Date & Time</h2>
                <div class="date-time-selector">
                    <input type="date" id="booking-date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                    <div id="time-slots" class="time-slots"></div>
                </div>
            </div>

            <!-- Step 3: Client Info -->
            <div class="wizard-content hidden" id="step-3">
                <h2>Your Information</h2>
                <form id="client-form">
                    <div class="form-group">
                        <label for="client_name">Name *</label>
                        <input type="text" id="client_name" name="client_name" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="client_email">Email *</label>
                        <input type="email" id="client_email" name="client_email" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="client_phone">Phone</label>
                        <input type="tel" id="client_phone" name="client_phone" class="form-control">
                    </div>
                </form>
            </div>

            <!-- Step 4: Confirmation -->
            <div class="wizard-content hidden" id="step-4">
                <h2>Confirm Your Booking</h2>
                <div id="booking-summary"></div>
                <button id="confirm-booking" class="btn btn-primary btn-lg">Confirm Booking</button>
            </div>

            <!-- Navigation -->
            <div class="wizard-navigation">
                <button id="btn-prev" class="btn btn-secondary hidden">Previous</button>
                <button id="btn-next" class="btn btn-primary hidden">Next</button>
            </div>
        </div>
    </div>
</div>

<script>
// Public booking logic
const bookingSlug = '<?php echo e($tenant['slug']); ?>';
let selectedService = null;
let selectedDate = null;
let selectedTime = null;
let selectedStaff = null;
let currentStep = 1;

// Service selection
document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('click', function() {
        document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        selectedService = this.dataset.serviceId;
        document.getElementById('btn-next').classList.remove('hidden');
    });
});

// Navigation
document.getElementById('btn-next').addEventListener('click', function() {
    if (currentStep < 4) {
        currentStep++;
        showStep(currentStep);
        if (currentStep === 2) loadAvailability();
    }
});

document.getElementById('btn-prev').addEventListener('click', function() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
});

function showStep(step) {
    document.querySelectorAll('.wizard-content').forEach(content => content.classList.add('hidden'));
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));

    document.getElementById('step-' + step).classList.remove('hidden');
    document.querySelector('[data-step="' + step + '"]').classList.add('active');

    document.getElementById('btn-prev').classList.toggle('hidden', step === 1);
    document.getElementById('btn-next').classList.toggle('hidden', step === 4);
}

function loadAvailability() {
    const dateInput = document.getElementById('booking-date');
    dateInput.addEventListener('change', function() {
        selectedDate = this.value;
        fetchTimeSlots();
    });
}

function fetchTimeSlots() {
    fetch(`/book/${bookingSlug}/availability`, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `service_id=${selectedService}&date=${selectedDate}`
    })
    .then(r => r.json())
    .then(data => {
        const container = document.getElementById('time-slots');
        container.innerHTML = '';

        if (data.staffSlots && data.staffSlots.length > 0) {
            data.staffSlots.forEach(staffData => {
                staffData.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.className = 'time-slot-btn';
                    btn.textContent = slot.display_time;
                    btn.onclick = () => selectTimeSlot(slot.start_time, staffData.staff_id);
                    container.appendChild(btn);
                });
            });
        }
    });
}

function selectTimeSlot(time, staffId) {
    selectedTime = time;
    selectedStaff = staffId;
    document.querySelectorAll('.time-slot-btn').forEach(btn => btn.classList.remove('selected'));
    event.target.classList.add('selected');
    document.getElementById('btn-next').classList.remove('hidden');
}

// Confirm booking
document.getElementById('confirm-booking').addEventListener('click', function() {
    const formData = {
        service_id: selectedService,
        staff_id: selectedStaff,
        date: selectedDate,
        start_time: selectedTime,
        client_name: document.getElementById('client_name').value,
        client_email: document.getElementById('client_email').value,
        client_phone: document.getElementById('client_phone').value
    };

    fetch(`/book/${bookingSlug}/create`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(formData)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = `/book/${bookingSlug}/confirmation/${data.reference}`;
        }
    });
});
</script>
