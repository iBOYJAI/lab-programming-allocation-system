<?php

/**
 * Admin Logout
 */

require_once '../config/database.php';
require_once '../includes/security.php';

startSecureSession();
logoutUser();
redirect('../index.php');
