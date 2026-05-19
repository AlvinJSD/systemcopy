<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');
$userId=$_SESSION['user_id']; $userType='coordinator'; $pageTitle='System Settings'; $activeNav='settings';
$db=getDB();

$success=$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&validateCSRF($_POST['csrf_token']??'')){
    $fields=['academic_year','semester','submission_deadline','max_file_size_mb','survey_enabled','system_name','institution_name','campus'];
    $stmt=$db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
    foreach($fields as $f){
        $val=sanitize($_POST[$f]??'');
        $stmt->execute([$f,$val,$val]);
    }
    logActivity('coordinator',$userId,'Update Settings','System settings updated.');
    $success='Settings saved successfully!';
}

// Load all settings
$settingsStmt=$db->query("SELECT setting_key,setting_value FROM settings");
$settings=[];
foreach($settingsStmt->fetchAll() as $r) $settings[$r['setting_key']]=$r['setting_value'];

include __DIR__.'/../components/header.php';
?>

<?php if($success): ?><div class="alert alert-success alert-dismissible" style="border-radius:10px"><i class="bi bi-check-circle me-2"></i><?=$success?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-8">
    <form method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">

      <!-- Academic Settings -->
      <div class="card mb-4">
        <div class="card-header"><i class="bi bi-mortarboard me-2 text-danger"></i>Academic Settings</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Academic Year</label>
              <input type="text" name="academic_year" class="form-control" value="<?=htmlspecialchars($settings['academic_year']??'')?>" placeholder="e.g. 2024-2025">
            </div>
            <div class="col-md-6">
              <label class="form-label">Semester</label>
              <select name="semester" class="form-select">
                <option value="1st Semester" <?=($settings['semester']??'')==='1st Semester'?'selected':''?>>1st Semester</option>
                <option value="2nd Semester" <?=($settings['semester']??'')==='2nd Semester'?'selected':''?>>2nd Semester</option>
                <option value="Summer" <?=($settings['semester']??'')==='Summer'?'selected':''?>>Summer</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Submission Deadline</label>
              <input type="date" name="submission_deadline" class="form-control" value="<?=htmlspecialchars($settings['submission_deadline']??'')?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Max File Size (MB)</label>
              <input type="number" name="max_file_size_mb" class="form-control" value="<?=htmlspecialchars($settings['max_file_size_mb']??'10')?>" min="1" max="50">
            </div>
          </div>
        </div>
      </div>

      <!-- System Settings -->
      <div class="card mb-4">
        <div class="card-header"><i class="bi bi-gear me-2 text-danger"></i>System Information</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">System Name</label>
              <input type="text" name="system_name" class="form-control" value="<?=htmlspecialchars($settings['system_name']??'PapeLESS')?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Campus</label>
              <input type="text" name="campus" class="form-control" value="<?=htmlspecialchars($settings['campus']??'')?>">
            </div>
            <div class="col-12">
              <label class="form-label">Institution Name</label>
              <input type="text" name="institution_name" class="form-control" value="<?=htmlspecialchars($settings['institution_name']??'')?>">
            </div>
          </div>
        </div>
      </div>

      <!-- Survey Settings -->
      <div class="card mb-4">
        <div class="card-header"><i class="bi bi-clipboard2-heart me-2 text-danger"></i>Survey Settings</div>
        <div class="card-body">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="survey_enabled" id="surveyEnabled" value="1" <?=($settings['survey_enabled']??'1')==='1'?'checked':''?>>
            <label class="form-check-label" for="surveyEnabled">Enable daily well-being survey popup for students</label>
          </div>
          <small class="text-muted d-block mt-2">When enabled, students will see a daily check-in popup on their dashboard.</small>
        </div>
      </div>

      <button type="submit" class="btn btn-danger">
        <i class="bi bi-save me-2"></i>Save Settings
      </button>
    </form>
  </div>

  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-info-circle me-2 text-danger"></i>System Information</div>
      <div class="card-body">
        <div class="mb-3">
          <span class="info-label">System Version</span>
          <div class="info-value">PapeLESS v1.0.0</div>
        </div>
        <div class="mb-3">
          <span class="info-label">PHP Version</span>
          <div class="info-value"><?=PHP_VERSION?></div>
        </div>
        <div class="mb-3">
          <span class="info-label">Database</span>
          <div class="info-value">MySQL (papeless_db)</div>
        </div>
        <div class="mb-3">
          <span class="info-label">Upload Directory</span>
          <div class="info-value" style="font-size:.78rem;word-break:break-all"><?=UPLOAD_PATH?></div>
        </div>
        <?php
        $totalStudents=$db->query("SELECT COUNT(*) FROM students")->fetchColumn();
        $totalSubs=$db->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
        $totalSurveys=$db->query("SELECT COUNT(*) FROM surveys")->fetchColumn();
        ?>
        <div class="divider"></div>
        <div class="mb-2"><span class="info-label">Total Students</span><span class="float-end badge bg-primary"><?=$totalStudents?></span></div>
        <div class="mb-2"><span class="info-label">Total Submissions</span><span class="float-end badge bg-success"><?=$totalSubs?></span></div>
        <div><span class="info-label">Total Surveys</span><span class="float-end badge bg-info"><?=$totalSurveys?></span></div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__.'/../components/footer.php'; ?>
