<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='My Students'; $activeNav='students';
$db=getDB();

$filter=$_GET['filter']??'all';
$search=$_GET['search']??'';

$sql="SELECT * FROM students WHERE adviser_id=?";
$params=[$userId];
if($filter!=='all'){$sql.=" AND status=?";$params[]=$filter;}
if($search){$sql.=" AND (first_name LIKE ? OR last_name LIKE ? OR student_number LIKE ?)";$s="%$search%";$params[]=$s;$params[]=$s;$params[]=$s;}
$sql.=" ORDER BY last_name, first_name";
$stmt=$db->prepare($sql); $stmt->execute($params); $students=$stmt->fetchAll();

// Counts per status
$counts=['all'=>0,'pending'=>0,'approved'=>0,'rejected'=>0,'completed'=>0];
$cStmt=$db->prepare("SELECT status,COUNT(*) as cnt FROM students WHERE adviser_id=? GROUP BY status"); $cStmt->execute([$userId]);
foreach($cStmt->fetchAll() as $r){$counts['all']+=$r['cnt'];$counts[$r['status']]=$r['cnt'];}

include __DIR__.'/../components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">My Students</h5><small class="text-muted">Manage your assigned OJT students</small></div>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-2 d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <div class="d-flex gap-2 flex-wrap">
      <?php foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','completed'=>'Completed'] as $k=>$label): ?>
        <a href="?filter=<?=$k?>" class="btn btn-sm <?=$filter===$k?'btn-danger':'btn-outline-secondary'?>">
          <?=$label?> <span class="badge bg-light text-dark ms-1"><?=$counts[$k]??0?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <form method="GET" class="search-box" style="width:220px">
      <i class="bi bi-search"></i>
      <input type="hidden" name="filter" value="<?=htmlspecialchars($filter)?>">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search students..." value="<?=htmlspecialchars($search)?>">
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <?php if(empty($students)): ?>
      <div class="empty-state"><i class="bi bi-people"></i><p>No students found.</p></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4">Student</th>
          <th>Program</th>
          <th>Company</th>
          <th>Status</th>
          <th>Registered</th>
          <th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($students as $s): ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-2">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <?=strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1))?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
                  <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['student_number'])?></div>
                </div>
              </div>
            </td>
            <td style="font-size:.83rem"><?=htmlspecialchars($s['program'])?> <?=$s['year_level']?>-<?=$s['section']?></td>
            <td>
              <div style="font-size:.82rem;font-weight:500"><?=htmlspecialchars($s['company_name'])?></div>
              <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['department_role'])?></div>
            </td>
            <td><?=statusBadge($s['status'])?></td>
            <td style="font-size:.78rem;color:#6c757d"><?=formatDate($s['created_at'])?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?=BASE_URL?>/adviser/student_profile.php?id=<?=$s['id']?>" class="btn btn-sm btn-outline-primary" title="View Profile"><i class="bi bi-eye"></i></a>
                <?php if($s['status']==='pending'): ?>
                  <button class="btn btn-sm btn-success approve-btn" data-id="<?=$s['id']?>" title="Approve"><i class="bi bi-check-lg"></i></button>
                  <button class="btn btn-sm btn-outline-danger reject-btn" data-id="<?=$s['id']?>" data-name="<?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?>" title="Reject"><i class="bi bi-x-lg"></i></button>
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

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow" style="border-radius:14px">
      <div class="modal-body p-4">
        <h6 class="fw-bold mb-3">Reject Registration</h6>
        <p class="text-muted" id="rejectStudentName" style="font-size:.85rem"></p>
        <input type="hidden" id="rejectStudentId">
        <textarea id="rejectReason" class="form-control form-control-sm mb-3" rows="3" placeholder="Reason for rejection..."></textarea>
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
    confirmAction('Approve this student?',async()=>{
      const res=await ajaxPost('/PapeLESS/adviser/submission_handler.php',{action:'approve_student',student_id:btn.dataset.id});
      if(res.success){showToast(res.message,'success');location.reload();}else showToast(res.error,'error');
    });
  });
});
document.querySelectorAll('.reject-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('rejectStudentId').value=btn.dataset.id;
    document.getElementById('rejectStudentName').textContent='Student: '+btn.dataset.name;
    document.getElementById('rejectReason').value='';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
  });
});
document.getElementById('doRejectBtn')?.addEventListener('click',async()=>{
  const reason=document.getElementById('rejectReason').value.trim();
  if(!reason){showToast('Reason required.','error');return;}
  const res=await ajaxPost('/PapeLESS/adviser/submission_handler.php',{action:'reject_student',student_id:document.getElementById('rejectStudentId').value,reason});
  if(res.success){showToast(res.message,'success');location.reload();}else showToast(res.error,'error');
});
</script>
<?php include __DIR__.'/../components/footer.php'; ?>
