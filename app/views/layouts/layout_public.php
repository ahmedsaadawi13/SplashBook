<!-- FILE: /app/views/layouts/layout_public.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? e($title) . ' - ' : ''; ?>SplashBook</title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body class="public-page">
    <div class="wrapper">
        <!-- Public Navigation -->
        <nav class="navbar navbar-public">
            <div class="container">
                <div class="navbar-brand">
                    <a href="/">SplashBook</a>
                </div>
                <ul class="navbar-nav">
                    <li><a href="/login">Login</a></li>
                    <li><a href="/register" class="btn btn-primary">Get Started</a></li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <?php echo $content; ?>
        </main>

        <!-- Footer -->
        <footer class="footer footer-public">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> SplashBook. Modern appointment booking for service businesses.</p>
            </div>
        </footer>
    </div>

    <script src="<?php echo asset('js/app.js'); ?>"></script>
</body>
</html>
