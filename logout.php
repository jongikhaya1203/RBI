<?php
/**
 * Logout - RBI Engineering Suite
 */
require_once __DIR__ . '/config/app.php';
$auth = new Auth();
$auth->logout();
redirect(BASE_URL . '/index.php');
