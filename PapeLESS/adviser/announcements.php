<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='Announcements'; $activeNav='announcements';
$db=getDB(); $user=getCurrentUser();
$anns=$db->prepare("SELECT * FROM announcements WHERE target_audience IN ('all','advisers') ORDER BY is_pinned DESC, created_at DESC");
$anns->execute(); $announcements=$anns->fetchAll();
include __DIR__.'/../components/header.php';
?>
<div class="mb-4"><h5 class="fw-bold mb-0">Announcements</h5><small class="text-muted">Messages from the OJT Coordinator</small></div>
<?php if(empty($announcements)): ?><div class="card"><div class="card-body"><div class="empty-state"><i class="bi bi-megaphone"></i><p>No announcements.</p></div></div></div>
<?php else: foreach($announcements as $a): ?>
<div class="card mb-3"><div class="card-body">
  <div class="d-flex justify-content-between align-items-start mb-2">
    <h6 class="fw-bold mb-0"><?=htmlspecialchars($a['title'])?><?php if($a['is_pinned']): ?> <i class="bi bi-pin-fill text-danger ms-1"></i><?php endif; ?></h6>
    <small class="text-muted"><?=formatDateTime($a['created_at'])?></small>
  </div>
  <p style="font-size:.88rem;color:#636e72;white-space:pre-wrap;margin:0"><?=htmlspecialchars($a['content'])?></p>
  <?php if($a['attachment']): ?><a href="<?=BASE_URL?>/assets/uploads/<?=htmlspecialchars($a['attachment'])?>" class="btn btn-sm btn-outline-danger mt-2" target="_blank"><i class="bi bi-paperclip me-1"></i><?=htmlspecialchars($a['attachment_name']??'Attachment')?></a><?php endif; ?>
</div></div>
<?php endforeach; endif; ?>
<?php include __DIR__.'/../components/footer.php'; ?>
