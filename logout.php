<?php
session_start();
session_unset();
session_destroy();

// Redirect ke portal.html atau index.html
$redirect = $_GET['to'] ?? 'portal.html';
if (!in_array($redirect, ['portal.html', 'index.html', 'login.html', 'login_admin.html'])) {
    $redirect = 'portal.html';
}

header("Location: " . $redirect);
exit;
