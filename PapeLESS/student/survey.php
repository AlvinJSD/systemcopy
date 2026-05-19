<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId   = $_SESSION['user_id'];
$userType = 'student';
$pageTitle = 'Daily Survey';
$activeNav = 'survey';

$db = getDB();

$hasSurveyToday = hasSurveyToday($userId);

// Fetch past 7 surveys
$histStmt = $db->prepare("SELECT * FROM surveys WHERE student_id = ? ORDER BY survey_date DESC LIMIT 14");
$histStmt->execute([$userId]);
$history = $histStmt->fetchAll();

$moodLabels = ['', '😞 Very Bad', '😕 Bad', '😐 Okay', '🙂 Good', '😄 Great'];
$stressLabels = ['', '😌 No Stress', '🙂 Low', '😐 Moderate', '😥 High', '😰 Very High'];

include __DIR__ . '/../components/header.php';
?>

<div class="row g-4">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-clipboard2-heart me-2 text-danger"></i>Daily Well-being Check-in</div>
      <div class="card-body">
        <?php if ($hasSurveyToday): ?>
          <div class="text-center py-4">
            <div style="font-size:3rem">✅</div>
            <h5 class="mt-3 fw-bold">Check-in Complete!</h5>
            <p class="text-muted" style="font-size:.9rem">You've already submitted your well-being check-in for today (<?= date('F d, Y') ?>).</p>
            <p class="text-muted" style="font-size:.85rem">Come back tomorrow for your next check-in.</p>
          </div>
        <?php else: ?>
          <p class="text-muted mb-4" style="font-size:.85rem">
            <i class="bi bi-info-circle text-primary me-1"></i>
            Complete your daily check-in so your adviser can monitor your well-being during your internship.
          </p>
          <form id="surveyForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

            <div class="survey-q">
              <label>1. How are you feeling today? <span class="text-danger">*</span></label>
              <div class="emoji-rating">
                <button type="button" class="emoji-btn" data-value="1">😞<span class="emoji-lbl">Very Bad</span></button>
                <button type="button" class="emoji-btn" data-value="2">😕<span class="emoji-lbl">Bad</span></button>
                <button type="button" class="emoji-btn" data-value="3">😐<span class="emoji-lbl">Okay</span></button>
                <button type="button" class="emoji-btn" data-value="4">🙂<span class="emoji-lbl">Good</span></button>
                <button type="button" class="emoji-btn" data-value="5">😄<span class="emoji-lbl">Great</span></button>
              </div>
              <input type="hidden" name="q1_mood">
            </div>

            <div class="survey-q">
              <label>2. What is your stress level today? <span class="text-danger">*</span></label>
              <div class="emoji-rating">
                <button type="button" class="emoji-btn" data-value="1">😌<span class="emoji-lbl">No Stress</span></button>
                <button type="button" class="emoji-btn" data-value="2">🙂<span class="emoji-lbl">Low</span></button>
                <button type="button" class="emoji-btn" data-value="3">😐<span class="emoji-lbl">Moderate</span></button>
                <button type="button" class="emoji-btn" data-value="4">😥<span class="emoji-lbl">High</span></button>
                <button type="button" class="emoji-btn" data-value="5">😰<span class="emoji-lbl">Very High</span></button>
              </div>
              <input type="hidden" name="q2_stress">
            </div>

            <div class="survey-q">
              <label>3. Do you need a consultation with your adviser? <span class="text-danger">*</span></label>
              <div class="yes-no-btns">
                <button type="button" class="yes-no-btn yes" data-value="1">✅ Yes, I need help</button>
                <button type="button" class="yes-no-btn no" data-value="0">🙅 No, I'm fine</button>
              </div>
              <input type="hidden" name="q3_needs_consultation">
            </div>

            <div class="survey-q">
              <label>4. Are there any problems at your company? <span class="text-danger">*</span></label>
              <div class="yes-no-btns">
                <button type="button" class="yes-no-btn yes" data-value="1">⚠️ Yes, there are issues</button>
                <button type="button" class="yes-no-btn no" data-value="0">👍 No problems</button>
              </div>
              <input type="hidden" name="q4_company_problem">
            </div>

            <div class="survey-q">
              <label>5. Rate your internship experience today <span class="text-danger">*</span></label>
              <div class="emoji-rating">
                <button type="button" class="emoji-btn" data-value="1">⭐<span class="emoji-lbl">1</span></button>
                <button type="button" class="emoji-btn" data-value="2">⭐⭐<span class="emoji-lbl">2</span></button>
                <button type="button" class="emoji-btn" data-value="3">⭐⭐⭐<span class="emoji-lbl">3</span></button>
                <button type="button" class="emoji-btn" data-value="4">⭐⭐⭐⭐<span class="emoji-lbl">4</span></button>
                <button type="button" class="emoji-btn" data-value="5">⭐⭐⭐⭐⭐<span class="emoji-lbl">5</span></button>
              </div>
              <input type="hidden" name="q5_experience_rating">
            </div>

            <div class="survey-q">
              <label>Additional Notes (Optional)</label>
              <textarea name="additional_notes" class="form-control" rows="3" placeholder="Anything you want to share with your adviser..."></textarea>
            </div>

            <button type="submit" class="btn btn-danger w-100 mt-1">
              <i class="bi bi-send me-2"></i>Submit Check-in
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- History -->
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-clock-history me-2 text-danger"></i>Check-in History</div>
      <div class="card-body p-0">
        <?php if (empty($history)): ?>
          <div class="empty-state"><i class="bi bi-clipboard-x"></i><p>No check-in history yet.</p></div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead><tr>
                <th class="ps-3">Date</th>
                <th>Mood</th>
                <th>Stress</th>
                <th>Rating</th>
                <th>Flags</th>
              </tr></thead>
              <tbody>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td class="ps-3" style="font-size:.82rem"><?= formatDate($h['survey_date'], 'M d, Y') ?></td>
                  <td style="font-size:.8rem"><?= $moodLabels[$h['q1_mood']] ?? '—' ?></td>
                  <td style="font-size:.8rem"><?= $stressLabels[$h['q2_stress']] ?? '—' ?></td>
                  <td>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="bi bi-star<?= $i <= $h['q5_experience_rating'] ? '-fill text-warning' : '' ?>" style="font-size:.75rem"></i>
                    <?php endfor; ?>
                  </td>
                  <td>
                    <?php if ($h['q3_needs_consultation']): ?>
                      <span class="badge bg-warning text-dark" style="font-size:.65rem">Consult</span>
                    <?php endif; ?>
                    <?php if ($h['q4_company_problem']): ?>
                      <span class="badge bg-danger" style="font-size:.65rem">Issue</span>
                    <?php endif; ?>
                    <?php if (!$h['q3_needs_consultation'] && !$h['q4_company_problem']): ?>
                      <span class="text-success" style="font-size:.75rem"><i class="bi bi-check-circle-fill"></i></span>
                    <?php endif; ?>
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
</div>

<?php include __DIR__ . '/../components/footer.php'; ?>
