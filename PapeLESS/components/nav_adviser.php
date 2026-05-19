<?php
$nav = $activeNav ?? '';
$pendingApprovals = 0;
$pendingReviews   = 0;
$unreadMessages   = 0;
try {
    $db = getDB();
    $a = $db->prepare("SELECT COUNT(*) FROM students WHERE adviser_id=? AND status='pending'");
    $a->execute([$userId]);
    $pendingApprovals = (int)$a->fetchColumn();

    $r = $db->prepare("SELECT COUNT(*) FROM submissions s JOIN students st ON st.id=s.student_id WHERE st.adviser_id=? AND s.status='pending'");
    $r->execute([$userId]);
    $pendingReviews = (int)$r->fetchColumn();

    $m = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_type='adviser' AND receiver_id=? AND is_read=0");
    $m->execute([$userId]);
    $unreadMessages = (int)$m->fetchColumn();
} catch (Exception $e) {}
?>
<div class="nav-section-label">Main</div>

<a href="<?= BASE_URL ?>/adviser/dashboard.php" class="sidebar-link <?= $nav==='dashboard'?'active':'' ?>">
  <i class="bi bi-speedometer2"></i>
  <span>Dashboard</span>
</a>

<a href="<?= BASE_URL ?>/adviser/profile.php" class="sidebar-link <?= $nav==='profile'?'active':'' ?>">
  <i class="bi bi-person-circle"></i>
  <span>My Profile</span>
</a>

<div class="nav-section-label">Students</div>

<a href="<?= BASE_URL ?>/adviser/students.php" class="sidebar-link <?= $nav==='students'?'active':'' ?>">
  <i class="bi bi-people"></i>
  <span>My Students</span>
  <?php if ($pendingApprovals > 0): ?>
    <span class="badge-count"><?= $pendingApprovals ?></span>
  <?php endif; ?>
</a>

<a href="<?= BASE_URL ?>/adviser/submissions.php" class="sidebar-link <?= $nav==='submissions'?'active':'' ?>">
  <i class="bi bi-file-earmark-check"></i>
  <span>File Review</span>
  <?php if ($pendingReviews > 0): ?>
    <span class="badge-count"><?= $pendingReviews ?></span>
  <?php endif; ?>
</a>

<a href="<?= BASE_URL ?>/adviser/surveys.php" class="sidebar-link <?= $nav==='surveys'?'active':'' ?>">
  <i class="bi bi-clipboard2-data"></i>
  <span>Well-being Monitor</span>
</a>

<div class="nav-section-label">Communication</div>

<a href="<?= BASE_URL ?>/adviser/messages.php" class="sidebar-link <?= $nav==='messages'?'active':'' ?>">
  <i class="bi bi-chat-dots"></i>
  <span>Messages</span>
  <?php if ($unreadMessages > 0): ?>
    <span class="badge-count"><?= $unreadMessages ?></span>
  <?php endif; ?>
</a>

<a href="<?= BASE_URL ?>/adviser/announcements.php" class="sidebar-link <?= $nav==='announcements'?'active':'' ?>">
  <i class="bi bi-megaphone"></i>
  <span>Announcements</span>
</a>

<div class="nav-section-label">Reports</div>

<a href="<?= BASE_URL ?>/adviser/reports.php" class="sidebar-link <?= $nav==='reports'?'active':'' ?>">
  <i class="bi bi-bar-chart-line"></i>
  <span>Reports</span>
</a>

<a href="<?= BASE_URL ?>/adviser/notifications.php" class="sidebar-link <?= $nav==='notifications'?'active':'' ?>">
  <i class="bi bi-bell"></i>
  <span>Notifications</span>
</a>
