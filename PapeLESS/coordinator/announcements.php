<?php
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');
$userId=$_SESSION['user_id']; $userType='coordinator'; $pageTitle='Announcements'; $activeNav='announcements';
$db=getDB();

$anns=$db->query("SELECT * FROM announcements ORDER BY is_pinned DESC, created_at DESC");
$announcements=$anns->fetchAll();

include __DIR__.'/../components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">Announcements</h5><small class="text-muted">Manage and post system-wide announcements</small></div>
  <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#annModal">
    <i class="bi bi-plus-lg me-2"></i>New Announcement
  </button>
</div>

<?php if(empty($announcements)): ?>
  <div class="card"><div class="card-body"><div class="empty-state"><i class="bi bi-megaphone"></i><p>No announcements yet.</p></div></div></div>
<?php else: foreach($announcements as $a): ?>
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
      <div style="flex:1">
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <?php if($a['is_pinned']): ?><i class="bi bi-pin-fill text-danger"></i><?php endif; ?>
          <h6 class="fw-bold mb-0"><?=htmlspecialchars($a['title'])?></h6>
          <span class="badge bg-light text-dark border" style="font-size:.68rem"><?=ucfirst($a['target_audience'])?></span>
          <?php if($a['target_program']): ?><span class="badge bg-light text-primary border" style="font-size:.68rem"><?=htmlspecialchars($a['target_program'])?></span><?php endif; ?>
        </div>
        <p style="font-size:.87rem;color:#636e72;margin:.4rem 0;white-space:pre-wrap"><?=htmlspecialchars($a['content'])?></p>
        <?php if($a['attachment']): ?>
          <a href="<?=BASE_URL?>/assets/uploads/<?=htmlspecialchars($a['attachment'])?>" class="btn btn-sm btn-outline-secondary" target="_blank">
            <i class="bi bi-paperclip me-1"></i><?=htmlspecialchars($a['attachment_name']??'Attachment')?>
          </a>
        <?php endif; ?>
      </div>
      <div class="d-flex flex-column align-items-end gap-1">
        <small class="text-muted"><?=formatDateTime($a['created_at'])?></small>
        <div class="d-flex gap-1">
          <button class="btn btn-sm btn-outline-primary edit-ann-btn"
            data-id="<?=$a['id']?>"
            data-title="<?=htmlspecialchars($a['title'])?>"
            data-content="<?=htmlspecialchars($a['content'])?>"
            data-audience="<?=$a['target_audience']?>"
            data-program="<?=htmlspecialchars($a['target_program']??'')?>"
            data-pinned="<?=$a['is_pinned']?>">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger delete-ann-btn" data-id="<?=$a['id']?>" data-title="<?=htmlspecialchars($a['title'])?>">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; endif; ?>

<!-- Create/Edit Modal -->
<div class="modal fade" id="annModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:14px">
      <div class="modal-header" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:14px 14px 0 0">
        <h6 class="modal-title fw-bold mb-0" id="annModalTitle"><i class="bi bi-megaphone me-2"></i>New Announcement</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="annAlert" class="alert d-none"></div>
        <form id="annForm" enctype="multipart/form-data" novalidate>
          <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
          <input type="hidden" name="action" id="annAction" value="create">
          <input type="hidden" name="announcement_id" id="annId" value="">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" id="annTitle" class="form-control" placeholder="Announcement title..." required>
            </div>
            <div class="col-12">
              <label class="form-label">Content <span class="text-danger">*</span></label>
              <textarea name="content" id="annContent" class="form-control" rows="5" placeholder="Write your announcement..." required></textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Audience <span class="text-danger">*</span></label>
              <select name="target_audience" id="annAudience" class="form-select" required>
                <option value="all">All (Students + Advisers)</option>
                <option value="students">Students Only</option>
                <option value="advisers">Advisers Only</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Target Program (Optional)</label>
              <select name="target_program" id="annProgram" class="form-select">
                <option value="">All Programs</option>
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSIS">BSIS</option>
                <option value="BSBA">BSBA</option>
                <option value="BSA">BSA</option>
                <option value="BSHRM">BSHRM</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Options</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_pinned" id="annPinned" value="1">
                <label class="form-check-label" for="annPinned">📌 Pin this announcement</label>
              </div>
            </div>
            <div class="col-12" id="attachField">
              <label class="form-label">Attachment (Optional)</label>
              <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.png,.zip">
              <small class="text-muted">Max 10MB. PDF, DOC, DOCX, JPG, PNG, ZIP</small>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" id="saveAnnBtn">
          <i class="bi bi-send me-1"></i>Post Announcement
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Edit announcement
document.querySelectorAll('.edit-ann-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('annModalTitle').innerHTML='<i class="bi bi-pencil me-2"></i>Edit Announcement';
    document.getElementById('annAction').value='update';
    document.getElementById('annId').value=btn.dataset.id;
    document.getElementById('annTitle').value=btn.dataset.title;
    document.getElementById('annContent').value=btn.dataset.content;
    document.getElementById('annAudience').value=btn.dataset.audience;
    document.getElementById('annProgram').value=btn.dataset.program;
    document.getElementById('annPinned').checked=btn.dataset.pinned==='1';
    document.getElementById('attachField').style.display='none'; // hide on edit
    document.getElementById('annAlert').className='alert d-none';
    new bootstrap.Modal(document.getElementById('annModal')).show();
  });
});

// New modal reset
document.querySelector('[data-bs-target="#annModal"]')?.addEventListener('click',()=>{
  document.getElementById('annModalTitle').innerHTML='<i class="bi bi-megaphone me-2"></i>New Announcement';
  document.getElementById('annAction').value='create';
  document.getElementById('annId').value='';
  document.getElementById('annForm').reset();
  document.getElementById('attachField').style.display='';
  document.getElementById('annAlert').className='alert d-none';
});

document.getElementById('saveAnnBtn').addEventListener('click',async()=>{
  const alertDiv=document.getElementById('annAlert');
  const btn=document.getElementById('saveAnnBtn');
  btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Posting...';

  const formData=new FormData(document.getElementById('annForm'));
  formData.append('csrf_token',window.CSRF_TOKEN||'');

  const res=await fetch('/PapeLESS/coordinator/announcement_handler.php',{method:'POST',body:formData});
  const json=await res.json();

  if(json.success){
    showToast(json.message,'success');
    bootstrap.Modal.getInstance(document.getElementById('annModal')).hide();
    setTimeout(()=>location.reload(),800);
  } else {
    alertDiv.className='alert alert-danger';
    alertDiv.textContent=json.error;
  }
  btn.disabled=false; btn.innerHTML='<i class="bi bi-send me-1"></i>Post Announcement';
});

document.querySelectorAll('.delete-ann-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    confirmAction(`Delete announcement: "${btn.dataset.title}"?`,async()=>{
      const res=await ajaxPost('/PapeLESS/coordinator/announcement_handler.php',{action:'delete',announcement_id:btn.dataset.id});
      if(res.success){showToast(res.message,'success');location.reload();}else showToast(res.error,'error');
    });
  });
});
</script>

<?php include __DIR__.'/../components/footer.php'; ?>
