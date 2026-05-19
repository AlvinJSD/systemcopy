<?php
// ============================================================
// PapeLESS - Coordinator: Adviser Handler (AJAX)
// ============================================================
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');

$action=$_POST['action']??'';
switch($action){
    case 'create':         createAdviser();   break;
    case 'update':         updateAdviser();   break;
    case 'delete':         deleteAdviser();   break;
    case 'reset_password': resetPassword();   break;
    default: jsonResponse(['success'=>false,'error'=>'Invalid action.'],400);
}

function createAdviser():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $coordinatorId=$_SESSION['user_id'];
    $empId    = sanitize($_POST['employee_id']??'');
    $fn       = sanitize($_POST['first_name']??'');
    $mn       = sanitize($_POST['middle_name']??'');
    $ln       = sanitize($_POST['last_name']??'');
    $email    = filter_var(trim($_POST['email']??''),FILTER_SANITIZE_EMAIL);
    $phone    = sanitize($_POST['contact_number']??'');
    $password = $_POST['password']??'';
    $program  = sanitize($_POST['program']??'');
    $year     = (int)($_POST['year_level']??0);

    if(!$empId||!$fn||!$ln||!$email||!$password||!$program||!$year)
        jsonResponse(['success'=>false,'error'=>'All required fields must be filled.']);
    if(!filter_var($email,FILTER_VALIDATE_EMAIL))
        jsonResponse(['success'=>false,'error'=>'Invalid email address.']);
    if(strlen($password)<8)
        jsonResponse(['success'=>false,'error'=>'Password must be at least 8 characters.']);
    if(!in_array($year,[1,2,3,4]))
        jsonResponse(['success'=>false,'error'=>'Invalid year level.']);

    $db=getDB();
    $chk=$db->prepare("SELECT id FROM advisers WHERE email=? OR employee_id=?");
    $chk->execute([$email,$empId]);
    if($chk->fetch()) jsonResponse(['success'=>false,'error'=>'Email or Employee ID already exists.']);

    $stmt=$db->prepare("INSERT INTO advisers (employee_id,first_name,middle_name,last_name,email,password,contact_number,program,year_level,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$empId,$fn,$mn?:null,$ln,$email,hashPassword($password),$phone?:null,$program,$year,$coordinatorId]);

    logActivity('coordinator',$coordinatorId,'Create Adviser',"Created adviser: $fn $ln ($program Year $year)");
    jsonResponse(['success'=>true,'message'=>"Adviser $fn $ln created successfully."]);
}

function updateAdviser():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $coordinatorId=$_SESSION['user_id'];
    $adviserId=(int)($_POST['adviser_id']??0);
    $empId    = sanitize($_POST['employee_id']??'');
    $fn       = sanitize($_POST['first_name']??'');
    $mn       = sanitize($_POST['middle_name']??'');
    $ln       = sanitize($_POST['last_name']??'');
    $email    = filter_var(trim($_POST['email']??''),FILTER_SANITIZE_EMAIL);
    $phone    = sanitize($_POST['contact_number']??'');
    $password = $_POST['password']??'';
    $program  = sanitize($_POST['program']??'');
    $year     = (int)($_POST['year_level']??0);
    $isActive = (int)($_POST['is_active']??1);

    if(!$adviserId||!$fn||!$ln||!$email||!$program||!$year)
        jsonResponse(['success'=>false,'error'=>'All required fields must be filled.']);

    $db=getDB();
    // Check duplicate email (exclude self)
    $chk=$db->prepare("SELECT id FROM advisers WHERE email=? AND id!=?");
    $chk->execute([$email,$adviserId]);
    if($chk->fetch()) jsonResponse(['success'=>false,'error'=>'Email already used by another adviser.']);

    $sql="UPDATE advisers SET employee_id=?,first_name=?,middle_name=?,last_name=?,email=?,contact_number=?,program=?,year_level=?,is_active=?";
    $params=[$empId,$fn,$mn?:null,$ln,$email,$phone?:null,$program,$year,$isActive];
    if($password){
        if(strlen($password)<8) jsonResponse(['success'=>false,'error'=>'Password must be at least 8 characters.']);
        $sql.=",password=?"; $params[]=hashPassword($password);
    }
    $sql.=" WHERE id=?"; $params[]=$adviserId;
    $db->prepare($sql)->execute($params);

    logActivity('coordinator',$coordinatorId,'Update Adviser',"Updated adviser ID $adviserId: $fn $ln");
    jsonResponse(['success'=>true,'message'=>"Adviser $fn $ln updated successfully."]);
}

function deleteAdviser():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $coordinatorId=$_SESSION['user_id'];
    $adviserId=(int)($_POST['adviser_id']??0);
    if(!$adviserId) jsonResponse(['success'=>false,'error'=>'Invalid adviser.']);
    $db=getDB();
    // Check if adviser has students
    $chk=$db->prepare("SELECT COUNT(*) FROM students WHERE adviser_id=? AND status='approved'");
    $chk->execute([$adviserId]);
    if((int)$chk->fetchColumn()>0)
        jsonResponse(['success'=>false,'error'=>'Cannot delete adviser with active approved students. Reassign students first.']);
    // Soft delete by setting inactive, or hard delete
    $db->prepare("DELETE FROM advisers WHERE id=?")->execute([$adviserId]);
    logActivity('coordinator',$coordinatorId,'Delete Adviser',"Deleted adviser ID $adviserId");
    jsonResponse(['success'=>true,'message'=>'Adviser deleted successfully.']);
}

function resetPassword():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $coordinatorId=$_SESSION['user_id'];
    $adviserId=(int)($_POST['adviser_id']??0);
    $newPwd=$_POST['new_password']??'';
    if(!$adviserId||strlen($newPwd)<8) jsonResponse(['success'=>false,'error'=>'Invalid request or password too short.']);
    $db=getDB();
    $db->prepare("UPDATE advisers SET password=? WHERE id=?")->execute([hashPassword($newPwd),$adviserId]);
    logActivity('coordinator',$coordinatorId,'Reset Adviser Password',"Reset password for adviser ID $adviserId");
    jsonResponse(['success'=>true,'message'=>'Password reset successfully.']);
}
