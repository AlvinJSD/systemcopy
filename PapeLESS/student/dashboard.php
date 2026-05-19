<?php
// ============================================================
// PapeLESS - Student Dashboard
// ============================================================
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId   = $_SESSION['user_id'];
$userType = 'student';
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$db   = getDB();
$user = getCurrentUser();

// Stats
$totalReqs = $db->query("SELECT COUNT(*) FROM submission_requirements WHERE is_active=1")->fetchColumn();

$subStmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM submissions WHERE student_id=? GROUP BY status");
$subStmt->execute([$userId]);
$subStats = [];
foreach ($subStmt->fetchAll() as $r) $subStats[$r['status']] = $r['cnt'];

$approvedCount  = $subStats['approved']  ?? 0;
$pendingCount   = $subStats['pending']   ?? 0;
$revisionCount  = $subStats['needs_revision'] ?? 0;
$progress       = $totalReqs > 0 ? round(($approvedCount / $totalReqs) * 100) : 0;

// Adviser info
$adviser = null;
if ($user['adviser_id']) {
    $adv = $db->prepare("SELECT * FROM advisers WHERE id=?");
    $adv->execute([$user['adviser_id']]);
    $adviser = $adv->fetch();
}

// Recent submissions
$recentStmt = $db->prepare("
    SELECT s.*, r.requirement_name 
    FROM submissions s JOIN submission_requirements r ON r.id=s.requirement_id
    WHERE s.student_id=? ORDER BY s.submitted_at DESC LIMIT 5
");
$recentStmt->execute([$userId]);
$recentSubs = $recentStmt->fetchAll();

// Unread announcements (latest 3)
$annStmt = $db->query("SELECT * FROM announcements WHERE target_audience IN ('all','students') ORDER BY is_pinned DESC, created_at DESC LIMIT 3");
$announcements = $annStmt->fetchAll();

// Survey check
$hasSurveyToday = hasSurveyToday($userId);

include __DIR__ . '/../components/header.php';
?>

<?php if (!$hasSurveyToday): ?>
<!-- Daily Survey Modal Overlay -->
<div class="survey-modal-overlay" id="surveyModalOverlay">
  <div class="survey-modal">
    <div class="survey-header">
      <div style="font-size:2rem">😊</div>
      <h5>Daily Well-being Check-in</h5>
      <p><?= date('l, F d, Y') ?> · This takes only 1 minute</p>
    </div>
    <div class="survey-body">
      <form id="surveyForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

        <!-- Q1: Mood -->
        <div class="survey-q">
          <label>1. How are you feeling today?</label>
          <div class="emoji-rating">
            <button type="button" class="emoji-btn" data-value="1">😞<span class="emoji-lbl">Very Bad</span></button>
            <button type="button" class="emoji-btn" data-value="2">😕<span class="emoji-lbl">Bad</span></button>
            <button type="button" class="emoji-btn" data-value="3">😐<span class="emoji-lbl">Okay</span></button>
            <button type="button" class="emoji-btn" data-value="4">🙂<span class="emoji-lbl">Good</span></button>
            <button type="button" class="emoji-btn" data-value="5">😄<span class="emoji-lbl">Great</span></button>
          </div>
          <input type="hidden" name="q1_mood">
        </div>

        <!-- Q2: Stress -->
        <div class="survey-q">
          <label>2. How is your stress level today?</label>
          <div class="emoji-rating">
            <button type="button" class="emoji-btn" data-value="1">😌<span class="emoji-lbl">No Stress</span></button>
            <button type="button" class="emoji-btn" data-value="2">🙂<span class="emoji-lbl">Low</span></button>
            <button type="button" class="emoji-btn" data-value="3">😐<span class="emoji-lbl">Moderate</span></button>
            <button type="button" class="emoji-btn" data-value="4">😥<span class="emoji-lbl">High</span></button>
            <button type="button" class="emoji-btn" data-value="5">😰<span class="emoji-lbl">Very High</span></button>
          </div>
          <input type="hidden" name="q2_stress">
        </div>

        <!-- Q3: Consultation -->
        <div class="survey-q">
          <label>3. Do you need a consultation with your adviser?</label>
          <div class="yes-no-btns">
            <button type="button" class="yes-no-btn yes" data-value="1">✅ Yes, I need help</button>
            <button type="button" class="yes-no-btn no"  data-value="0">🙅 No, I'm fine</button>
          </div>
          <input type="hidden" name="q3_needs_consultation">
        </div>

        <!-- Q4: Company problem -->
        <div class="survey-q">
          <label>4. Are you experiencing any problems in your company?</label>
          <div class="yes-no-btns">
            <button type="button" class="yes-no-btn yes" data-value="1">⚠️ Yes, there are issues</button>
            <button type="button" class="yes-no-btn no"  data-value="0">👍 No problems</button>
          </div>
          <input type="hidden" name="q4_company_problem">
        </div>

        <!-- Q5: Experience rating -->
        <div class="survey-q">
          <label>5. Rate your internship experience today</label>
          <div class="emoji-rating">
            <button type="button" class="emoji-btn" data-value="1">⭐<span class="emoji-lbl">1 Star</span></button>
            <button type="button" class="emoji-btn" data-value="2">⭐⭐<span class="emoji-lbl">2 Stars</span></button>
            <button type="button" class="emoji-btn" data-value="3">⭐⭐⭐<span class="emoji-lbl">3 Stars</span></button>
            <button type="button" class="emoji-btn" data-value="4">⭐⭐⭐⭐<span class="emoji-lbl">4 Stars</span></button>
            <button type="button" class="emoji-btn" data-value="5">⭐⭐⭐⭐⭐<span class="emoji-lbl">5 Stars</span></button>
          </div>
          <input type="hidden" name="q5_experience_rating">
        </div>

        <!-- Notes -->
        <div class="survey-q">
          <label>Any additional notes? (Optional)</label>
          <textarea name="additional_notes" class="form-control" rows="2" placeholder="Share anything you'd like your adviser to know..."></textarea>
        </div>

        <button type="submit" class="btn btn-danger w-100 mt-2">
          <i class="bi bi-send me-2"></i>Submit Check-in
        </button>
        <button type="button" class="btn btn-link w-100 text-muted mt-1" onclick="document.getElementById('surveyModalOverlay').remove()" style="font-size:.8rem">
          Remind me later
        </button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="stat-card red">
      <div class="stat-icon"><i class="bi bi-folder2-open"></i></div>
      <div>
        <div class="stat-value"><?= $approvedCount ?>/<?= $totalReqs ?></div>
        <div class="stat-label">Approved Docs</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card orange">
      <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
      <div>
        <div class="stat-value"><?= $pendingCount ?></div>
        <div class="stat-label">Pending Review</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card blue">
      <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
      <div>
        <div class="stat-value"><?= $revisionCount ?></div>
        <div class="stat-label">Needs Revision</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card green">
      <div class="stat-icon"><i class="bi bi-percent"></i></div>
      <div>
        <div class="stat-value"><?= $progress ?>%</div>
        <div class="stat-label">Completion Rate</div>
      </div>
    </div>
  </div>
</div>

<!-- Progress Bar -->
<div class="card mb-4">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <span style="font-weight:700;font-size:.9rem;color:var(--secondary)"><i class="bi bi-graph-up me-1 text-danger"></i>Overall Document Progress</span>
      <span class="badge bg-danger"><?= $progress ?>%</span>
    </div>
    <div class="progress mb-2">
      <div class="progress-bar" style="width:<?= $progress ?>%"></div>
    </div>
    <div class="d-flex gap-3" style="font-size:.75rem;color:#6c757d">
      <span><i class="bi bi-circle-fill text-success me-1"></i>Approved: <?= $approvedCount ?></span>
      <span><i class="bi bi-circle-fill text-warning me-1"></i>Pending: <?= $pendingCount ?></span>
      <span><i class="bi bi-circle-fill text-danger me-1"></i>Needs Revision: <?= $revisionCount ?></span>
      <span><i class="bi bi-circle-fill text-secondary me-1"></i>Not Submitted: <?= max(0, $totalReqs - $approvedCount - $pendingCount - $revisionCount) ?></span>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Left Column -->
  <div class="col-lg-8">

    <!-- Recent Submissions -->
    <div class="card mb-4">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-clock-history me-2 text-danger"></i>Recent Submissions</span>
        <a href="<?= BASE_URL ?>/student/submissions.php" class="btn btn-sm btn-outline-danger">View All</a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentSubs)): ?>
          <div class="empty-state">
            <i class="bi bi-folder-x"></i>
            <p>No submissions yet. <a href="<?= BASE_URL ?>/student/submissions.php" style="color:var(--primary)">Upload your first document →</a></p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead><tr>
                <th class="ps-4">Document</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr></thead>
              <tbody>
              <?php foreach ($recentSubs as $sub): ?>
                <tr>
                  <td class="ps-4">
                    <div class="d-flex align-items-center gap-2">
                      <i class="bi <?= getFileIcon($sub['file_type']) ?> fs-5"></i>
                      <div>
                        <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($sub['requirement_name']) ?></div>
                        <div style="font-size:.72rem;color:#6c757d"><?= htmlspecialchars($sub['original_name']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td><?= statusBadge($sub['status']) ?></td>
                  <td style="font-size:.8rem;color:#6c757d"><?= formatDate($sub['submitted_at'], 'M d, Y') ?></td>
                  <td>
                    <a href="<?= BASE_URL ?>/<?= htmlspecialchars($sub['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Announcements -->
    <div class="card">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-megaphone me-2 text-danger"></i>Latest Announcements</span>
        <a href="<?= BASE_URL ?>/student/announcements.php" class="btn btn-sm btn-outline-danger">View All</a>
      </div>
      <div class="card-body">
        <?php if (empty($announcements)): ?>
          <div class="empty-state py-3"><i class="bi bi-megaphone fs-2 d-block mb-2"></i><p>No announcements yet.</p></div>
        <?php else: foreach ($announcements as $ann): ?>
          <div class="mb-3 pb-3 border-bottom">
            <div class="d-flex justify-content-between align-items-start">
              <h6 style="font-weight:700;font-size:.9rem;margin-bottom:.25rem">
                <?php if ($ann['is_pinned']): ?><i class="bi bi-pin-fill text-danger me-1"></i><?php endif; ?>
                <?= htmlspecialchars($ann['title']) ?>
              </h6>
              <small class="text-muted"><?= timeAgo($ann['created_at']) ?></small>
            </div>
            <p style="font-size:.83rem;color:#636e72;margin:0"><?= htmlspecialchars(substr($ann['content'], 0, 150)) ?>...</p>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

  </div><!-- /left -->

  <!-- Right Column -->
  <div class="col-lg-4">

    <!-- Profile Card -->
    <div class="card mb-4">
      <div class="card-body text-center pt-4">
        <div class="avatar-initials-lg mx-auto mb-3">
          <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
        </div>
        <h6 style="font-weight:700;font-size:1rem"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h6>
        <p style="font-size:.8rem;color:#6c757d;margin:0"><?= htmlspecialchars($user['student_number']) ?></p>
        <p style="font-size:.8rem;color:var(--primary);font-weight:600"><?= htmlspecialchars($user['program']) ?> – <?= $user['year_level'] ?>-<?= $user['section'] ?></p>
        <div class="divider"></div>
        <div class="text-start">
          <div class="row g-2">
            <div class="col-6">
              <div class="info-label">Company</div>
              <div class="info-value" style="font-size:.8rem"><?= htmlspecialchars($user['company_name']) ?></div>
            </div>
            <div class="col-6">
              <div class="info-label">Role</div>
              <div class="info-value" style="font-size:.8rem"><?= htmlspecialchars($user['department_role']) ?></div>
            </div>
            <div class="col-6">
              <div class="info-label">Start Date</div>
              <div class="info-value" style="font-size:.8rem"><?= formatDate($user['internship_start']) ?></div>
            </div>
            <div class="col-6">
              <div class="info-label">Schedule</div>
              <div class="info-value" style="font-size:.8rem"><?= date('h:i A', strtotime($user['time_in'])) ?> – <?= date('h:i A', strtotime($user['time_out'])) ?></div>
            </div>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/student/profile.php" class="btn btn-outline-danger btn-sm mt-3 w-100">
          <i class="bi bi-pencil me-1"></i>Edit Profile
        </a>
      </div>
    </div>

    <!-- Adviser Card -->
    <div class="card mb-4">
      <div class="card-header"><i class="bi bi-person-badge me-2 text-danger"></i>My Adviser</div>
      <div class="card-body">
        <?php if ($adviser): ?>
          <div class="d-flex align-items-center gap-3 mb-3">
            <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#2980b9,#3498db);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.1rem">
              <?= strtoupper(substr($adviser['first_name'],0,1).substr($adviser['last_name'],0,1)) ?>
            </div>
            <div>
              <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($adviser['first_name'].' '.$adviser['last_name']) ?></div>
              <div style="font-size:.75rem;color:#6c757d"><?= htmlspecialchars($adviser['program']) ?> Yr <?= $adviser['year_level'] ?> Adviser</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.82rem">
            <i class="bi bi-envelope text-muted"></i>
            <span><?= htmlspecialchars($adviser['email']) ?></span>
          </div>
          <a href="<?= BASE_URL ?>/student/messages.php" class="btn btn-outline-primary btn-sm w-100">
            <i class="bi bi-chat-dots me-1"></i>Message Adviser
          </a>
        <?php else: ?>
          <div class="text-center text-muted py-2">
            <i class="bi bi-person-x fs-3 d-block mb-1"></i>
            <small>No adviser assigned yet</small>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Quick Links -->
    <div class="card">
      <div class="card-header"><i class="bi bi-lightning me-2 text-danger"></i>Quick Actions</div>
      <div class="card-body d-flex flex-column gap-2">
        <a href="<?= BASE_URL ?>/student/submissions.php" class="btn btn-outline-danger btn-sm">
          <i class="bi bi-cloud-upload me-2"></i>Upload Document
        </a>
        <a href="<?= BASE_URL ?>/student/survey.php" class="btn btn-outline-success btn-sm">
          <i class="bi bi-clipboard2-heart me-2"></i>Daily Check-in
        </a>
        <a href="<?= BASE_URL ?>/student/guidelines.php" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-book me-2"></i>View Guidelines
        </a>
        <a href="<?= BASE_URL ?>/student/messages.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-chat me-2"></i>Message Adviser
        </a>
      </div>
    </div>

  </div><!-- /right -->
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
