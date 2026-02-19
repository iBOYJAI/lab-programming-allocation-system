    <?php
    // Determine assets path
    $assets_base = file_exists('assets/css/style.css') ? 'assets' : '../assets';
    ?>

    <!-- jQuery -->
    <script src="<?= $assets_base ?>/js/jquery.min.js"></script>

    <!-- Bootstrap 5.3 JS -->
    <script src="<?= $assets_base ?>/js/bootstrap.bundle.min.js"></script>

    <!-- Chart.js (if needed) -->
    <?php if (isset($include_charts) && $include_charts): ?>
        <script src="<?= $assets_base ?>/js/chart.min.js"></script>
    <?php endif; ?>

    <!-- CodeMirror JS (if needed) -->
    <?php if (isset($include_codemirror) && $include_codemirror): ?>
        <script src="<?= $assets_base ?>/codemirror/codemirror.min.js"></script>
        <script src="<?= $assets_base ?>/codemirror/clike.min.js"></script>
        <script src="<?= $assets_base ?>/codemirror/python.min.js"></script>
    <?php endif; ?>

    <!-- Custom JS -->
    <script src="<?= $assets_base ?>/js/custom.js"></script>

    <?php if (isset($extra_js)): ?>
        <?= $extra_js ?>
    <?php endif; ?>
    </body>

    </html>