<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('adviser');
$userId=$_SESSION['user_id']; $userType='adviser'; $activeNav='students';
$db=getDB();

$studentId=(int)($_GET['id']??0);
$stu=$db->prepare("SELECT * FROM students WHERE id=? AND adviser_id=?");
$stu->execute([$studentId,$userId]);
$student=$stu->fetch();
if(!$student){redirect(BASE_URL.'/adviser/students.php');}

$pageTitle=htmlspecialchars($student['first_name'].' '.$student['last_name']);

// Submissions
$subs=$db->prepare("SELECT s.*,r.requirement_name FROM submissions s JOIN submission_requirements r ON r.id=s.requirement_id WHERE s.student_id=? ORDER BY s.submitted_at DESC");
$subs->execute([$studentId]); $submissions=$subs->fetchAll();

// Surveys (last 10)
$svs=$db->prepare("SELECT * FROM surveys WHERE student_id=? ORDER BY survey_date DESC LIMIT 10");
$svs->execute([$studentId]); $surveys=$svs->fetchAll();

// Messages
$msgs=$db->prepare("SELECT * FROM messages WHERE (sender_type='student' AND sender_id=? AND receiver_type='adviser' AND receiver_id=?) OR (sender_type='adviser' AND sender_id=? AND receiver_type='student' AND receiver_id=?) ORDER BY created_at ASC LIMIT 30");
$msgs->execute([$studentId,$userId,$userId,$studentId]); $messages=$msgs->fetchAll();

$moodLabels=['','😞','😕','😐','🙂','😄'];
include __DIR__.'/../components/header.php';
?>
<div class="d-flex align-items-center gap-3 mb-4">
  <a href="<?=BASE_URL?>/adviser/students.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
  <div>
    <h5 class="fw-bold mb-0"><?=htmlspecialchars($student['first_name'].' '.$student['last_name'])?></h5>
    <small class="text-muted"><?=htmlspecialchars($student['student_number'])?> • <?=htmlspecialchars($student['program'])?> <?=$student['year_level']?>-<?=$student['section']?></small>
  </div>
  <?=statusBadge($student['status'])?>
</div>

<div class="row g-4">
  <!-- Left: Info -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-person me-2 text-danger"></i>Personal Info</div>
      <div class="card-body">
        <div class="mb-2"><span class="info-label">Full Name</span><br><span class="info-value"><?=htmlspecialchars($student['first_name'].' '.($student['middle_name']?' '.$student['middle_name'].' ':'').$student['last_name'])?></span></div>
        <div class="mb-2"><span class="info-label">Birthdate</span><br><span class="info-value"><?=formatDate($student['birthdate'])?></span></div>
        <div class="mb-2"><span class="info-label">Email</span><br><span class="info-value" style="font-size:.82rem"><?=htmlspecialchars($student['email'])?></span></div>
        <div><span class="info-label">Contact</span><br><span class="info-value"><?=htmlspecialchars($student['contact_number']??'N/A')?></span></div>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><i class="bi bi-building me-2 text-danger"></i>Internship Info</div>
      <div class="card-body">
        <div class="mb-2"><span class="info-label">Company</span><br><span class="info-value"><?=htmlspecialchars($student['company_name'])?></span></div>
        <div class="mb-2"><span class="info-label">Role</span><br><span class="info-value"><?=htmlspecialchars($student['department_role'])?></span></div>
        <div class="mb-2"><span class="info-label">Address</span><br><span class="info-value" style="font-size:.82rem"><?=htmlspecialchars($student['company_address'])?></span></div>
        <div class="mb-2"><span class="info-label">Start Date</span><br><span class="info-value"><?=formatDate($student['internship_start'])?></span></div>
        <div><span class="info-label">Schedule</span><br><span class="info-value"><?=date('h:i A',strtotime($student['time_in']))?> – <?=date('h:i A',strtotime($student['time_out']))?></span></div>
      </div>
    </div>
  </div>

  <!-- Right: Tabs -->
  <div class="col-lg-8">
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabSubs">Submissions (<?=count($submissions)?>)</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabSurvey">Surveys (<?=count($surveys)?>)</a></li>
      <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabMsg">Messages</a></li>
    </ul>

    <div class="tab-content">
      <!-- Submissions tab -->
      <div class="tab-pane fade show active" id="tabSubs">
        <div class="card">
          <div class="card-body p-0">
            <?php if(empty($submissions)): ?>
              <div class="empty-state"><i class="bi bi-folder-x"></i><p>No submissions yet.</p></div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead><tr><th class="ps-3">Requirement</th><th>File</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach($submissions as $s): ?>
                  <tr>
                    <td class="ps-3" style="font-size:.83rem;font-weight:600"><?=htmlspecialchars($s['requirement_name'])?></td>
                    <td>
                      <div class="d-flex align-items-center gap-1">
                        <i class="bi <?=getFileIcon($s['file_type'])?>"></i>
                        <span style="font-size:.75rem"><?=htmlspecialchars($s['original_name'])?></span>
                      </div>
                    </td>
                    <td><?=statusBadge($s['status'])?></td>
                    <td style="font-size:.75rem;color:#6c757d"><?=timeAgo($s['submitted_at'])?></td>
                    <td>
                      <div class="d-flex gap-1">
                        <a href="<?=BASE_URL?>/<?=htmlspecialchars($s['file_path'])?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                        <?php if(in_array($s['status'],['pending','resubmitted'])): ?>
                          <button class="btn btn-sm btn-success review-approve-btn" data-id="<?=$s['id']?>"><i class="bi bi-check-lg"></i></button>
                          <button class="btn btn-sm btn-outline-warning review-revise-btn" data-id="<?=$s['id']?>"><i class="bi bi-pencil"></i></button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Survey tab -->
      <div class="tab-pane fade" id="tabSurvey">
        <div class="card">
          <div class="card-body p-0">
            <?php if(empty($surveys)): ?>
              <div class="empty-state"><i class="bi bi-clipboard-x"></i><p>No survey responses yet.</p></div>
            <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead><tr><th class="ps-3">Date</th><th>Mood</th><th>Stress</th><th>Experience</th><th>Flags</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach($surveys as $sv): ?>
                  <tr>
                    <td class="ps-3" style="font-size:.8rem"><?=formatDate($sv['survey_date'],'M d, Y')?></td>
                    <td style="font-size:1.1rem"><?=$moodLabels[$sv['q1_mood']]??'—'?></td>
                    <td>
                      <?php $stressColors=['','success','success','warning','danger','danger'];?>
                      <span class="badge bg-<?=$stressColors[$sv['q2_stress']]??'secondary'?>"><?=$sv['q2_stress']?>/5</span>
                    </td>
                    <td>
                      <?php for($i=1;$i<=5;$i++): ?>
                        <i class="bi bi-star<?=$i<=$sv['q5_experience_rating']?'-fill text-warning':''?>" style="font-size:.75rem"></i>
                      <?php endfor; ?>
                    </td>
                    <td>
                      <?php if($sv['q3_needs_consultation']): ?><span class="badge bg-warning text-dark me-1" style="font-size:.65rem">Consult</span><?php endif; ?>
                      <?php if($sv['q4_company_problem']): ?><span class="badge bg-danger" style="font-size:.65rem">Issue</span><?php endif; ?>
                    </td>
                    <td style="font-size:.75rem;color:#6c757d;max-width:180px"><?=htmlspecialchars(substr($sv['additional_notes']??'',0,80))?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Messages tab -->
      <div class="tab-pane fade" id="tabMsg">
        <div class="card" style="height:400px;display:flex;flex-direction:column">
          <div class="card-body p-3" style="flex:1;overflow-y:auto;background:#f8f9fa">
            <?php if(empty($messages)): ?>
              <div class="text-center text-muted py-4" style="font-size:.85rem">No messages yet.</div>
            <?php else: foreach($messages as $m): ?>
              <?php $isSent=($m['sender_type']==='adviser'); ?>
              <div class="d-flex <?=$isSent?'justify-content-end':''?> mb-2">
                <div class="msg-bubble <?=$isSent?'sent':'received'?>">
                  <div style="font-weight:700;font-size:.75rem;margin-bottom:.2rem"><?=htmlspecialchars($m['subject'])?></div>
                  <?=nl2br(htmlspecialchars($m['message']))?>
                  <div class="msg-time"><?=timeAgo($m['created_at'])?></div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
          <div class="card-footer bg-white border-top p-3">
            <form id="msgForm">
              <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
              <input type="hidden" name="student_id" value="<?=$studentId?>">
              <div class="mb-2"><input type="text" name="subject" class="form-control form-control-sm" placeholder="Subject" required></div>
              <div class="d-flex gap-2">
                <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Type message..." required style="resize:none"></textarea>
                <button type="submit" class="btn btn-danger btn-sm align-self-end"><i class="bi bi-send-fill"></i></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow" style="border-radius:14px">
      <div class="modal-header border-0"><h6 class="fw-bold mb-0" id="reviewModalTitle">Review Submission</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" id="reviewSubId">
        <input type="hidden" id="reviewDecision">
        <div class="mb-3">
          <label class="form-label fw-bold">Feedback / Comment <span id="commentReq"></span></label>
          <textarea id="reviewComment" class="form-control" rows="3" placeholder="Add your feedback..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger btn-sm" id="doReviewBtn">Submit Review</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.review-approve-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('reviewSubId').value=btn.dataset.id;
    document.getElementById('reviewDecision').value='approved';
    document.getElementById('reviewModalTitle').textContent='Approve Submission';
    document.getElementById('commentReq').textContent='(Optional)';
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
  });
});
document.querySelectorAll('.review-revise-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('reviewSubId').value=btn.dataset.id;
    document.getElementById('reviewDecision').value='needs_revision';
    document.getElementById('reviewModalTitle').textContent='Request Revision';
    document.getElementById('commentReq').textContent='(Required)';
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
  });
});
document.getElementById('doReviewBtn')?.addEventListener('click',async()=>{
  const decision=document.getElementById('reviewDecision').value;
  const comment=document.getElementById('reviewComment').value.trim();
  if(decision==='needs_revision'&&!comment){showToast('Comment required for revision.','error');return;}
  const res=await ajaxPost('/PapeLESS/adviser/submission_handler.php',{action:'review_submission',submission_id:document.getElementById('reviewSubId').value,decision,comment});
  if(res.success){showToast(res.message,'success');bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();location.reload();}
  else showToast(res.error,'error');
});

document.getElementById('msgForm')?.addEventListener('submit',async(e)=>{
  e.preventDefault();
  const data=Object.fromEntries(new FormData(e.target));
  data.action='send_message';
  const res=await ajaxPost('/PapeLESS/adviser/messages.php',data);
  if(res.success){showToast('Message sent!','success');location.reload();}else showToast(res.error,'error');
});
</script>
<?php include __DIR__.'/../components/footer.php'; ?>
