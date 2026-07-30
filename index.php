<?php
require __DIR__ . '/includes/auth.php';
header('Location: ' . (isLoggedIn() ? 'dashboard.php' : 'login.php'));
exit;
