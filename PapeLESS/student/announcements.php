<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');
$userId=$_SESSION['user_id']; $userType='student'; $pageTitle='Announcements'; $activeNav='announcements';
$db=getDB();
$anns=$db->prepare("SELECT * FROM announcements WHERE target_audience IN ('all','students') ORDER BY is_pinned DESC, created_at DESC");
$anns->execute(); $announcements=$anns->fetchAll();
include __DIR__.'/../components/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">Announcements</h5><small class="text-muted">Updates from the OJT Coordinator</small></div>
</div>
<?php if(empty($announcements)): ?>
  <div class="card"><div class="card-body"><div class="empty-state"><i class="bi bi-megaphone"></i><p>No announcements yet.</p></div></div></div>
<?php else: foreach($announcements as $a): ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h6 class="fw-bold mb-0">
          <?php if($a['is_pinned']): ?><i class="bi bi-pin-fill text-danger me-1"></i><?php endif; ?>
          <?=htmlspecialchars($a['title'])?>
        </h6>
        <div class="d-flex gap-2 align-items-center">
          <span class="badge bg-light text-muted border"><?=htmlspecialchars($a['target_audience'])?></span>
          <small class="text-muted"><?=formatDateTime($a['created_at'])?></small>
        </div>
      </div>
      <p style="font-size:.88rem;color:#636e72;line-height:1.7;white-space:pre-wrap"><?=htmlspecialchars($a['content'])?></p>
      <?php if($a['attachment'] && $a['attachment_name']): ?>
        <a href="<?=BASE_URL?>/assets/uploads/<?=htmlspecialchars($a['attachment'])?>" class="btn btn-sm btn-outline-danger" target="_blank">
          <i class="bi bi-paperclip me-1"></i><?=htmlspecialchars($a['attachment_name'])?>
        </a>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; endif; ?>
<?php include __DIR__.'/../components/footer.php'; ?>
