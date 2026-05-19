<?php
// ============================================================
// PapeLESS - Coordinator: Adviser Management
// ============================================================
require_once __DIR__.'/../includes/functions.php';
requireRole('coordinator');
$userId=$_SESSION['user_id']; $userType='coordinator'; $pageTitle='Adviser Management'; $activeNav='advisers';
$db=getDB();

$success=$error='';

// Handle GET action=add or ?edit=id
$editAdviser=null;
if(isset($_GET['edit'])){
    $ea=$db->prepare("SELECT * FROM advisers WHERE id=?");
    $ea->execute([(int)$_GET['edit']]);
    $editAdviser=$ea->fetch();
}

// Advisers list
$search=$_GET['search']??'';
$sql="SELECT a.*,(SELECT COUNT(*) FROM students s WHERE s.adviser_id=a.id AND s.status='approved') as student_count FROM advisers a WHERE 1";
$params=[];
if($search){$sql.=" AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.employee_id LIKE ? OR a.program LIKE ?)";$s="%$search%";$params=[$s,$s,$s,$s];}
$sql.=" ORDER BY a.program,a.year_level,a.last_name";
$stmt=$db->prepare($sql); $stmt->execute($params); $advisers=$stmt->fetchAll();

include __DIR__.'/../components/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div><h5 class="fw-bold mb-0">Adviser Management</h5><small class="text-muted">Create, assign, and manage adviser accounts</small></div>
  <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#adviserModal" id="addAdviserBtn">
    <i class="bi bi-person-plus me-2"></i>Add Adviser
  </button>
</div>

<!-- Search -->
<div class="card mb-4">
  <div class="card-body py-2">
    <form method="GET" class="search-box" style="max-width:300px">
      <i class="bi bi-search"></i>
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search advisers..." value="<?=htmlspecialchars($search)?>">
    </form>
  </div>
</div>

<!-- Advisers Table -->
<div class="card">
  <div class="card-body p-0">
    <?php if(empty($advisers)): ?>
      <div class="empty-state"><i class="bi bi-person-badge"></i><p>No advisers found. <a href="#" data-bs-toggle="modal" data-bs-target="#adviserModal">Add the first adviser →</a></p></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4">Adviser</th>
          <th>Employee ID</th>
          <th>Program</th>
          <th>Year Level</th>
          <th>Students</th>
          <th>Status</th>
          <th>Actions</th>
        </tr></thead>
        <tbody id="adviserTableBody">
        <?php foreach($advisers as $a): ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-2">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#2980b9,#3498db);color:#fff;font-weight:700;font-size:.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                  <?=strtoupper(substr($a['first_name'],0,1).substr($a['last_name'],0,1))?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:.85rem"><?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?></div>
                  <div style="font-size:.72rem;color:#6c757d"><?=htmlspecialchars($a['email'])?></div>
                </div>
              </div>
            </td>
            <td style="font-size:.83rem"><?=htmlspecialchars($a['employee_id'])?></td>
            <td style="font-size:.83rem;font-weight:600"><?=htmlspecialchars($a['program'])?></td>
            <td><span class="badge bg-light text-dark border">Year <?=$a['year_level']?></span></td>
            <td><span class="badge bg-primary"><?=$a['student_count']?> students</span></td>
            <td>
              <?php if($a['is_active']): ?>
                <span class="badge bg-success">Active</span>
              <?php else: ?>
                <span class="badge bg-secondary">Inactive</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary edit-adviser-btn"
                  data-id="<?=$a['id']?>"
                  data-empid="<?=htmlspecialchars($a['employee_id'])?>"
                  data-fn="<?=htmlspecialchars($a['first_name'])?>"
                  data-mn="<?=htmlspecialchars($a['middle_name']??'')?>"
                  data-ln="<?=htmlspecialchars($a['last_name'])?>"
                  data-email="<?=htmlspecialchars($a['email'])?>"
                  data-phone="<?=htmlspecialchars($a['contact_number']??'')?>"
                  data-program="<?=htmlspecialchars($a['program'])?>"
                  data-year="<?=$a['year_level']?>"
                  data-active="<?=$a['is_active']?>"
                  title="Edit">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-warning reset-pwd-btn" data-id="<?=$a['id']?>" data-name="<?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?>" title="Reset Password">
                  <i class="bi bi-key"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger delete-adviser-btn" data-id="<?=$a['id']?>" data-name="<?=htmlspecialchars($a['first_name'].' '.$a['last_name'])?>" title="Delete">
                  <i class="bi bi-trash"></i>
                </button>
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

<!-- Add/Edit Adviser Modal -->
<div class="modal fade" id="adviserModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:14px">
      <div class="modal-header" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:14px 14px 0 0">
        <h6 class="modal-title fw-bold mb-0" id="adviserModalTitle"><i class="bi bi-person-plus me-2"></i>Add Adviser</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="adviserAlert" class="alert d-none"></div>
        <form id="adviserForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
          <input type="hidden" name="action" id="adviserAction" value="create">
          <input type="hidden" name="adviser_id" id="adviserEditId" value="">

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Employee ID <span class="text-danger">*</span></label>
              <input type="text" name="employee_id" id="advEmpId" class="form-control" placeholder="ADV-XXX" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">First Name <span class="text-danger">*</span></label>
              <input type="text" name="first_name" id="advFn" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Middle Name</label>
              <input type="text" name="middle_name" id="advMn" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Last Name <span class="text-danger">*</span></label>
              <input type="text" name="last_name" id="advLn" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" name="email" id="advEmail" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Contact Number</label>
              <input type="tel" name="contact_number" id="advPhone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Password <span class="text-danger" id="pwdRequired">*</span> <span id="pwdNote" class="text-muted fw-normal" style="font-size:.75rem"></span></label>
              <input type="password" name="password" id="advPassword" class="form-control" placeholder="Min. 8 characters">
            </div>
            <div class="col-md-6">
              <label class="form-label">Program / Course <span class="text-danger">*</span></label>
              <select name="program" id="advProgram" class="form-select" required>
                <option value="">Select Program</option>
                <option value="BSIT">BSIT</option>
                <option value="BSCS">BSCS</option>
                <option value="BSIS">BSIS</option>
                <option value="BSBA">BSBA</option>
                <option value="BSA">BSA</option>
                <option value="BSHRM">BSHRM</option>
                <option value="BSE">BSE</option>
                <option value="BSED">BSED</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Year Level Assignment <span class="text-danger">*</span></label>
              <select name="year_level" id="advYear" class="form-select" required>
                <option value="">Select Year</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
            <div class="col-md-6" id="activeField" style="display:none">
              <label class="form-label">Account Status</label>
              <select name="is_active" id="advActive" class="form-select">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" id="saveAdviserBtn">
          <i class="bi bi-save me-1"></i>Save Adviser
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPwdModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow" style="border-radius:14px">
      <div class="modal-body p-4">
        <h6 class="fw-bold mb-1">Reset Password</h6>
        <p class="text-muted mb-3" id="resetAdviserName" style="font-size:.85rem"></p>
        <input type="hidden" id="resetAdviserId">
        <div class="mb-3">
          <label class="form-label fw-bold" style="font-size:.82rem">New Password <span class="text-danger">*</span></label>
          <input type="password" id="newPassword" class="form-control form-control-sm" placeholder="Min. 8 characters">
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary btn-sm flex-fill" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-danger btn-sm flex-fill" id="doResetBtn">Reset</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Edit adviser
document.querySelectorAll('.edit-adviser-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('adviserModalTitle').innerHTML='<i class="bi bi-pencil me-2"></i>Edit Adviser';
    document.getElementById('adviserAction').value='update';
    document.getElementById('adviserEditId').value=btn.dataset.id;
    document.getElementById('advEmpId').value=btn.dataset.empid;
    document.getElementById('advFn').value=btn.dataset.fn;
    document.getElementById('advMn').value=btn.dataset.mn;
    document.getElementById('advLn').value=btn.dataset.ln;
    document.getElementById('advEmail').value=btn.dataset.email;
    document.getElementById('advPhone').value=btn.dataset.phone;
    document.getElementById('advProgram').value=btn.dataset.program;
    document.getElementById('advYear').value=btn.dataset.year;
    document.getElementById('advActive').value=btn.dataset.active;
    document.getElementById('activeField').style.display='';
    document.getElementById('pwdRequired').style.display='none';
    document.getElementById('pwdNote').textContent='(Leave blank to keep current)';
    new bootstrap.Modal(document.getElementById('adviserModal')).show();
  });
});

document.getElementById('addAdviserBtn').addEventListener('click',()=>{
  document.getElementById('adviserModalTitle').innerHTML='<i class="bi bi-person-plus me-2"></i>Add Adviser';
  document.getElementById('adviserAction').value='create';
  document.getElementById('adviserEditId').value='';
  document.getElementById('adviserForm').reset();
  document.getElementById('activeField').style.display='none';
  document.getElementById('pwdRequired').style.display='';
  document.getElementById('pwdNote').textContent='';
  document.getElementById('adviserAlert').className='alert d-none';
});

document.getElementById('saveAdviserBtn').addEventListener('click',async()=>{
  const alertDiv=document.getElementById('adviserAlert');
  const btn=document.getElementById('saveAdviserBtn');
  btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

  const data=Object.fromEntries(new FormData(document.getElementById('adviserForm')));
  const res=await ajaxPost('/PapeLESS/coordinator/adviser_handler.php',data);

  if(res.success){
    showToast(res.message,'success');
    bootstrap.Modal.getInstance(document.getElementById('adviserModal')).hide();
    setTimeout(()=>location.reload(),800);
  } else {
    alertDiv.className='alert alert-danger';
    alertDiv.textContent=res.error;
  }
  btn.disabled=false; btn.innerHTML='<i class="bi bi-save me-1"></i>Save Adviser';
});

// Reset password
document.querySelectorAll('.reset-pwd-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    document.getElementById('resetAdviserId').value=btn.dataset.id;
    document.getElementById('resetAdviserName').textContent='Adviser: '+btn.dataset.name;
    document.getElementById('newPassword').value='';
    new bootstrap.Modal(document.getElementById('resetPwdModal')).show();
  });
});
document.getElementById('doResetBtn')?.addEventListener('click',async()=>{
  const pwd=document.getElementById('newPassword').value.trim();
  if(pwd.length<8){showToast('Password must be at least 8 characters.','error');return;}
  const res=await ajaxPost('/PapeLESS/coordinator/adviser_handler.php',{action:'reset_password',adviser_id:document.getElementById('resetAdviserId').value,new_password:pwd});
  if(res.success){showToast(res.message,'success');bootstrap.Modal.getInstance(document.getElementById('resetPwdModal')).hide();}
  else showToast(res.error,'error');
});

// Delete adviser
document.querySelectorAll('.delete-adviser-btn').forEach(btn=>{
  btn.addEventListener('click',()=>{
    confirmAction(`Delete adviser ${btn.dataset.name}? This cannot be undone.`,async()=>{
      const res=await ajaxPost('/PapeLESS/coordinator/adviser_handler.php',{action:'delete',adviser_id:btn.dataset.id});
      if(res.success){showToast(res.message,'success');location.reload();}else showToast(res.error,'error');
    });
  });
});
</script>

<?php include __DIR__.'/../components/footer.php'; ?>
