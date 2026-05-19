<?php
// ============================================================
// PapeLESS - Student Submissions
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId   = $_SESSION['user_id'];
$userType = 'student';
$pageTitle = 'My Submissions';
$activeNav = 'submissions';

$db = getDB();

// Get all requirements with latest submission status
$reqStmt = $db->prepare("
    SELECT r.*,
           s.id AS sub_id, s.status, s.file_name, s.original_name, s.file_type,
           s.file_size, s.file_path, s.adviser_comment, s.submitted_at, s.reviewed_at
    FROM submission_requirements r
    LEFT JOIN (
        SELECT * FROM submissions s1
        WHERE s1.student_id = ?
          AND s1.id = (SELECT MAX(s2.id) FROM submissions s2 WHERE s2.student_id = s1.student_id AND s2.requirement_id = s1.requirement_id)
    ) s ON s.requirement_id = r.id
    WHERE r.is_active = 1
    ORDER BY r.sort_order
");
$reqStmt->execute([$userId]);
$requirements = $reqStmt->fetchAll();

// Group by week
$grouped = [];
foreach ($requirements as $req) {
    $key = $req['week_number'] !== null ? 'Week ' . $req['week_number'] : 'General Documents';
    $grouped[$key][] = $req;
}

include __DIR__ . '/../components/header.php';
?>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:14px">
      <div class="modal-header" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:14px 14px 0 0">
        <h6 class="modal-title fw-bold mb-0" id="uploadModalTitle"><i class="bi bi-cloud-upload me-2"></i>Upload Document</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div id="uploadAlert" class="alert d-none"></div>
        <form id="uploadForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
          <input type="hidden" name="requirement_id" id="uploadReqId">

          <div class="mb-3">
            <label class="form-label fw-bold">Requirement</label>
            <div id="uploadReqName" class="p-2 rounded" style="background:#f8f9fa;font-size:.9rem;color:var(--secondary)"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Select File <span class="text-danger">*</span></label>
            <div class="upload-zone" id="uploadZone">
              <i class="bi bi-cloud-arrow-up mb-2"></i>
              <p>Click to browse or drag & drop</p>
              <small>Allowed: PDF, DOC, DOCX, JPG, PNG, ZIP (max 10MB)</small>
              <input type="file" name="submission_file" id="submissionFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" class="d-none" required>
            </div>
          </div>

          <div id="uploadProgress" class="d-none">
            <div class="d-flex justify-content-between mb-1" style="font-size:.8rem">
              <span>Uploading...</span><span id="uploadPercent">0%</span>
            </div>
            <div class="progress">
              <div class="progress-bar" id="uploadProgressBar" style="width:0%"></div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm" id="doUploadBtn">
          <i class="bi bi-cloud-upload me-1"></i>Upload File
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-0">Document Submissions</h5>
    <small class="text-muted">Track and manage your OJT document uploads</small>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge bg-danger"><?= array_sum(array_map(fn($r)=>$r['status']==='approved'?1:0, $requirements)) ?> / <?= count($requirements) ?> Approved</span>
  </div>
</div>

<!-- Legend -->
<div class="card mb-4">
  <div class="card-body py-2">
    <div class="d-flex gap-3 flex-wrap" style="font-size:.78rem">
      <span><i class="bi bi-circle-fill text-secondary me-1"></i>Not Submitted</span>
      <span><i class="bi bi-circle-fill text-warning me-1"></i>Pending Review</span>
      <span><i class="bi bi-circle-fill text-success me-1"></i>Approved</span>
      <span style="color:#e67e22"><i class="bi bi-circle-fill me-1"></i>Needs Revision</span>
      <span><i class="bi bi-circle-fill text-info me-1"></i>Resubmitted</span>
    </div>
  </div>
</div>

<!-- Requirements grouped -->
<?php foreach ($grouped as $group => $reqs): ?>
<div class="card mb-4">
  <div class="card-header">
    <i class="bi bi-<?= str_starts_with($group, 'Week') ? 'calendar-week' : 'folder' ?> me-2 text-danger"></i>
    <?= htmlspecialchars($group) ?>
    <span class="badge bg-light text-dark ms-2" style="font-size:.7rem">
      <?= count(array_filter($reqs, fn($r) => $r['status'] === 'approved')) ?>/<?= count($reqs) ?> approved
    </span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th class="ps-4" style="width:40%">Requirement</th>
            <th>Status</th>
            <th>File</th>
            <th>Date</th>
            <th>Adviser Feedback</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($reqs as $req): ?>
          <tr>
            <td class="ps-4">
              <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($req['requirement_name']) ?></div>
              <?php if ($req['description']): ?>
                <div style="font-size:.72rem;color:#6c757d"><?= htmlspecialchars($req['description']) ?></div>
              <?php endif; ?>
              <?php if ($req['is_required']): ?>
                <span class="badge bg-light text-danger border border-danger" style="font-size:.65rem">Required</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($req['sub_id']): ?>
                <?= statusBadge($req['status']) ?>
              <?php else: ?>
                <span class="badge bg-secondary">Not Submitted</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($req['sub_id'] && $req['file_name']): ?>
                <div class="d-flex align-items-center gap-1">
                  <i class="bi <?= getFileIcon($req['file_type']) ?>"></i>
                  <span style="font-size:.75rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($req['original_name']) ?>">
                    <?= htmlspecialchars($req['original_name']) ?>
                  </span>
                </div>
                <div style="font-size:.7rem;color:#adb5bd"><?= formatFileSize((int)$req['file_size']) ?></div>
              <?php else: ?>
                <span class="text-muted" style="font-size:.8rem">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($req['submitted_at']): ?>
                <span style="font-size:.78rem;color:#6c757d"><?= formatDate($req['submitted_at'],'M d, Y') ?></span>
              <?php else: ?>
                <span class="text-muted" style="font-size:.8rem">—</span>
              <?php endif; ?>
            </td>
            <td style="max-width:180px">
              <?php if ($req['adviser_comment']): ?>
                <span style="font-size:.78rem;color:#636e72" title="<?= htmlspecialchars($req['adviser_comment']) ?>">
                  <?= htmlspecialchars(substr($req['adviser_comment'], 0, 60)) ?>...
                </span>
              <?php else: ?>
                <span class="text-muted" style="font-size:.8rem">No feedback</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-1">
                <?php if ($req['sub_id'] && $req['file_path']): ?>
                  <a href="<?= BASE_URL ?>/<?= htmlspecialchars($req['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                <?php endif; ?>
                <?php if (!$req['sub_id'] || in_array($req['status'], ['needs_revision','resubmitted'])): ?>
                  <button class="btn btn-sm btn-danger upload-btn"
                          data-req-id="<?= $req['id'] ?>"
                          data-req-name="<?= htmlspecialchars($req['requirement_name']) ?>"
                          data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="bi bi-upload"></i>
                    <?= $req['status'] === 'needs_revision' ? 'Resubmit' : 'Upload' ?>
                  </button>
                <?php elseif ($req['status'] === 'pending'): ?>
                  <span style="font-size:.75rem;color:#f39c12"><i class="bi bi-hourglass-split me-1"></i>Waiting</span>
                <?php elseif ($req['status'] === 'approved'): ?>
                  <span style="font-size:.75rem;color:var(--success)"><i class="bi bi-check-circle-fill me-1"></i>Done</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endforeach; ?>

<script>
// Populate upload modal
document.querySelectorAll('.upload-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.getElementById('uploadReqId').value = btn.dataset.reqId;
    document.getElementById('uploadReqName').textContent = btn.dataset.reqName;
    document.getElementById('uploadModalTitle').innerHTML = '<i class="bi bi-cloud-upload me-2"></i>' + btn.dataset.reqName;
    document.getElementById('uploadAlert').className = 'alert d-none';
    document.getElementById('submissionFile').value = '';
    document.querySelector('#uploadZone p').textContent = 'Click to browse or drag & drop';
  });
});

// Upload handler
document.getElementById('doUploadBtn').addEventListener('click', async () => {
  const fileInput = document.getElementById('submissionFile');
  const reqId     = document.getElementById('uploadReqId').value;
  const alertDiv  = document.getElementById('uploadAlert');

  if (!fileInput.files.length) {
    alertDiv.className = 'alert alert-warning';
    alertDiv.textContent = 'Please select a file first.';
    return;
  }

  const btn = document.getElementById('doUploadBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading...';

  document.getElementById('uploadProgress').classList.remove('d-none');

  const formData = new FormData(document.getElementById('uploadForm'));
  formData.append('action', 'upload');

  // Simulate progress
  let progress = 0;
  const interval = setInterval(() => {
    progress = Math.min(progress + 10, 85);
    document.getElementById('uploadProgressBar').style.width = progress + '%';
    document.getElementById('uploadPercent').textContent = progress + '%';
  }, 200);

  const res  = await fetch('/PapeLESS/student/submission_handler.php', { method: 'POST', body: formData });
  const json = await res.json();

  clearInterval(interval);
  document.getElementById('uploadProgressBar').style.width = '100%';
  document.getElementById('uploadPercent').textContent = '100%';

  if (json.success) {
    alertDiv.className = 'alert alert-success';
    alertDiv.innerHTML = '<i class="bi bi-check-circle me-1"></i>' + json.message;
    setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('uploadModal')).hide(); location.reload(); }, 1500);
  } else {
    alertDiv.className = 'alert alert-danger';
    alertDiv.textContent = json.error;
    document.getElementById('uploadProgress').classList.add('d-none');
  }

  btn.disabled = false;
  btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Upload File';
});
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
