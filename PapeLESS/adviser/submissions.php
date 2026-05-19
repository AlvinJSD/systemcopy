<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='File Review'; $activeNav='submissions';
$db=getDB();

$filterStatus=$_GET['status']??'pending';
$search=$_GET['search']??'';

$sql="SELECT s.*,r.requirement_name,st.first_name,st.last_name,st.student_number,st.program,st.year_level
      FROM submissions s
      JOIN submission_requirements r ON r.id=s.requirement_id
      JOIN students st ON st.id=s.student_id
      WHERE st.adviser_id=?";
$params=[$userId];
if($filterStatus!=='all'){$sql.=" AND s.status=?";$params[]=$filterStatus;}
if($search){$sql.=" AND (st.first_name LIKE ? OR st.last_name LIKE ? OR r.requirement_name LIKE ?)";$s="%$search%";array_push($params,$s,$s,$s);}
$sql.=" ORDER BY s.submitted_at DESC";
$stmt=$db->prepare($sql); $stmt->execute($params); $submissions=$stmt->fetchAll();

include __DIR__.'/../components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">File Review</h5><small class="text-muted">Review and action student document submissions</small></div>
</div>

<div class="card mb-4">
  <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <div class="d-flex gap-2">
      <?php foreach(['pending'=>'Pending','resubmitted'=>'Resubmitted','approved'=>'Approved','needs_revision'=>'Needs Revision','all'=>'All'] as $k=>$lbl): ?>
        <a href="?status=<?=$k?>" class="btn btn-sm <?=$filterStatus===$k?'btn-danger':'btn-outline-secondary'?>"><?=$lbl?></a>
      <?php endforeach; ?>
    </div>
    <form method="GET" class="search-box" style="width:220px">
      <i class="bi bi-search"></i>
      <input type="hidden" name="status" value="<?=htmlspecialchars($filterStatus)?>">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?=htmlspecialchars($search)?>">
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if(empty($submissions)): ?>
      <div class="empty-state"><i class="bi bi-folder-check"></i><p>No submissions in this category.</p></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4">Student</th>
          <th>Requirement</th>
          <th>File</th>
          <th>Status</th>
          <th>Submitted</th>
          <th>Comment</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($submissions as $s): ?>
          <tr>
            <td class="ps-4">
              <a href="<?=BASE_URL?>/adviser/student_profile.php?id=<?=$s['student_id']?>" style="font-weight:600;font-size:.85rem;color:inherit"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></a>
              <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['student_number'])?> • <?=htmlspecialchars($s['program'])?></div>
            </td>
            <td style="font-size:.83rem;font-weight:500"><?=htmlspecialchars($s['requirement_name'])?></td>
            <td>
              <div class="d-flex align-items-center gap-1">
                <i class="bi <?=getFileIcon($s['file_type'])?>"></i>
                <div>
                  <div style="font-size:.78rem"><?=htmlspecialchars(substr($s['original_name'],0,25))?><?=strlen($s['original_name'])>25?'...':''?></div>
                  <div style="font-size:.68rem;color:#adb5bd"><?=formatFileSize((int)$s['file_size'])?></div>
                </div>
              </div>
            </td>
            <td><?=statusBadge($s['status'])?></td>
            <td style="font-size:.78rem;color:#6c757d"><?=timeAgo($s['submitted_at'])?></td>
            <td style="font-size:.75rem;color:#6c757d;max-width:150px"><?=htmlspecialchars(substr($s['adviser_comment']??'—',0,60))?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?=BASE_URL?>/<?=htmlspecialchars($s['file_path'])?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View File"><i class="bi bi-eye"></i></a>
                <?php if(in_array($s['status'],['pending','resubmitted'])): ?>
                  <button class="btn btn-sm btn-success review-approve-btn" data-id="<?=$s['id']?>" title="Approve"><i class="bi bi-check-lg"></i></button>
                  <button class="btn btn-sm btn-outline-warning review-revise-btn" data-id="<?=$s['id']?>" title="Request Revision"><i class="bi bi-pencil"></i></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius:14px">
      <div class="modal-header border-0 pb-0"><h6 class="fw-bold" id="reviewModalTitle">Review Submission</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="reviewSubId">
        <input type="hidden" id="reviewDecision">
        <label class="form-label fw-bold">Adviser Comment <span id="reqNote" class="text-muted fw-normal">(Optional)</span></label>
        <textarea id="reviewComment" class="form-control" rows="4" placeholder="Add your feedback or reason..."></textarea>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger btn-sm" id="doReviewBtn">Submit</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.review-approve-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('reviewSubId').value=btn.dataset.id;
    document.getElementById('reviewDecision').value='approved';
    document.getElementById('reviewModalTitle').textContent='✅ Approve Submission';
    document.getElementById('reqNote').textContent='(Optional)';
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
  });
});
document.querySelectorAll('.review-revise-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('reviewSubId').value=btn.dataset.id;
    document.getElementById('reviewDecision').value='needs_revision';
    document.getElementById('reviewModalTitle').textContent='📝 Request Revision';
    document.getElementById('reqNote').textContent='(Required)';
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
  });
});
document.getElementById('doReviewBtn')?.addEventListener('click',async()=>{
  const decision=document.getElementById('reviewDecision').value;
  const comment=document.getElementById('reviewComment').value.trim();
  if(decision==='needs_revision'&&!comment){showToast('Please add a comment explaining what needs revision.','error');return;}
  const res=await ajaxPost('/PapeLESS/adviser/submission_handler.php',{action:'review_submission',submission_id:document.getElementById('reviewSubId').value,decision,comment});
  if(res.success){showToast(res.message,'success');bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();setTimeout(()=>location.reload(),800);}
  else showToast(res.error,'error');
});

<?php if(isset($_GET['review'])): ?>
// Auto-open review modal
document.querySelector('.review-approve-btn[data-id="<?=(int)$_GET['review']?>"]')?.click();
<?php endif; ?>
</script>
<?php include __DIR__.'/../components/footer.php'; ?>
