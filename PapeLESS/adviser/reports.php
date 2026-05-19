<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='Reports'; $activeNav='reports';
$db=getDB();

$students=$db->prepare("SELECT s.*,
    (SELECT COUNT(*) FROM submissions sub WHERE sub.student_id=s.id AND sub.status='approved') as approved_count,
    (SELECT COUNT(*) FROM submissions sub WHERE sub.student_id=s.id AND sub.status='pending') as pending_count,
    (SELECT COUNT(*) FROM submissions sub WHERE sub.student_id=s.id AND sub.status='needs_revision') as revision_count,
    (SELECT COUNT(*) FROM surveys sv WHERE sv.student_id=s.id) as survey_count,
    (SELECT COUNT(*) FROM surveys sv WHERE sv.student_id=s.id AND sv.q3_needs_consultation=1) as consult_count
FROM students s WHERE s.adviser_id=? AND s.status='approved' ORDER BY s.last_name");
$students->execute([$userId]); $reportData=$students->fetchAll();

$totalReqs=$db->query("SELECT COUNT(*) FROM submission_requirements WHERE is_active=1")->fetchColumn();
include __DIR__.'/../components/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">Reports</h5><small class="text-muted">Student performance and submission summary</small></div>
  <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="bi bi-printer me-1"></i>Print</button>
</div>
<div class="card">
  <div class="card-header"><i class="bi bi-bar-chart-line me-2 text-danger"></i>Student Submission Summary</div>
  <div class="card-body p-0">
    <?php if(empty($reportData)): ?>
      <div class="empty-state"><i class="bi bi-people"></i><p>No approved students yet.</p></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4">Student</th>
          <th>Program</th>
          <th>Company</th>
          <th>Approved</th>
          <th>Pending</th>
          <th>Needs Revision</th>
          <th>Progress</th>
          <th>Surveys</th>
          <th>Consult Req.</th>
        </tr></thead>
        <tbody>
        <?php foreach($reportData as $s): ?>
          <?php $prog=$totalReqs>0?round(($s['approved_count']/$totalReqs)*100):0; ?>
          <tr>
            <td class="ps-4">
              <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
              <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['student_number'])?></div>
            </td>
            <td style="font-size:.82rem"><?=htmlspecialchars($s['program'])?></td>
            <td style="font-size:.82rem"><?=htmlspecialchars($s['company_name'])?></td>
            <td><span class="badge bg-success"><?=$s['approved_count']?></span></td>
            <td><span class="badge bg-warning text-dark"><?=$s['pending_count']?></span></td>
            <td><span class="badge" style="background:#e67e22"><?=$s['revision_count']?></span></td>
            <td style="min-width:120px">
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-fill" style="height:6px">
                  <div class="progress-bar" style="width:<?=$prog?>%"></div>
                </div>
                <span style="font-size:.75rem;font-weight:600;color:var(--primary)"><?=$prog?>%</span>
              </div>
            </td>
            <td><span class="badge bg-info"><?=$s['survey_count']?></span></td>
            <td><span class="badge <?=$s['consult_count']>0?'bg-danger':'bg-light text-dark border'?>"><?=$s['consult_count']?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php include __DIR__.'/../components/footer.php'; ?>
