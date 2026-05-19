<?php
// ============================================================
// PapeLESS - Coordinator Dashboard
// ============================================================
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');
$userId=$_SESSION['user_id']; $userType='coordinator'; $pageTitle='Dashboard'; $activeNav='dashboard';
$db=getDB();

// System-wide stats
$totalStudents  = (int)$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalAdvisers  = (int)$db->query("SELECT COUNT(*) FROM advisers WHERE is_active=1")->fetchColumn();
$pendingStudents= (int)$db->query("SELECT COUNT(*) FROM students WHERE status='pending'")->fetchColumn();
$approvedStudents=(int)$db->query("SELECT COUNT(*) FROM students WHERE status='approved'")->fetchColumn();
$totalSubs      = (int)$db->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
$approvedSubs   = (int)$db->query("SELECT COUNT(*) FROM submissions WHERE status='approved'")->fetchColumn();
$surveyToday    = (int)$db->query("SELECT COUNT(*) FROM surveys WHERE survey_date=CURDATE()")->fetchColumn();
$alertsToday    = (int)$db->query("SELECT COUNT(*) FROM surveys WHERE survey_date=CURDATE() AND (q3_needs_consultation=1 OR q4_company_problem=1)")->fetchColumn();

// Recent activity log
$actStmt=$db->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10");
$activities=$actStmt->fetchAll();

// Submissions by status (for chart)
$subByStatus=$db->query("SELECT status, COUNT(*) as cnt FROM submissions GROUP BY status")->fetchAll();
$subChart=[];
foreach($subByStatus as $r) $subChart[$r['status']]=$r['cnt'];

// Students by program
$byProgram=$db->query("SELECT program, COUNT(*) as cnt FROM students WHERE status='approved' GROUP BY program ORDER BY cnt DESC LIMIT 6")->fetchAll();

// Recent pending students
$recentPending=$db->query("SELECT s.*,a.first_name as adv_fn,a.last_name as adv_ln FROM students s LEFT JOIN advisers a ON a.id=s.adviser_id WHERE s.status='pending' ORDER BY s.created_at DESC LIMIT 5")->fetchAll();

// Recent announcements
$recentAnns=$db->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3")->fetchAll();

include __DIR__.'/../components/header.php';
?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card blue">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div><div class="stat-value"><?=$totalStudents?></div><div class="stat-label">Total Students</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card green">
      <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
      <div><div class="stat-value"><?=$totalAdvisers?></div><div class="stat-label">Active Advisers</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card orange">
      <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
      <div><div class="stat-value"><?=$pendingStudents?></div><div class="stat-label">Pending Approvals</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card red">
      <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
      <div><div class="stat-value"><?=$approvedSubs?>/<?=$totalSubs?></div><div class="stat-label">Docs Approved</div></div>
    </div>
  </div>
</div>

<?php if($alertsToday>0): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4" style="border-radius:10px;border-left:4px solid #f39c12">
  <i class="bi bi-exclamation-triangle-fill fs-5"></i>
  <div><strong><?=$alertsToday?> student(s)</strong> flagged concerns in today's well-being check-ins. <a href="<?=BASE_URL?>/coordinator/students.php" style="color:inherit;font-weight:700">View details →</a></div>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Left column -->
  <div class="col-xl-8">

    <!-- Submission Analytics -->
    <div class="card mb-4">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-pie-chart me-2 text-danger"></i>Submission Analytics</span>
        <a href="<?=BASE_URL?>/coordinator/reports.php" class="btn btn-sm btn-outline-danger">Full Report</a>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php
          $statuses=['pending'=>['Pending Review','warning'],'approved'=>['Approved','success'],'needs_revision'=>['Needs Revision','orange'],'resubmitted'=>['Resubmitted','info']];
          foreach($statuses as $k=>[$lbl,$col]): $cnt=$subChart[$k]??0; $pct=$totalSubs>0?round($cnt/$totalSubs*100):0;
          ?>
          <div class="col-6 col-md-3">
            <div class="text-center p-3 rounded" style="background:#f8f9fa">
              <div style="font-size:1.8rem;font-weight:800;color:var(--<?=$col==='orange'?'secondary':$col?>)"><?=$cnt?></div>
              <div style="font-size:.75rem;color:#6c757d;font-weight:600"><?=$lbl?></div>
              <div class="progress mt-2" style="height:4px">
                <div class="progress-bar bg-<?=$col==='orange'?'warning':$col?>" style="width:<?=$pct?>%"></div>
              </div>
              <div style="font-size:.7rem;color:#adb5bd;margin-top:.3rem"><?=$pct?>% of total</div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-3 pt-3 border-top d-flex gap-4 flex-wrap" style="font-size:.82rem;color:#6c757d">
          <span><i class="bi bi-calendar2 me-1"></i>Survey responses today: <strong><?=$surveyToday?></strong></span>
          <span><i class="bi bi-people me-1"></i>Approved students: <strong><?=$approvedStudents?></strong></span>
          <span><i class="bi bi-exclamation-triangle me-1"></i>Alerts today: <strong class="text-danger"><?=$alertsToday?></strong></span>
        </div>
      </div>
    </div>

    <!-- Students by Program -->
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-bar-chart-line me-2 text-danger"></i>Students by Program</div>
      <div class="card-body">
        <?php if(empty($byProgram)): ?>
          <div class="empty-state py-2"><p>No approved students yet.</p></div>
        <?php else: $maxCnt=max(array_column($byProgram,'cnt')); foreach($byProgram as $p): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span style="font-size:.83rem;font-weight:600"><?=htmlspecialchars($p['program'])?></span>
              <span style="font-size:.8rem;color:#6c757d"><?=$p['cnt']?> students</span>
            </div>
            <div class="progress" style="height:8px">
              <div class="progress-bar" style="width:<?=round($p['cnt']/$maxCnt*100)?>%"></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Pending Approvals -->
    <div class="card">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-person-check me-2 text-danger"></i>Pending Student Approvals</span>
        <a href="<?=BASE_URL?>/coordinator/students.php?status=pending" class="btn btn-sm btn-outline-danger">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if(empty($recentPending)): ?>
          <div class="empty-state py-3"><i class="bi bi-person-check-fill fs-3 d-block mb-2 text-success"></i><p>No pending approvals!</p></div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th class="ps-4">Student</th><th>Program</th><th>Assigned Adviser</th><th>Registered</th></tr></thead>
            <tbody>
            <?php foreach($recentPending as $s): ?>
              <tr>
                <td class="ps-4">
                  <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
                  <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['email'])?></div>
                </td>
                <td style="font-size:.83rem"><?=htmlspecialchars($s['program'])?> <?=$s['year_level']?>-<?=$s['section']?></td>
                <td style="font-size:.82rem">
                  <?php if($s['adv_fn']): ?>
                    <?=htmlspecialchars($s['adv_fn'].' '.$s['adv_ln'])?>
                  <?php else: ?>
                    <span class="badge bg-warning text-dark">Unassigned</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:.78rem;color:#6c757d"><?=timeAgo($s['created_at'])?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /left -->

  <!-- Right column -->
  <div class="col-xl-4">

    <!-- Quick Actions -->
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-lightning me-2 text-danger"></i>Quick Actions</div>
      <div class="card-body d-flex flex-column gap-2">
        <a href="<?=BASE_URL?>/coordinator/advisers.php?action=add" class="btn btn-danger btn-sm">
          <i class="bi bi-person-plus me-2"></i>Add Adviser
        </a>
        <a href="<?=BASE_URL?>/coordinator/announcements.php?action=new" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-megaphone me-2"></i>Post Announcement
        </a>
        <a href="<?=BASE_URL?>/coordinator/reports.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-file-earmark-bar-graph me-2"></i>Generate Reports
        </a>
        <a href="<?=BASE_URL?>/coordinator/settings.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-gear me-2"></i>System Settings
        </a>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-activity me-2 text-danger"></i>Recent Activity</div>
      <div class="card-body p-0">
        <?php if(empty($activities)): ?>
          <div class="empty-state py-3"><p style="font-size:.85rem">No activity yet.</p></div>
        <?php else: foreach($activities as $act): ?>
          <div class="d-flex gap-2 p-3 border-bottom align-items-start">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--primary);flex-shrink:0;margin-top:5px"></div>
            <div>
              <div style="font-size:.82rem;font-weight:600;color:#2d3436"><?=htmlspecialchars($act['action'])?></div>
              <div style="font-size:.74rem;color:#6c757d">
                <span class="badge bg-light text-dark border"><?=ucfirst($act['user_type'])?></span>
                <?=htmlspecialchars(substr($act['details'],0,50))?>
              </div>
              <div style="font-size:.7rem;color:#adb5bd"><?=timeAgo($act['created_at'])?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Recent Announcements -->
    <div class="card">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-megaphone me-2 text-danger"></i>Latest Announcements</span>
        <a href="<?=BASE_URL?>/coordinator/announcements.php" class="btn btn-sm btn-outline-danger">Manage</a>
      </div>
      <div class="card-body">
        <?php if(empty($recentAnns)): ?>
          <div class="empty-state py-2"><p style="font-size:.85rem">No announcements posted.</p></div>
        <?php else: foreach($recentAnns as $a): ?>
          <div class="mb-3 pb-3 border-bottom">
            <div class="d-flex justify-content-between">
              <span style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($a['title'])?></span>
              <span class="badge bg-light text-dark border" style="font-size:.65rem"><?=$a['target_audience']?></span>
            </div>
            <p style="font-size:.78rem;color:#636e72;margin:.3rem 0 0"><?=htmlspecialchars(substr($a['content'],0,80))?>...</p>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

  </div><!-- /right -->
</div>

<?php include __DIR__.'/../components/footer.php'; ?>
