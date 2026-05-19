<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('student');
$userId=$_SESSION['user_id']; $userType='student'; $pageTitle='Guidelines & Templates'; $activeNav='guidelines';
$db=getDB();
$gStmt=$db->query("SELECT * FROM guidelines WHERE is_active=1 ORDER BY sort_order");
$guidelines=$gStmt->fetchAll();
$grouped=[];
foreach($guidelines as $g) $grouped[$g['category']][]=$g;
$catLabels=['procedure'=>'OJT Procedures','template'=>'Downloadable Templates','policy'=>'Policies & Handbooks','other'=>'Other Resources'];
$catIcons=['procedure'=>'bi-list-check','template'=>'bi-file-earmark-text','policy'=>'bi-shield-check','other'=>'bi-folder'];
include __DIR__.'/../components/header.php';
?>
<div class="mb-4">
  <h5 class="fw-bold mb-0">Guidelines & Templates</h5>
  <small class="text-muted">Access OJT procedures, templates, and important documents</small>
</div>
<div class="alert alert-info mb-4" style="border-radius:10px;font-size:.85rem">
  <i class="bi bi-info-circle me-2"></i>
  All templates must be downloaded, completed, and uploaded through the <a href="<?=BASE_URL?>/student/submissions.php" style="color:inherit;font-weight:600">My Submissions</a> page.
</div>
<?php foreach($grouped as $cat=>$items): ?>
<div class="card mb-4">
  <div class="card-header">
    <i class="bi <?=$catIcons[$cat]??'bi-folder'?> me-2 text-danger"></i>
    <?=$catLabels[$cat]??ucfirst($cat)?>
  </div>
  <div class="card-body p-0">
    <?php foreach($items as $item): ?>
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom" style="gap:1rem">
      <div>
        <div style="font-weight:600;font-size:.9rem"><?=htmlspecialchars($item['title'])?></div>
        <?php if($item['description']): ?>
          <div style="font-size:.8rem;color:#6c757d;margin-top:.2rem"><?=htmlspecialchars($item['description'])?></div>
        <?php endif; ?>
      </div>
      <div class="d-flex gap-2 flex-shrink-0">
        <?php if($item['drive_link'] && $item['drive_link']!=='#'): ?>
          <a href="<?=htmlspecialchars($item['drive_link'])?>" target="_blank" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-google me-1"></i>Open in Drive
          </a>
        <?php elseif($item['drive_link']==='#'): ?>
          <button class="btn btn-sm btn-outline-secondary" disabled title="Link not configured">
            <i class="bi bi-link me-1"></i>Coming Soon
          </button>
        <?php endif; ?>
        <?php if($item['file_path']): ?>
          <a href="<?=BASE_URL?>/<?=htmlspecialchars($item['file_path'])?>" download class="btn btn-sm btn-danger">
            <i class="bi bi-download me-1"></i>Download
          </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endforeach; ?>
<?php include __DIR__.'/../components/footer.php'; ?>
