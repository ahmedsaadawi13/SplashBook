<!-- FILE: /app/views/layouts/layout.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? e($title) . ' - ' : ''; ?>SplashBook</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <div class="wrapper">
        <!-- Navigation -->
        <nav class="navbar">
            <div class="container">
                <div class="navbar-brand">
                    <a href="/dashboard">SplashBook</a>
                </div>
                <ul class="navbar-nav">
                    <?php if ($session->has('user_id')): ?>
                        <?php $user = $session->get('user'); ?>

                        <?php if ($user['role'] !== 'platform_admin'): ?>
                            <li><a href="/dashboard">Dashboard</a></li>
                            <li><a href="/calendar">Calendar</a></li>
                            <li><a href="/bookings">Bookings</a></li>
                            <li><a href="/clients">Clients</a></li>
                            <li><a href="/staff">Staff</a></li>
                            <li><a href="/services">Services</a></li>

                            <?php if ($user['role'] === 'tenant_admin'): ?>
                                <li><a href="/settings">Settings</a></li>
                                <li><a href="/subscription">Subscription</a></li>
                            <?php endif; ?>
                        <?php else: ?>
                            <li><a href="/admin/tenants">Tenants</a></li>
                            <li><a href="/admin/plans">Plans</a></li>
                        <?php endif; ?>

                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">
                                <?php echo e($user['first_name'] . ' ' . $user['last_name']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="/logout">Logout</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container">
                <!-- Flash Messages -->
                <?php if ($session->hasFlash('success')): ?>
                    <div class="alert alert-success">
                        <?php echo e($session->getFlash('success')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($session->hasFlash('error')): ?>
                    <div class="alert alert-error">
                        <?php echo e($session->getFlash('error')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($session->hasFlash('warning')): ?>
                    <div class="alert alert-warning">
                        <?php echo e($session->getFlash('warning')); ?>
                    </div>
                <?php endif; ?>

                <!-- Page Content -->
                <?php echo $content; ?>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> SplashBook. All rights reserved.</p>
            </div>
        </footer>
    </div>

    <script src="<?php echo asset('js/app.js'); ?>"></script>
</body>
</html>
