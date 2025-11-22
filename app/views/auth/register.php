<!-- FILE: /app/views/auth/register.php -->
<div class="auth-container">
    <div class="auth-box">
        <h1>Create Your SplashBook Account</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="/register">
            <input type="hidden" name="csrf_token" value="<?php echo e($session->getCsrfToken()); ?>">

            <div class="form-group">
                <label for="business_name">Business Name *</label>
                <input type="text" id="business_name" name="business_name" required class="form-control" value="<?php echo isset($old['business_name']) ? e($old['business_name']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required class="form-control" value="<?php echo isset($old['first_name']) ? e($old['first_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required class="form-control" value="<?php echo isset($old['last_name']) ? e($old['last_name']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required class="form-control" value="<?php echo isset($old['email']) ? e($old['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo isset($old['phone']) ? e($old['phone']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required class="form-control" minlength="8">
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required class="form-control">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Create Account</button>
        </form>

        <p class="text-center mt-3">
            Already have an account? <a href="/login">Login</a>
        </p>
    </div>
</div>
