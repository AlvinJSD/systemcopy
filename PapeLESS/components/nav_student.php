<?php
// Student sidebar nav
// $activeNav is set by the calling page
$nav = $activeNav ?? '';

$pendingSubmissions = 0;
$unreadMessages = 0;
try {
    $db = getDB();
    $s = $db->prepare("SELECT COUNT(*) FROM submissions WHERE student_id = ? AND status = 'pending'");
    $s->execute([$userId]);
    $pendingSubmissions = (int)$s->fetchColumn();

    $m = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_type='student' AND receiver_id=? AND is_read=0");
    $m->execute([$userId]);
    $unreadMessages = (int)$m->fetchColumn();
} catch (Exception $e) {}
?>
<div class="nav-section-label">Main</div>

<a href="<?= BASE_URL ?>/student/dashboard.php" class="sidebar-link <?= $nav==='dashboard'?'active':'' ?>">
  <i class="bi bi-speedometer2"></i>
  <span>Dashboard</span>
</a>

<a href="<?= BASE_URL ?>/student/profile.php" class="sidebar-link <?= $nav==='profile'?'active':'' ?>">
  <i class="bi bi-person-circle"></i>
  <span>My Profile</span>
</a>

<div class="nav-section-label">Documents</div>

<a href="<?= BASE_URL ?>/student/submissions.php" class="sidebar-link <?= $nav==='submissions'?'active':'' ?>">
  <i class="bi bi-folder2-open"></i>
  <span>My Submissions</span>
  <?php if ($pendingSubmissions > 0): ?>
    <span class="badge-count"><?= $pendingSubmissions ?></span>
  <?php endif; ?>
</a>

<a href="<?= BASE_URL ?>/student/guidelines.php" class="sidebar-link <?= $nav==='guidelines'?'active':'' ?>">
  <i class="bi bi-book"></i>
  <span>Guidelines & Templates</span>
</a>

<div class="nav-section-label">Communication</div>

<a href="<?= BASE_URL ?>/student/messages.php" class="sidebar-link <?= $nav==='messages'?'active':'' ?>">
  <i class="bi bi-chat-dots"></i>
  <span>Messages</span>
  <?php if ($unreadMessages > 0): ?>
    <span class="badge-count"><?= $unreadMessages ?></span>
  <?php endif; ?>
</a>

<a href="<?= BASE_URL ?>/student/announcements.php" class="sidebar-link <?= $nav==='announcements'?'active':'' ?>">
  <i class="bi bi-megaphone"></i>
  <span>Announcements</span>
</a>

<div class="nav-section-label">Well-being</div>

<a href="<?= BASE_URL ?>/student/survey.php" class="sidebar-link <?= $nav==='survey'?'active':'' ?>">
  <i class="bi bi-clipboard2-heart"></i>
  <span>Daily Survey</span>
</a>

<a href="<?= BASE_URL ?>/student/notifications.php" class="sidebar-link <?= $nav==='notifications'?'active':'' ?>">
  <i class="bi bi-bell"></i>
  <span>Notifications</span>
</a>
