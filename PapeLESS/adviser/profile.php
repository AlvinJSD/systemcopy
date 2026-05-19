<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='My Profile'; $activeNav='profile';
$db=getDB();
$success=$error='';
if($_SERVER['REQUEST_METHOD']==='POST'&&validateCSRF($_POST['csrf_token']??'')){
    $fn=sanitize($_POST['first_name']??'');
    $ln=sanitize($_POST['last_name']??'');
    $phone=sanitize($_POST['contact_number']??'');
    $newPwd=$_POST['new_password']??'';
    $confirmPwd=$_POST['confirm_password']??'';
    $updates=["first_name=?,last_name=?,contact_number=?"]; $params=[$fn,$ln,$phone?:null];
    if($newPwd){
        if(strlen($newPwd)<8){$error='Password must be at least 8 characters.';}
        elseif($newPwd!==$confirmPwd){$error='Passwords do not match.';}
        else{$updates[]="password=?";$params[]=hashPassword($newPwd);}
    }
    if(!$error){$params[]=$userId;$db->prepare("UPDATE advisers SET ".implode(',',$updates)." WHERE id=?")->execute($params);$success='Profile updated!';}
}
$user=getCurrentUser();
include __DIR__.'/../components/header.php';
?>
<?php if($success): ?><div class="alert alert-success" style="border-radius:10px"><i class="bi bi-check-circle me-2"></i><?=$success?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger" style="border-radius:10px"><?=$error?></div><?php endif; ?>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card text-center">
      <div class="card-body pt-4">
        <div class="avatar-initials-lg mx-auto mb-3"><?=strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1))?></div>
        <h6 class="fw-bold"><?=htmlspecialchars($user['first_name'].' '.$user['last_name'])?></h6>
        <p style="font-size:.8rem;color:var(--primary);font-weight:600"><?=htmlspecialchars($user['program'])?> — Year <?=$user['year_level']?> Adviser</p>
        <div class="divider"></div>
        <div class="text-start">
          <div class="mb-2"><span class="info-label">Employee ID</span><br><span class="info-value"><?=htmlspecialchars($user['employee_id'])?></span></div>
          <div class="mb-2"><span class="info-label">Email</span><br><span class="info-value" style="font-size:.82rem"><?=htmlspecialchars($user['email'])?></span></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header"><i class="bi bi-pencil me-2 text-danger"></i>Edit Profile</div>
      <div class="card-body">
        <form method="POST" novalidate>
          <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
          <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="<?=htmlspecialchars($user['first_name'])?>" required></div>
            <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="<?=htmlspecialchars($user['last_name'])?>" required></div>
            <div class="col-md-6"><label class="form-label">Contact Number</label><input type="tel" name="contact_number" class="form-control" value="<?=htmlspecialchars($user['contact_number']??'')?>"></div>
          </div>
          <h6 class="text-uppercase fw-bold mb-3" style="font-size:.72rem;letter-spacing:1px;color:#adb5bd">Change Password</h6>
          <div class="row g-3 mb-4">
            <div class="col-md-6"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep current"></div>
            <div class="col-md-6"><label class="form-label">Confirm Password</label><input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password"></div>
          </div>
          <button type="submit" class="btn btn-danger"><i class="bi bi-save me-2"></i>Save Changes</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__.'/../components/footer.php'; ?>
