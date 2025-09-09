<?php
// sidebar.php
?>
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : '' ?>"> 💻 Dashboard</a>
    <a href="dokter.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dokter.php' ? 'active' : '' ?>">👨‍⚕️ Manajemen Dokter</a>
    <a href="spesialis.php" class="<?= basename($_SERVER['PHP_SELF']) == 'spesialis.php' ? 'active' : '' ?>">🩺 Manajemen Spesialisasi</a>
    <a href="alasan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'alasan.php' ? 'active' : '' ?>">💬 Manajemen Feedback</a>
    <a href="report.php" class="<?= basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : '' ?>">📖 Report</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>
