<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='Well-being Monitor'; $activeNav='surveys';
$db=getDB();

$dateFilter=$_GET['date']??date('Y-m-d');
$svStmt=$db->prepare("SELECT sv.*,st.first_name,st.last_name,st.student_number,st.company_name FROM surveys sv JOIN students st ON st.id=sv.student_id WHERE sv.survey_date=? AND st.adviser_id=? ORDER BY sv.created_at DESC");
$svStmt->execute([$dateFilter,$userId]); $surveys=$svStmt->fetchAll();

$moodLabels=['','😞 Very Bad','😕 Bad','😐 Okay','🙂 Good','😄 Great'];
$stressLabels=['','😌 None','🙂 Low','😐 Moderate','😥 High','😰 Very High'];

include __DIR__.'/../components/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">Well-being Monitor</h5><small class="text-muted">Track student daily check-in responses</small></div>
</div>
<div class="card mb-4">
  <div class="card-body py-2">
    <form method="GET" class="d-flex align-items-center gap-2">
      <label class="form-label mb-0 fw-bold" style="font-size:.85rem;white-space:nowrap">Survey Date:</label>
      <input type="date" name="date" class="form-control form-control-sm" style="width:200px" value="<?=htmlspecialchars($dateFilter)?>" onchange="this.form.submit()">
      <span class="text-muted" style="font-size:.82rem"><?=count($surveys)?> responses</span>
    </form>
  </div>
</div>

<?php if(empty($surveys)): ?>
  <div class="card"><div class="card-body"><div class="empty-state"><i class="bi bi-clipboard-x"></i><p>No survey responses for <?=formatDate($dateFilter)?>.</p></div></div></div>
<?php else: ?>
<div class="row g-3">
  <?php foreach($surveys as $sv): ?>
  <div class="col-lg-6">
    <div class="card <?=($sv['q3_needs_consultation']||$sv['q4_company_problem'])?'border-warning':''?>" style="<?=($sv['q3_needs_consultation']||$sv['q4_company_problem'])?'border-width:2px':''?>">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <div style="font-weight:700"><?=htmlspecialchars($sv['first_name'].' '.$sv['last_name'])?></div>
            <div style="font-size:.75rem;color:#6c757d"><?=htmlspecialchars($sv['student_number'])?> • <?=htmlspecialchars($sv['company_name'])?></div>
          </div>
          <div class="d-flex gap-1">
            <?php if($sv['q3_needs_consultation']): ?><span class="badge bg-warning text-dark">Needs Consult</span><?php endif; ?>
            <?php if($sv['q4_company_problem']): ?><span class="badge bg-danger">Company Issue</span><?php endif; ?>
          </div>
        </div>
        <div class="row g-2">
          <div class="col-6">
            <div class="info-label">Mood</div>
            <div style="font-size:.9rem"><?=$moodLabels[$sv['q1_mood']]??'—'?></div>
          </div>
          <div class="col-6">
            <div class="info-label">Stress</div>
            <div style="font-size:.9rem"><?=$stressLabels[$sv['q2_stress']]??'—'?></div>
          </div>
          <div class="col-6">
            <div class="info-label">Experience</div>
            <div>
              <?php for($i=1;$i<=5;$i++): ?><i class="bi bi-star<?=$i<=$sv['q5_experience_rating']?'-fill text-warning':''?>" style="font-size:.85rem"></i><?php endfor; ?>
            </div>
          </div>
          <div class="col-6">
            <div class="info-label">Submitted</div>
            <div style="font-size:.8rem"><?=timeAgo($sv['created_at'])?></div>
          </div>
        </div>
        <?php if($sv['additional_notes']): ?>
          <div class="mt-2 p-2 rounded" style="background:#f8f9fa;font-size:.8rem;color:#636e72"><i class="bi bi-chat-quote me-1"></i><?=htmlspecialchars($sv['additional_notes'])?></div>
        <?php endif; ?>
        <?php if($sv['q3_needs_consultation']||$sv['q4_company_problem']): ?>
          <div class="mt-2">
            <a href="<?=BASE_URL?>/adviser/messages.php?student=<?=$sv['student_id']?>" class="btn btn-warning btn-sm w-100">
              <i class="bi bi-chat me-1"></i>Send Message
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include __DIR__.'/../components/footer.php'; ?>
