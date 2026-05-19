<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');
$userId=$_SESSION['user_id']; $userType='coordinator'; $pageTitle='All Students'; $activeNav='students';
$db=getDB();

$statusFilter=$_GET['status']??'all';
$programFilter=$_GET['program']??'';
$search=$_GET['search']??'';

$sql="SELECT s.*,a.first_name as adv_fn,a.last_name as adv_ln,a.program as adv_program,
    (SELECT COUNT(*) FROM submissions sub WHERE sub.student_id=s.id AND sub.status='approved') as approved_docs
    FROM students s LEFT JOIN advisers a ON a.id=s.adviser_id WHERE 1";
$params=[];
if($statusFilter!=='all'){$sql.=" AND s.status=?";$params[]=$statusFilter;}
if($programFilter){$sql.=" AND s.program=?";$params[]=$programFilter;}
if($search){$sql.=" AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_number LIKE ? OR s.company_name LIKE ?)";$s="%$search%";array_push($params,$s,$s,$s,$s);}
$sql.=" ORDER BY s.created_at DESC";
$stmt=$db->prepare($sql); $stmt->execute($params); $students=$stmt->fetchAll();

// Status counts
$cntStmt=$db->query("SELECT status,COUNT(*) as cnt FROM students GROUP BY status");
$counts=['all'=>0]; foreach($cntStmt->fetchAll() as $r){$counts['all']+=$r['cnt'];$counts[$r['status']]=$r['cnt'];}

// Programs list
$programs=$db->query("SELECT DISTINCT program FROM students ORDER BY program")->fetchAll(PDO::FETCH_COLUMN);
$totalReqs=$db->query("SELECT COUNT(*) FROM submission_requirements WHERE is_active=1")->fetchColumn();

include __DIR__.'/../components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">All Students</h5><small class="text-muted">System-wide student overview</small></div>
  <span class="badge bg-danger fs-6"><?=$counts['all']?> total</span>
</div>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body py-2">
    <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
      <div class="d-flex gap-1 flex-wrap">
        <?php foreach(['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','completed'=>'Completed'] as $k=>$lbl): ?>
          <a href="?status=<?=$k?>&program=<?=urlencode($programFilter)?>&search=<?=urlencode($search)?>"
             class="btn btn-sm <?=$statusFilter===$k?'btn-danger':'btn-outline-secondary'?>">
            <?=$lbl?> <span class="badge bg-light text-dark ms-1"><?=$counts[$k]??0?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="ms-auto d-flex gap-2">
        <select name="program" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
          <option value="">All Programs</option>
          <?php foreach($programs as $p): ?>
            <option value="<?=htmlspecialchars($p)?>" <?=$programFilter===$p?'selected':''?>><?=htmlspecialchars($p)?></option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" name="status" value="<?=htmlspecialchars($statusFilter)?>">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?=htmlspecialchars($search)?>" style="width:200px">
        </div>
      </div>
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
          <th>Adviser</th>
          <th>Progress</th>
          <th>Status</th>
          <th>Registered</th>
        </tr></thead>
        <tbody>
        <?php foreach($students as $s): ?>
          <?php $prog=$totalReqs>0?round($s['approved_docs']/$totalReqs*100):0; ?>
          <tr>
            <td class="ps-4">
              <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
              <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['student_number']).' • '.htmlspecialchars($s['email'])?></div>
            </td>
            <td style="font-size:.82rem"><?=htmlspecialchars($s['program'])?> <?=$s['year_level']?>-<?=$s['section']?></td>
            <td>
              <div style="font-size:.82rem;font-weight:500"><?=htmlspecialchars($s['company_name'])?></div>
              <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['department_role'])?></div>
            </td>
            <td style="font-size:.82rem">
              <?php if($s['adv_fn']): ?>
                <?=htmlspecialchars($s['adv_fn'].' '.$s['adv_ln'])?>
                <div style="font-size:.7rem;color:#adb5bd"><?=htmlspecialchars($s['adv_program'])?></div>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Unassigned</span>
              <?php endif; ?>
            </td>
            <td style="min-width:110px">
              <div class="d-flex align-items-center gap-1">
                <div class="progress flex-fill" style="height:5px"><div class="progress-bar" style="width:<?=$prog?>%"></div></div>
                <span style="font-size:.72rem;color:var(--primary);font-weight:600;white-space:nowrap"><?=$prog?>%</span>
              </div>
              <div style="font-size:.68rem;color:#adb5bd"><?=$s['approved_docs']?>/<?=$totalReqs?> docs</div>
            </td>
            <td><?=statusBadge($s['status'])?></td>
            <td style="font-size:.78rem;color:#6c757d"><?=formatDate($s['created_at'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__.'/../components/footer.php'; ?>
