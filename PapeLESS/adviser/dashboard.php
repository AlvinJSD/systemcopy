<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='Dashboard'; $activeNav='dashboard';
$db=getDB();

// Stats
$total=$db->prepare("SELECT COUNT(*) FROM students WHERE adviser_id=?"); $total->execute([$userId]); $totalStudents=(int)$total->fetchColumn();
$pending=$db->prepare("SELECT COUNT(*) FROM students WHERE adviser_id=? AND status='pending'"); $pending->execute([$userId]); $pendingApprovals=(int)$pending->fetchColumn();
$subPending=$db->prepare("SELECT COUNT(*) FROM submissions s JOIN students st ON st.id=s.student_id WHERE st.adviser_id=? AND s.status='pending'"); $subPending->execute([$userId]); $pendingSubs=(int)$subPending->fetchColumn();
$needConsult=$db->prepare("SELECT COUNT(DISTINCT student_id) FROM surveys WHERE survey_date=CURDATE() AND (q3_needs_consultation=1 OR q4_company_problem=1) AND student_id IN (SELECT id FROM students WHERE adviser_id=?)"); $needConsult->execute([$userId]); $consultCount=(int)$needConsult->fetchColumn();

// Pending approvals list
$pendingList=$db->prepare("SELECT * FROM students WHERE adviser_id=? AND status='pending' ORDER BY created_at DESC LIMIT 5"); $pendingList->execute([$userId]); $pendingStudents=$pendingList->fetchAll();

// Recent submissions needing review
$recentSubs=$db->prepare("SELECT s.*,r.requirement_name,st.first_name,st.last_name,st.student_number FROM submissions s JOIN submission_requirements r ON r.id=s.requirement_id JOIN students st ON st.id=s.student_id WHERE st.adviser_id=? AND s.status='pending' ORDER BY s.submitted_at DESC LIMIT 8"); $recentSubs->execute([$userId]); $pendingSubmissions=$recentSubs->fetchAll();

// Today's survey alerts
$alerts=$db->prepare("SELECT sv.*,st.first_name,st.last_name FROM surveys sv JOIN students st ON st.id=sv.student_id WHERE sv.survey_date=CURDATE() AND (sv.q3_needs_consultation=1 OR sv.q4_company_problem=1) AND st.adviser_id=?"); $alerts->execute([$userId]); $surveyAlerts=$alerts->fetchAll();

include __DIR__.'/../components/header.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card blue">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div><div class="stat-value"><?=$totalStudents?></div><div class="stat-label">My Students</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card orange">
      <div class="stat-icon"><i class="bi bi-person-check"></i></div>
      <div><div class="stat-value"><?=$pendingApprovals?></div><div class="stat-label">Pending Approval</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card red">
      <div class="stat-icon"><i class="bi bi-file-earmark-check"></i></div>
      <div><div class="stat-value"><?=$pendingSubs?></div><div class="stat-label">Files to Review</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card purple">
      <div class="stat-icon"><i class="bi bi-heart-pulse"></i></div>
      <div><div class="stat-value"><?=$consultCount?></div><div class="stat-label">Need Attention</div></div>
    </div>
  </div>
</div>

<?php if($consultCount>0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" style="border-radius:10px;border-left:4px solid #f39c12">
  <i class="bi bi-exclamation-triangle-fill fs-5"></i>
  <div><strong><?=$consultCount?> student(s)</strong> flagged concerns in today's well-being check-in. <a href="<?=BASE_URL?>/adviser/surveys.php" style="color:inherit;font-weight:700">View now →</a></div>
</div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <!-- Pending Submissions -->
    <div class="card mb-4">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-file-earmark-check me-2 text-danger"></i>Files Awaiting Review</span>
        <a href="<?=BASE_URL?>/adviser/submissions.php" class="btn btn-sm btn-outline-danger">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($pendingSubmissions)): ?>
          <div class="empty-state"><i class="bi bi-folder-check"></i><p>No pending submissions. All caught up!</p></div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead><tr><th class="ps-3">Student</th><th>Document</th><th>Date</th><th>Action</th></tr></thead>
              <tbody>
              <?php foreach($pendingSubmissions as $s): ?>
                <tr>
                  <td class="ps-3">
                    <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
                    <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['student_number'])?></div>
                  </td>
                  <td>
                    <div style="font-size:.82rem"><?=htmlspecialchars($s['requirement_name'])?></div>
                    <div style="font-size:.72rem;color:#6c757d"><i class="bi <?=getFileIcon($s['file_type'])?>"></i> <?=htmlspecialchars($s['original_name'])?></div>
                  </td>
                  <td style="font-size:.78rem;color:#6c757d"><?=timeAgo($s['submitted_at'])?></td>
                  <td>
                    <a href="<?=BASE_URL?>/adviser/submissions.php?review=<?=$s['id']?>" class="btn btn-sm btn-danger">Review</a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Survey Alerts -->
    <?php if(!empty($surveyAlerts)): ?>
    <div class="card">
      <div class="card-header"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Today's Well-being Alerts</div>
      <div class="card-body p-0">
        <?php foreach($surveyAlerts as $a): ?>
          <div class="d-flex align-items-center gap-3 p-3 border-bottom">
            <div style="width:36px;height:36px;border-radius:50%;background:#fff3cd;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0">⚠️</div>
            <div style="flex:1">
              <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?></div>
              <div style="font-size:.77rem;color:#6c757d">
                <?php if($a['q3_needs_consultation']): ?><span class="badge bg-warning text-dark me-1">Needs Consultation</span><?php endif; ?>
                <?php if($a['q4_company_problem']): ?><span class="badge bg-danger me-1">Company Problem</span><?php endif; ?>
              </div>
            </div>
            <a href="<?=BASE_URL?>/adviser/messages.php" class="btn btn-sm btn-outline-warning">Message</a>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <!-- Pending Approvals -->
    <div class="card mb-4">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-person-check me-2 text-danger"></i>Pending Approvals</span>
        <a href="<?=BASE_URL?>/adviser/students.php?filter=pending" class="btn btn-sm btn-outline-danger">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($pendingStudents)): ?>
          <div class="empty-state py-3"><i class="bi bi-person-check fs-3 d-block mb-2"></i><p style="font-size:.85rem">No pending approvals</p></div>
        <?php else: foreach($pendingStudents as $s): ?>
          <div class="d-flex align-items-center gap-3 p-3 border-bottom">
            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <?=strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1))?>
            </div>
            <div style="flex:1;min-width:0">
              <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
              <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['program'])?> — <?=$s['year_level']?>-<?=$s['section']?></div>
              <div style="font-size:.7rem;color:#adb5bd"><?=timeAgo($s['created_at'])?></div>
            </div>
            <div class="d-flex gap-1">
              <button class="btn btn-sm btn-success approve-btn" data-id="<?=$s['id']?>" title="Approve"><i class="bi bi-check-lg"></i></button>
              <button class="btn btn-sm btn-outline-danger reject-btn" data-id="<?=$s['id']?>" data-name="<?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?>" title="Reject"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow" style="border-radius:14px">
      <div class="modal-body p-4">
        <h6 class="fw-bold mb-3">Reject Registration</h6>
        <p style="font-size:.85rem;color:#6c757d" id="rejectStudentName"></p>
        <input type="hidden" id="rejectStudentId">
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size:.82rem">Reason for rejection <span class="text-danger">*</span></label>
          <textarea id="rejectReason" class="form-control form-control-sm" rows="3" placeholder="Provide a reason..."></textarea>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-danger btn-sm flex-fill" id="doRejectBtn">Reject</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.approve-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    confirmAction('Approve this student registration?', async ()=>{
      const res=await ajaxPost('/PapeLESS/adviser/submission_handler.php',{action:'approve_student',student_id:btn.dataset.id});
      if(res.success){showToast(res.message,'success');location.reload();}else showToast(res.error,'error');
    });
  });
});
document.querySelectorAll('.reject-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('rejectStudentId').value=btn.dataset.id;
    document.getElementById('rejectStudentName').textContent='Student: '+btn.dataset.name;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
  });
});
document.getElementById('doRejectBtn')?.addEventListener('click', async ()=>{
  const reason=document.getElementById('rejectReason').value.trim();
  if(!reason){showToast('Please provide a reason.','error');return;}
  const res=await ajaxPost('/PapeLESS/adviser/submission_handler.php',{action:'reject_student',student_id:document.getElementById('rejectStudentId').value,reason});
  if(res.success){showToast(res.message,'success');location.reload();}else showToast(res.error,'error');
});
</script>

<?php include __DIR__.'/../components/footer.php'; ?>
