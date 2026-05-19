<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');
$action=$_POST['action']??'';
switch($action){
    case 'create': createAnnouncement(); break;
    case 'update': updateAnnouncement(); break;
    case 'delete': deleteAnnouncement(); break;
    default: jsonResponse(['success'=>false,'error'=>'Invalid action.'],400);
}

function createAnnouncement():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $coordinatorId=$_SESSION['user_id'];
    $title    = sanitize($_POST['title']??'');
    $content  = sanitize($_POST['content']??'');
    $audience = $_POST['target_audience']??'all';
    $program  = sanitize($_POST['target_program']??'');
    $pinned   = isset($_POST['is_pinned'])?1:0;

    if(!$title||!$content) jsonResponse(['success'=>false,'error'=>'Title and content are required.']);
    if(!in_array($audience,['all','students','advisers'])) jsonResponse(['success'=>false,'error'=>'Invalid audience.']);

    $db=getDB();
    $attachPath=$attachName=null;
    if(!empty($_FILES['attachment']['name'])){
        $res=handleFileUpload($_FILES['attachment'],0);
        if(!$res['success']) jsonResponse(['success'=>false,'error'=>$res['error']]);
        $attachPath=$res['file_name'];
        $attachName=$res['original_name'];
        // Move to announcements folder
        $dir=UPLOAD_PATH.'../../attachments/';
        if(!is_dir($dir)) mkdir($dir,0755,true);
        rename(UPLOAD_PATH.'0/'.$res['file_name'],$dir.$res['file_name']);
        $attachPath='assets/uploads/attachments/'.$res['file_name'];
    }

    $stmt=$db->prepare("INSERT INTO announcements (title,content,attachment,attachment_name,target_audience,target_program,is_pinned,posted_by) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$title,$content,$attachPath,$attachName,$audience,$program?:null,$pinned,$coordinatorId]);

    // Notify users
    $notifTitle="📢 ".$title;
    $notifMsg=substr($content,0,100).(strlen($content)>100?'...':'');
    if(in_array($audience,['all','students'])){
        $stuIds=$db->query("SELECT id FROM students WHERE status='approved'")->fetchAll(PDO::FETCH_COLUMN);
        foreach($stuIds as $sid) createNotification('student',$sid,$notifTitle,$notifMsg,'announcement');
    }
    if(in_array($audience,['all','advisers'])){
        $advIds=$db->query("SELECT id FROM advisers WHERE is_active=1")->fetchAll(PDO::FETCH_COLUMN);
        foreach($advIds as $aid) createNotification('adviser',$aid,$notifTitle,$notifMsg,'announcement');
    }

    logActivity('coordinator',$coordinatorId,'Post Announcement',"Posted: $title (to $audience)");
    jsonResponse(['success'=>true,'message'=>'Announcement posted successfully!']);
}

function updateAnnouncement():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $coordinatorId=$_SESSION['user_id'];
    $annId  =(int)($_POST['announcement_id']??0);
    $title   = sanitize($_POST['title']??'');
    $content = sanitize($_POST['content']??'');
    $audience= $_POST['target_audience']??'all';
    $program = sanitize($_POST['target_program']??'');
    $pinned  = isset($_POST['is_pinned'])?1:0;
    if(!$annId||!$title||!$content) jsonResponse(['success'=>false,'error'=>'Required fields missing.']);
    $db=getDB();
    $db->prepare("UPDATE announcements SET title=?,content=?,target_audience=?,target_program=?,is_pinned=?,updated_at=NOW() WHERE id=?")->execute([$title,$content,$audience,$program?:null,$pinned,$annId]);
    logActivity('coordinator',$coordinatorId,'Update Announcement',"Updated announcement ID $annId: $title");
    jsonResponse(['success'=>true,'message'=>'Announcement updated successfully!']);
}

function deleteAnnouncement():void{
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $coordinatorId=$_SESSION['user_id'];
    $annId=(int)($_POST['announcement_id']??0);
    if(!$annId) jsonResponse(['success'=>false,'error'=>'Invalid announcement.']);
    $db=getDB();
    $db->prepare("DELETE FROM announcements WHERE id=?")->execute([$annId]);
    logActivity('coordinator',$coordinatorId,'Delete Announcement',"Deleted announcement ID $annId");
    jsonResponse(['success'=>true,'message'=>'Announcement deleted.']);
}
