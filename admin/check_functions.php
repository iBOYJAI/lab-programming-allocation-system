<?php
// Quick diagnostic
require_once '../includes/functions.php';

if (function_exists('displayAlert')) {
    echo "✅ displayAlert() function EXISTS and is loaded correctly!\n";
    echo "Function is defined in: " . (new ReflectionFunction('displayAlert'))->getFileName() . "\n\n";
    echo "If you're still seeing errors, please RESTART APACHE:\n";
    echo "1. Open XAMPP Control Panel\n";
    echo "2. Click 'Stop' on Apache\n";
    echo "3. Wait 2 seconds\n";
    echo "4. Click 'Start' on Apache\n\n";
    echo "This clears PHP's OPcache and loads the updated functions.php file.";
} else {
    echo "❌ displayAlert() function NOT FOUND\n";
    echo "This should not happen. Please check functions.php";
}
