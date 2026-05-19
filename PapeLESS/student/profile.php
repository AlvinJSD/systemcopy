<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('student');
$userId=$_SESSION['user_id']; $userType='student'; $pageTitle='My Profile'; $activeNav='profile';
$db=getDB();

$success=$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&validateCSRF($_POST['csrf_token']??'')){
    $fn=sanitize($_POST['first_name']??'');
    $mn=sanitize($_POST['middle_name']??'');
    $ln=sanitize($_POST['last_name']??'');
    $phone=sanitize($_POST['contact_number']??'');
    $company=sanitize($_POST['company_name']??'');
    $address=sanitize($_POST['company_address']??'');
    $role=sanitize($_POST['department_role']??'');
    $tin=$_POST['time_in']??'';
    $tout=$_POST['time_out']??'';

    $photoPath=null;
    if(!empty($_FILES['profile_photo']['name'])){
        $ext=strtolower(pathinfo($_FILES['profile_photo']['name'],PATHINFO_EXTENSION));
        if(in_array($ext,['jpg','jpeg','png','webp'])&&$_FILES['profile_photo']['size']<2*1024*1024){
            $dir=UPLOAD_PATH.'../../profile_photos/';
            if(!is_dir($dir)) mkdir($dir,0755,true);
            $fname='student_'.$userId.'_'.time().'.'.$ext;
            if(move_uploaded_file($_FILES['profile_photo']['tmp_name'],$dir.$fname))
                $photoPath='profile_photos/'.$fname;
        } else { $error='Photo must be JPG/PNG and under 2MB.'; }
    }

    if(!$error){
        $sql="UPDATE students SET first_name=?,middle_name=?,last_name=?,contact_number=?,company_name=?,company_address=?,department_role=?,time_in=?,time_out=?"
            .($photoPath?",profile_photo=?":'')." WHERE id=?";
        $params=[$fn,$mn?:null,$ln,$phone?:null,$company,$address,$role,$tin,$tout];
        if($photoPath) $params[]=$photoPath;
        $params[]=$userId;
        $db->prepare($sql)->execute($params);
        $_SESSION['user_name']="$fn $ln";
        $success='Profile updated successfully!';
    }
}

$user=getCurrentUser();
include __DIR__.'/../components/header.php';
?>
<?php if($success): ?><div class="alert alert-success alert-dismissible" style="border-radius:10px"><i class="bi bi-check-circle me-2"></i><?=$success?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger" style="border-radius:10px"><?=$error?></div><?php endif; ?>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card text-center">
      <div class="card-body pt-4">
        <?php if(!empty($user['profile_photo'])): ?>
          <img src="<?=BASE_URL?>/assets/uploads/<?=htmlspecialchars($user['profile_photo'])?>" class="profile-avatar-lg mb-3" alt="Photo">
        <?php else: ?>
          <div class="avatar-initials-lg mx-auto mb-3"><?=strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1))?></div>
        <?php endif; ?>
        <h6 class="fw-bold"><?=htmlspecialchars($user['first_name'].' '.$user['last_name'])?></h6>
        <p style="font-size:.8rem;color:var(--primary);font-weight:600"><?=htmlspecialchars($user['program'])?> — <?=$user['year_level']?>-<?=$user['section']?></p>
        <div class="divider"></div>
        <div class="text-start">
          <div class="mb-2"><span class="info-label">Student No.</span><br><span class="info-value"><?=htmlspecialchars($user['student_number'])?></span></div>
          <div class="mb-2"><span class="info-label">Email</span><br><span class="info-value" style="font-size:.82rem"><?=htmlspecialchars($user['email'])?></span></div>
          <div class="mb-2"><span class="info-label">Status</span><br><?=statusBadge($user['status'])?></div>
          <div><span class="info-label">Registered</span><br><span class="info-value" style="font-size:.82rem"><?=formatDate($user['created_at'])?></span></div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-pencil me-2 text-danger"></i>Edit Profile</div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">

          <h6 class="text-uppercase fw-bold mb-3" style="font-size:.72rem;letter-spacing:1px;color:#adb5bd">Personal Information</h6>
          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control" value="<?=htmlspecialchars($user['first_name'])?>" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name</label>
              <input type="text" name="middle_name" class="form-control" value="<?=htmlspecialchars($user['middle_name']??'')?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control" value="<?=htmlspecialchars($user['last_name'])?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact Number</label>
              <input type="tel" name="contact_number" class="form-control" value="<?=htmlspecialchars($user['contact_number']??'')?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Profile Photo</label>
              <input type="file" name="profile_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
              <small class="text-muted">JPG/PNG, max 2MB</small>
            </div>
          </div>

          <h6 class="text-uppercase fw-bold mb-3" style="font-size:.72rem;letter-spacing:1px;color:#adb5bd">Internship Information</h6>
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">Company Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" class="form-control" value="<?=htmlspecialchars($user['company_name'])?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Department / Role <span class="text-danger">*</span></label>
              <input type="text" name="department_role" class="form-control" value="<?=htmlspecialchars($user['department_role'])?>" required>
            </div>
            <div class="col-12">
              <label class="form-label">Company Address <span class="text-danger">*</span></label>
              <textarea name="company_address" class="form-control" rows="2" required><?=htmlspecialchars($user['company_address'])?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Time In</label>
              <input type="time" name="time_in" class="form-control" value="<?=htmlspecialchars($user['time_in'])?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Time Out</label>
              <input type="time" name="time_out" class="form-control" value="<?=htmlspecialchars($user['time_out'])?>">
            </div>
          </div>

          <button type="submit" class="btn btn-danger">
            <i class="bi bi-save me-2"></i>Save Changes
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/../components/footer.php'; ?>
