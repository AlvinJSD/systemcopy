<?php
$nav = $activeNav ?? '';
$pendingStudents = 0;
try {
    $db = getDB();
    $s = $db->prepare("SELECT COUNT(*) FROM students WHERE status='pending'");
    $s->execute();
    $pendingStudents = (int)$s->fetchColumn();
} catch (Exception $e) {}
?>
<div class="nav-section-label">Main</div>

<a href="<?= BASE_URL ?>/coordinator/dashboard.php" class="sidebar-link <?= $nav==='dashboard'?'active':'' ?>">
  <i class="bi bi-speedometer2"></i>
  <span>Dashboard</span>
</a>

<a href="<?= BASE_URL ?>/coordinator/profile.php" class="sidebar-link <?= $nav==='profile'?'active':'' ?>">
  <i class="bi bi-person-circle"></i>
  <span>My Profile</span>
</a>

<div class="nav-section-label">Management</div>

<a href="<?= BASE_URL ?>/coordinator/advisers.php" class="sidebar-link <?= $nav==='advisers'?'active':'' ?>">
  <i class="bi bi-person-badge"></i>
  <span>Advisers</span>
</a>

<a href="<?= BASE_URL ?>/coordinator/students.php" class="sidebar-link <?= $nav==='students'?'active':'' ?>">
  <i class="bi bi-people"></i>
  <span>All Students</span>
  <?php if ($pendingStudents > 0): ?>
    <span class="badge-count"><?= $pendingStudents ?></span>
  <?php endif; ?>
</a>

<a href="<?= BASE_URL ?>/coordinator/announcements.php" class="sidebar-link <?= $nav==='announcements'?'active':'' ?>">
  <i class="bi bi-megaphone"></i>
  <span>Announcements</span>
</a>

<div class="nav-section-label">Analytics</div>

<a href="<?= BASE_URL ?>/coordinator/reports.php" class="sidebar-link <?= $nav==='reports'?'active':'' ?>">
  <i class="bi bi-bar-chart-line"></i>
  <span>Reports</span>
</a>

<div class="nav-section-label">System</div>

<a href="<?= BASE_URL ?>/coordinator/settings.php" class="sidebar-link <?= $nav==='settings'?'active':'' ?>">
  <i class="bi bi-gear"></i>
  <span>System Settings</span>
</a>

<a href="<?= BASE_URL ?>/coordinator/notifications.php" class="sidebar-link <?= $nav==='notifications'?'active':'' ?>">
  <i class="bi bi-bell"></i>
  <span>Notifications</span>
</a>
