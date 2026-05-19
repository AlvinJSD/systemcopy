<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$action=$_POST['action']??'';
switch($action){
    case 'approve_student':  approveStudent(); break;
    case 'reject_student':   rejectStudent();  break;
    case 'review_submission':reviewSubmission(); break;
    default: jsonResponse(['success'=>false,'error'=>'Invalid action.'],400);
}

function approveStudent():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $adviserId=$_SESSION['user_id'];
    $studentId=(int)($_POST['student_id']??0);
    $db=getDB();
    // Verify this student belongs to this adviser
    $chk=$db->prepare("SELECT id,first_name,last_name,email FROM students WHERE id=? AND adviser_id=? AND status='pending'");
    $chk->execute([$studentId,$adviserId]);
    $student=$chk->fetch();
    if(!$student) jsonResponse(['success'=>false,'error'=>'Student not found or already processed.']);
    $db->prepare("UPDATE students SET status='approved', approved_at=NOW() WHERE id=?")->execute([$studentId]);
    createNotification('student',$studentId,'Registration Approved!','Your OJT registration has been approved. You can now fully access the system.','approval');
    logActivity('adviser',$adviserId,'Approve Student',"Approved student ID $studentId");
    jsonResponse(['success'=>true,'message'=>"Registration approved for {$student['first_name']} {$student['last_name']}."]); 
}

function rejectStudent():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $adviserId=$_SESSION['user_id'];
    $studentId=(int)($_POST['student_id']??0);
    $reason=sanitize($_POST['reason']??'');
    if(!$reason) jsonResponse(['success'=>false,'error'=>'Rejection reason required.']);
    $db=getDB();
    $chk=$db->prepare("SELECT id,first_name,last_name FROM students WHERE id=? AND adviser_id=? AND status='pending'");
    $chk->execute([$studentId,$adviserId]);
    $student=$chk->fetch();
    if(!$student) jsonResponse(['success'=>false,'error'=>'Student not found.']);
    $db->prepare("UPDATE students SET status='rejected', rejection_reason=? WHERE id=?")->execute([$reason,$studentId]);
    createNotification('student',$studentId,'Registration Rejected','Your registration was rejected. Reason: '.$reason,'system');
    logActivity('adviser',$adviserId,'Reject Student',"Rejected student ID $studentId. Reason: $reason");
    jsonResponse(['success'=>true,'message'=>"Registration rejected for {$student['first_name']} {$student['last_name']}."]); 
}

function reviewSubmission():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $adviserId=$_SESSION['user_id'];
    $subId=(int)($_POST['submission_id']??0);
    $decision=$_POST['decision']??'';
    $comment=sanitize($_POST['comment']??'');
    if(!in_array($decision,['approved','needs_revision'])) jsonResponse(['success'=>false,'error'=>'Invalid decision.']);
    $db=getDB();
    // Verify this submission belongs to adviser's student
    $sub=$db->prepare("SELECT s.*,st.adviser_id,st.first_name,st.last_name,r.requirement_name FROM submissions s JOIN students st ON st.id=s.student_id JOIN submission_requirements r ON r.id=s.requirement_id WHERE s.id=?");
    $sub->execute([$subId]);
    $submission=$sub->fetch();
    if(!$submission||$submission['adviser_id']!=$adviserId) jsonResponse(['success'=>false,'error'=>'Submission not found.']);
    $db->prepare("UPDATE submissions SET status=?,adviser_comment=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")->execute([$decision,$comment?:null,$adviserId,$subId]);
    $studentId=$submission['student_id'];
    $reqName=$submission['requirement_name'];
    if($decision==='approved'){
        createNotification('student',$studentId,'Document Approved',"Your submission for '$reqName' has been approved.",'approval',$subId,'submission');
        $msg="Your document '$reqName' has been approved.";
    } else {
        createNotification('student',$studentId,'Revision Required',"Your submission for '$reqName' needs revision.".($comment?" Comment: $comment":''),'revision',$subId,'submission');
        $msg="Revision requested for '$reqName'.";
    }
    logActivity('adviser',$adviserId,'Review Submission',"$decision submission ID $subId for {$submission['first_name']} {$submission['last_name']}");
    jsonResponse(['success'=>true,'message'=>$msg]);
}
