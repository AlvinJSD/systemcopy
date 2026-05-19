<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $pageTitle='Messages'; $activeNav='messages';
$db=getDB();

// AJAX send
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['action'])&&$_POST['action']==='send_message'){
    if(!validateCSRF($_POST['csrf_token']??'')) jsonResponse(['success'=>false,'error'=>'Invalid CSRF.'],403);
    $studentId=(int)($_POST['student_id']??0);
    $subject=sanitize($_POST['subject']??'');
    $message=sanitize($_POST['message']??'');
    // Verify student belongs to this adviser
    $chk=$db->prepare("SELECT id,first_name,last_name FROM students WHERE id=? AND adviser_id=?");
    $chk->execute([$studentId,$userId]);
    $student=$chk->fetch();
    if(!$student||!$subject||!$message) jsonResponse(['success'=>false,'error'=>'Invalid request.']);
    $stmt=$db->prepare("INSERT INTO messages (sender_type,sender_id,receiver_type,receiver_id,subject,message) VALUES ('adviser',?,'student',?,?,?)");
    $stmt->execute([$userId,$studentId,$subject,$message]);
    createNotification('student',$studentId,'New Message from Adviser',"Your adviser sent: $subject",'message');
    jsonResponse(['success'=>true,'message'=>'Message sent.']);
}

// Student list for sidebar
$students=$db->prepare("SELECT id,first_name,last_name,student_number FROM students WHERE adviser_id=? AND status='approved' ORDER BY last_name");
$students->execute([$userId]); $studentList=$students->fetchAll();

// Selected student
$selectedId=(int)($_GET['student']??($studentList[0]['id']??0));
$msgs=[];
$selectedStudent=null;
if($selectedId){
    $sel=$db->prepare("SELECT * FROM students WHERE id=? AND adviser_id=?");
    $sel->execute([$selectedId,$userId]);
    $selectedStudent=$sel->fetch();
    if($selectedStudent){
        $msgStmt=$db->prepare("SELECT * FROM messages WHERE (sender_type='adviser' AND sender_id=? AND receiver_type='student' AND receiver_id=?) OR (sender_type='student' AND sender_id=? AND receiver_type='adviser' AND receiver_id=?) ORDER BY created_at ASC LIMIT 50");
        $msgStmt->execute([$userId,$selectedId,$selectedId,$userId]);
        $msgs=$msgStmt->fetchAll();
        // Mark student messages as read
        $db->prepare("UPDATE messages SET is_read=1 WHERE sender_type='student' AND sender_id=? AND receiver_type='adviser' AND receiver_id=?")->execute([$selectedId,$userId]);
    }
}
include __DIR__.'/../components/header.php';
?>
<div class="row g-0" style="height:calc(100vh - 120px)">
  <!-- Student List Sidebar -->
  <div class="col-lg-3 col-md-4 border-end" style="overflow-y:auto">
    <div class="p-3 border-bottom">
      <h6 class="fw-bold mb-0" style="font-size:.9rem">Students</h6>
    </div>
    <?php if(empty($studentList)): ?>
      <div class="p-3 text-center text-muted" style="font-size:.82rem">No approved students</div>
    <?php else: foreach($studentList as $s): ?>
      <a href="?student=<?=$s['id']?>" class="d-flex align-items-center gap-2 p-3 border-bottom text-decoration-none <?=$s['id']==$selectedId?'bg-light':''?>">
        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;font-size:.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <?=strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1))?>
        </div>
        <div>
          <div style="font-size:.85rem;font-weight:600;color:#2d3436"><?=htmlspecialchars($s['first_name'].' '.$s['last_name'])?></div>
          <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($s['student_number'])?></div>
        </div>
      </a>
    <?php endforeach; endif; ?>
  </div>

  <!-- Chat Area -->
  <div class="col-lg-9 col-md-8 d-flex flex-column">
    <?php if(!$selectedStudent): ?>
      <div class="d-flex align-items-center justify-content-center h-100 text-muted">
        <div class="text-center"><i class="bi bi-chat-dots fs-1 d-block mb-2"></i><p>Select a student to start messaging</p></div>
      </div>
    <?php else: ?>
      <!-- Header -->
      <div class="p-3 border-bottom d-flex align-items-center gap-3" style="background:#fff">
        <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;font-weight:700;display:flex;align-items:center;justify-content:center">
          <?=strtoupper(substr($selectedStudent['first_name'],0,1).substr($selectedStudent['last_name'],0,1))?>
        </div>
        <div>
          <div style="font-weight:700"><?=htmlspecialchars($selectedStudent['first_name'].' '.$selectedStudent['last_name'])?></div>
          <div style="font-size:.75rem;color:#6c757d"><?=htmlspecialchars($selectedStudent['company_name'])?></div>
        </div>
        <a href="<?=BASE_URL?>/adviser/student_profile.php?id=<?=$selectedId?>" class="btn btn-sm btn-outline-secondary ms-auto"><i class="bi bi-person me-1"></i>View Profile</a>
      </div>

      <!-- Messages -->
      <div class="p-3 flex-fill overflow-auto" style="background:#f8f9fa" id="msgThread">
        <?php if(empty($msgs)): ?>
          <div class="text-center text-muted py-4" style="font-size:.85rem">No messages yet.</div>
        <?php else: foreach($msgs as $m): ?>
          <?php $isSent=($m['sender_type']==='adviser'); ?>
          <div class="d-flex <?=$isSent?'justify-content-end':''?> mb-2">
            <div class="msg-bubble <?=$isSent?'sent':'received'?>">
              <div style="font-weight:700;font-size:.75rem;margin-bottom:.2rem"><?=htmlspecialchars($m['subject'])?></div>
              <?=nl2br(htmlspecialchars($m['message']))?>
              <div class="msg-time"><?=formatDateTime($m['created_at'])?></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Compose -->
      <div class="p-3 border-top" style="background:#fff">
        <form id="msgForm">
          <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
          <input type="hidden" name="student_id" value="<?=$selectedId?>">
          <div class="mb-2"><input type="text" name="subject" class="form-control form-control-sm" placeholder="Subject" required></div>
          <div class="d-flex gap-2">
            <textarea name="message" class="form-control form-control-sm" rows="2" style="resize:none" placeholder="Type your message..." required></textarea>
            <button type="submit" class="btn btn-danger btn-sm align-self-end"><i class="bi bi-send-fill"></i></button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
const thread=document.getElementById('msgThread');
if(thread) thread.scrollTop=thread.scrollHeight;

document.getElementById('msgForm')?.addEventListener('submit',async(e)=>{
  e.preventDefault();
  const data=Object.fromEntries(new FormData(e.target));
  data.action='send_message';
  const res=await ajaxPost('/PapeLESS/adviser/messages.php',data);
  if(res.success){showToast('Message sent!','success');location.reload();}else showToast(res.error,'error');
});
</script>
<?php include __DIR__.'/../components/footer.php'; ?>
