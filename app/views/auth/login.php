<!-- FILE: /app/views/auth/login.php -->
<div class="auth-container">
    <div class="auth-box">
        <h1>Login to SplashBook</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <input type="hidden" name="csrf_token" value="<?php echo e($session->getCsrfToken()); ?>">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required class="form-control">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>

            <div class="form-group">
                <label for="tenant_slug">Business Account (optional, leave empty for platform admin)</label>
                <input type="text" id="tenant_slug" name="tenant_slug" class="form-control" placeholder="your-business-name">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

        <p class="text-center mt-3">
            Don't have an account? <a href="/register">Register</a>
        </p>
    </div>
</div>
