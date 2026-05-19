<?php
// ============================================================
// PapeLESS - Header Component
// Usage: include at top of dashboard pages
// Expects: $pageTitle, $activeNav, $userType, $userId
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
$csrf = generateCSRF();
$unreadCount = getUnreadNotificationCount($userType, $userId);
$notifications = getNotifications($userType, $userId, 8);
$user = getCurrentUser();
$initials = strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? '', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= $csrf ?>">
  <title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> | PapeLESS</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <!-- Main CSS -->
  <link href="<?= BASE_URL ?>/assets/css/main.css" rel="stylesheet">
</head>
<body>
<script>window.CSRF_TOKEN = '<?= $csrf ?>';</script>

<div class="dashboard-wrapper">
  <!-- Sidebar Overlay (mobile) -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-name">Pape<span>LESS</span></div>
      <div class="brand-sub">OJT Document Tracking</div>
    </div>

    <div class="sidebar-user">
      <div class="avatar">
        <?php if (!empty($user['profile_photo']) && file_exists(__DIR__ . '/../assets/uploads/' . $user['profile_photo'])): ?>
          <img src="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($user['profile_photo']) ?>" alt="">
        <?php else: ?>
          <?= $initials ?>
        <?php endif; ?>
      </div>
      <div>
        <div class="user-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
        <div class="user-role"><?= ucfirst($userType) ?></div>
      </div>
    </div>

    <div class="sidebar-nav">
      <?php
      $navFile = __DIR__ . "/nav_{$userType}.php";
      if (file_exists($navFile)) include $navFile;
      ?>
    </div>

    <div class="sidebar-bottom">
      <a href="#" class="sidebar-link" id="logoutBtn" style="border-left:none">
        <i class="bi bi-box-arrow-left"></i>
        <span>Logout</span>
      </a>
    </div>
  </nav>
  <!-- /SIDEBAR -->

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <!-- TOPBAR -->
    <header class="topbar">
      <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
      </button>
      <span class="page-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>

      <!-- Notifications -->
      <div class="position-relative">
        <button class="notif-btn" id="notifBtn" aria-label="Notifications">
          <i class="bi bi-bell"></i>
          <?php if ($unreadCount > 0): ?>
            <span class="dot"></span>
          <?php endif; ?>
        </button>

        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-dropdown-header">
            <h6>Notifications <?php if ($unreadCount > 0): ?><span class="badge bg-danger"><?= $unreadCount ?></span><?php endif; ?></h6>
            <a href="<?= BASE_URL ?>/<?= $userType ?>/notifications.php" style="font-size:.78rem;color:var(--primary)">View all</a>
          </div>
          <div style="max-height:360px;overflow-y:auto">
            <?php if (empty($notifications)): ?>
              <div class="p-3 text-center text-muted" style="font-size:.85rem">No notifications</div>
            <?php else: foreach ($notifications as $n): ?>
              <div class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
                <div class="notif-icon <?= $n['type'] ?>">
                  <?php
                  $icons = ['submission'=>'bi-file-earmark-arrow-up','approval'=>'bi-check-circle','revision'=>'bi-pencil','message'=>'bi-chat-dots','announcement'=>'bi-megaphone','survey'=>'bi-clipboard2-heart','system'=>'bi-info-circle'];
                  echo '<i class="bi ' . ($icons[$n['type']] ?? 'bi-bell') . '"></i>';
                  ?>
                </div>
                <div>
                  <div class="notif-title"><?= htmlspecialchars($n['title']) ?></div>
                  <div class="notif-msg"><?= htmlspecialchars(substr($n['message'], 0, 80)) ?>...</div>
                  <div class="notif-time"><?= timeAgo($n['created_at']) ?></div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
          <div class="p-2 border-top text-center">
            <a href="<?= BASE_URL ?>/<?= $userType ?>/notifications.php" class="btn btn-sm btn-outline-secondary w-100" style="font-size:.78rem">See all notifications</a>
          </div>
        </div>
      </div>
      <!-- /Notifications -->

      <!-- Profile dropdown -->
      <div class="dropdown">
        <button class="btn btn-link p-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown">
          <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem">
            <?= $initials ?>
          </div>
          <i class="bi bi-chevron-down text-muted" style="font-size:.7rem"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:10px;min-width:180px">
          <li><a class="dropdown-item" href="<?= BASE_URL ?>/<?= $userType ?>/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="#" id="logoutBtnTop"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
        </ul>
      </div>
    </header>
    <!-- /TOPBAR -->

    <!-- PAGE CONTENT starts here (closed in footer.php) -->
    <main class="page-content">
