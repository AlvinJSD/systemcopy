<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('student');
$userId=$_SESSION['user_id']; $userType='student'; $pageTitle='Notifications'; $activeNav='notifications';
$db=getDB();
$notifs=$db->prepare("SELECT * FROM notifications WHERE user_type='student' AND user_id=? ORDER BY created_at DESC LIMIT 50");
$notifs->execute([$userId]); $notifications=$notifs->fetchAll();
markNotificationsRead('student',$userId);
$icons=['submission'=>'bi-file-earmark-arrow-up','approval'=>'bi-check-circle','revision'=>'bi-pencil','message'=>'bi-chat-dots','announcement'=>'bi-megaphone','survey'=>'bi-clipboard2-heart','system'=>'bi-info-circle'];
include __DIR__.'/../components/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">Notifications</h5><small class="text-muted">All system notifications</small></div>
</div>
<div class="card">
  <div class="card-body p-0">
    <?php if(empty($notifications)): ?>
      <div class="empty-state"><i class="bi bi-bell-slash"></i><p>No notifications yet.</p></div>
    <?php else: foreach($notifications as $n): ?>
      <div class="notif-item border-bottom <?=$n['is_read']?'':'unread'?>" style="padding:1rem 1.5rem">
        <div class="notif-icon <?=$n['type']?>">
          <i class="bi <?=$icons[$n['type']]??'bi-bell'?>"></i>
        </div>
        <div style="flex:1">
          <div class="notif-title"><?=htmlspecialchars($n['title'])?></div>
          <div class="notif-msg"><?=htmlspecialchars($n['message'])?></div>
          <div class="notif-time"><?=formatDateTime($n['created_at'])?></div>
        </div>
        <?php if(!$n['is_read']): ?><div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:6px"></div><?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php include __DIR__.'/../components/footer.php'; ?>
