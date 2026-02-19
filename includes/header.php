<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>Lab Allocation System</title>

    <?php
    // Determine assets path based on current directory structure
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $dir_parts = explode('/', str_replace('\\', '/', $script_dir));
    $current_dir = end($dir_parts);

    // List of subdirectories that need to go up one level
    $sub_dirs = ['admin', 'staff', 'student', 'tools', 'includes'];

    if (in_array($current_dir, $sub_dirs)) {
        $assets_base = '../assets';
    } else {
        // Default relative path check as fallback
        $assets_base = file_exists('assets/css/style.css') ? 'assets' : '../assets';
    }
    ?>

    <!-- Bootstrap 5.3 CSS -->
    <link href="<?= $assets_base ?>/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="<?= $assets_base ?>/css/bootstrap-icons.css" rel="stylesheet">

    <!-- CodeMirror CSS (if needed) -->
    <?php if (isset($include_codemirror) && $include_codemirror): ?>
        <link rel="stylesheet" href="<?= $assets_base ?>/codemirror/codemirror.min.css">
        <link rel="stylesheet" href="<?= $assets_base ?>/codemirror/monokai.min.css">
    <?php endif; ?>

    <!-- Custom CSS with Cache Buster -->
    <link href="<?= $assets_base ?>/css/style.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?= $assets_base ?>/css/sidebar.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?= $assets_base ?>/css/topbar.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link href="<?= $assets_base ?>/css/dashboard.css?v=<?php echo time(); ?>" rel="stylesheet">

    <?php if (isset($extra_css)): ?>
        <?= $extra_css ?>
    <?php endif; ?>
</head>

<body>