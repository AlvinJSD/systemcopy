<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');
$userId=$_SESSION['user_id']; $userType='coordinator'; $pageTitle='Reports'; $activeNav='reports';
$db=getDB();

$reportType=$_GET['type']??'students';
$totalReqs=(int)$db->query("SELECT COUNT(*) FROM submission_requirements WHERE is_active=1")->fetchColumn();

// Student completion report
$studentReport=$db->query("
    SELECT s.*,
        a.first_name as adv_fn, a.last_name as adv_ln,
        (SELECT COUNT(*) FROM submissions sub WHERE sub.student_id=s.id AND sub.status='approved') as approved_docs,
        (SELECT COUNT(*) FROM submissions sub WHERE sub.student_id=s.id AND sub.status='pending') as pending_docs,
        (SELECT COUNT(*) FROM submissions sub WHERE sub.student_id=s.id AND sub.status='needs_revision') as revision_docs,
        (SELECT COUNT(*) FROM surveys sv WHERE sv.student_id=s.id) as survey_count,
        (SELECT COUNT(*) FROM surveys sv WHERE sv.student_id=s.id AND sv.q3_needs_consultation=1) as consult_count,
        (SELECT COUNT(*) FROM surveys sv WHERE sv.student_id=s.id AND sv.q4_company_problem=1) as problem_count
    FROM students s
    LEFT JOIN advisers a ON a.id=s.adviser_id
    WHERE s.status='approved'
    ORDER BY s.program, s.year_level, s.last_name
")->fetchAll();

// Adviser performance report
$adviserReport=$db->query("
    SELECT a.*,
        (SELECT COUNT(*) FROM students s WHERE s.adviser_id=a.id AND s.status='approved') as student_count,
        (SELECT COUNT(*) FROM students s WHERE s.adviser_id=a.id AND s.status='pending') as pending_count,
        (SELECT COUNT(*) FROM submissions sub JOIN students s ON s.id=sub.student_id WHERE s.adviser_id=a.id AND sub.status='approved') as approved_docs,
        (SELECT COUNT(*) FROM submissions sub JOIN students s ON s.id=sub.student_id WHERE s.adviser_id=a.id AND sub.reviewed_by=a.id) as reviewed_docs
    FROM advisers a WHERE a.is_active=1
    ORDER BY a.program, a.year_level, a.last_name
")->fetchAll();

// Survey analytics
$surveyStats=$db->query("
    SELECT
        COUNT(*) as total_responses,
        ROUND(AVG(q1_mood),2) as avg_mood,
        ROUND(AVG(q2_stress),2) as avg_stress,
        ROUND(AVG(q5_experience_rating),2) as avg_experience,
        SUM(q3_needs_consultation) as total_consults,
        SUM(q4_company_problem) as total_problems,
        COUNT(DISTINCT student_id) as unique_students
    FROM surveys
")->fetch();

include __DIR__.'/../components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">Reports & Analytics</h5><small class="text-muted">System-wide performance and completion reports</small></div>
  <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-printer me-2"></i>Print Report
  </button>
</div>

<!-- Report Tabs -->
<ul class="nav nav-tabs mb-4">
  <li class="nav-item"><a class="nav-link <?=$reportType==='students'?'active':''?>" href="?type=students">Student Completion</a></li>
  <li class="nav-item"><a class="nav-link <?=$reportType==='advisers'?'active':''?>" href="?type=advisers">Adviser Performance</a></li>
  <li class="nav-item"><a class="nav-link <?=$reportType==='surveys'?'active':''?>" href="?type=surveys">Survey Analytics</a></li>
</ul>

<?php if($reportType==='students'): ?>
<!-- Student Completion Report -->
<div class="card">
  <div class="card-header justify-content-between">
    <span><i class="bi bi-people me-2 text-danger"></i>Student Document Completion Report</span>
    <small class="text-muted"><?=date('F d, Y')?> • <?=count($studentReport)?> approved students</small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:.82rem">
        <thead><tr>
          <th class="ps-4">Student</th>
          <th>Program</th>
          <th>Company</th>
          <th>Adviser</th>
          <th>Approved</th>
          <th>Pending</th>
          <th>Revision</th>
          <th>Progress</th>
          <th>Surveys</th>
          <th>Alerts</th>
        </tr></thead>
        <tbody>
        <?php foreach($studentReport as $s): ?>
          <?php $prog=$totalReqs>0?round($s['approved_docs']/$totalReqs*100):0; ?>
          <tr>
            <td class="ps-4">
              <div style="font-weight:600"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
              <div style="font-size:.7rem;color:#6c757d"><?=htmlspecialchars($s['student_number'])?></div>
            </td>
            <td><?=htmlspecialchars($s['program'])?> <?=$s['year_level']?>-<?=$s['section']?></td>
            <td><?=htmlspecialchars($s['company_name'])?></td>
            <td><?=htmlspecialchars($s['adv_fn']?$s['adv_fn'].' '.$s['adv_ln']:'Unassigned')?></td>
            <td><span class="badge bg-success"><?=$s['approved_docs']?></span></td>
            <td><span class="badge bg-warning text-dark"><?=$s['pending_docs']?></span></td>
            <td><span class="badge" style="background:#e67e22"><?=$s['revision_docs']?></span></td>
            <td>
              <div class="d-flex align-items-center gap-1">
                <div class="progress" style="height:5px;width:80px"><div class="progress-bar" style="width:<?=$prog?>%"></div></div>
                <span style="font-size:.7rem;font-weight:700;color:var(--primary)"><?=$prog?>%</span>
              </div>
            </td>
            <td><span class="badge bg-info"><?=$s['survey_count']?></span></td>
            <td>
              <?php $alerts=$s['consult_count']+$s['problem_count']; ?>
              <span class="badge <?=$alerts>0?'bg-danger':'bg-light text-dark border'?>"><?=$alerts?></span>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($reportType==='advisers'): ?>
<!-- Adviser Performance -->
<div class="card">
  <div class="card-header justify-content-between">
    <span><i class="bi bi-person-badge me-2 text-danger"></i>Adviser Performance Report</span>
    <small class="text-muted"><?=date('F d, Y')?></small>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0" style="font-size:.82rem">
        <thead><tr>
          <th class="ps-4">Adviser</th>
          <th>Program</th>
          <th>Year Level</th>
          <th>Students</th>
          <th>Pending Students</th>
          <th>Docs Approved</th>
          <th>Docs Reviewed</th>
        </tr></thead>
        <tbody>
        <?php foreach($adviserReport as $a): ?>
          <tr>
            <td class="ps-4">
              <div style="font-weight:600"><?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?></div>
              <div style="font-size:.7rem;color:#6c757d"><?=htmlspecialchars($a['email'])?></div>
            </td>
            <td><?=htmlspecialchars($a['program'])?></td>
            <td>Year <?=$a['year_level']?></td>
            <td><span class="badge bg-primary"><?=$a['student_count']?></span></td>
            <td><span class="badge bg-warning text-dark"><?=$a['pending_count']?></span></td>
            <td><span class="badge bg-success"><?=$a['approved_docs']?></span></td>
            <td><span class="badge bg-info"><?=$a['reviewed_docs']?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif($reportType==='surveys'): ?>
<!-- Survey Analytics -->
<div class="row g-4 mb-4">
  <?php $statCards=[
    ['Total Responses','blue','bi-clipboard-data',$surveyStats['total_responses']??0],
    ['Unique Students','green','bi-people',$surveyStats['unique_students']??0],
    ['Consult Requests','orange','bi-chat-dots',$surveyStats['total_consults']??0],
    ['Problem Reports','red','bi-exclamation-triangle',$surveyStats['total_problems']??0],
  ]; foreach($statCards as [$lbl,$col,$ic,$val]): ?>
  <div class="col-6 col-md-3">
    <div class="stat-card <?=$col?>">
      <div class="stat-icon"><i class="bi <?=$ic?>"></i></div>
      <div><div class="stat-value"><?=$val?></div><div class="stat-label"><?=$lbl?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<div class="card">
  <div class="card-header"><i class="bi bi-bar-chart me-2 text-danger"></i>Average Scores</div>
  <div class="card-body">
    <div class="row g-4">
      <?php $avgs=[
        ['Average Mood','q1_mood','😊 '.($surveyStats['avg_mood']??0).'/5','success'],
        ['Average Stress','q2_stress','😰 '.($surveyStats['avg_stress']??0).'/5','warning'],
        ['Average Experience','q5_experience','⭐ '.($surveyStats['avg_experience']??0).'/5','primary'],
      ]; foreach($avgs as [$lbl,,$val,$col]): ?>
      <div class="col-md-4">
        <div class="p-4 text-center rounded" style="background:#f8f9fa">
          <div style="font-size:2rem;font-weight:800;color:var(--<?=$col?>)"><?=$val?></div>
          <div style="font-size:.82rem;color:#6c757d;margin-top:.3rem"><?=$lbl?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<style>
@media print {
  .sidebar,.topbar,.btn,nav { display:none!important; }
  .main-content { margin:0!important; }
  .page-content { padding:0!important; }
}
</style>

<?php include __DIR__.'/../components/footer.php'; ?>
