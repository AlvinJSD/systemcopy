<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$userId   = $_SESSION['user_id'];
$userType = 'student';
$pageTitle = 'Messages';
$activeNav = 'messages';

$db   = getDB();
$user = getCurrentUser();

// Get adviser
$adviser = null;
if ($user['adviser_id']) {
    $a = $db->prepare("SELECT * FROM advisers WHERE id = ?");
    $a->execute([$user['adviser_id']]);
    $adviser = $a->fetch();
}

// Handle send
$sendError = $sendSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (validateCSRF($_POST['csrf_token'] ?? '')) {
        $subject = sanitize($_POST['subject'] ?? '');
        $message = sanitize($_POST['message'] ?? '');
        if ($subject && $message && $adviser) {
            $stmt = $db->prepare("INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, subject, message) VALUES ('student', ?, 'adviser', ?, ?, ?)");
            $stmt->execute([$userId, $adviser['id'], $subject, $message]);
            createNotification('adviser', $adviser['id'], 'New Message from Student', "{$user['first_name']} {$user['last_name']}: $subject", 'message');
            $sendSuccess = 'Message sent successfully!';
        } else {
            $sendError = 'Subject and message are required.';
        }
    }
}

// Load messages
$msgs = [];
if ($adviser) {
    $stmt = $db->prepare("
        SELECT * FROM messages
        WHERE (sender_type='student' AND sender_id=? AND receiver_type='adviser' AND receiver_id=?)
           OR (sender_type='adviser' AND sender_id=? AND receiver_type='student' AND receiver_id=?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$userId, $adviser['id'], $adviser['id'], $userId]);
    $msgs = $stmt->fetchAll();

    // Mark adviser messages as read
    $db->prepare("UPDATE messages SET is_read=1 WHERE sender_type='adviser' AND receiver_type='student' AND receiver_id=?")->execute([$userId]);
}

include __DIR__ . '/../components/header.php';
?>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="card" style="height:calc(100vh - 160px);display:flex;flex-direction:column">
      <div class="card-header justify-content-between">
        <span><i class="bi bi-chat-dots me-2 text-danger"></i>Conversation with Adviser</span>
        <?php if ($adviser): ?>
          <span style="font-size:.8rem;color:#6c757d"><?= htmlspecialchars($adviser['first_name'].' '.$adviser['last_name']) ?></span>
        <?php endif; ?>
      </div>

      <?php if (!$adviser): ?>
        <div class="card-body">
          <div class="empty-state"><i class="bi bi-person-x"></i><p>No adviser assigned yet. You'll be able to message your adviser once one is assigned to you.</p></div>
        </div>
      <?php else: ?>
        <!-- Message Thread -->
        <div class="card-body p-3" style="flex:1;overflow-y:auto;background:#f8f9fa" id="msgThread">
          <?php if (empty($msgs)): ?>
            <div class="text-center text-muted py-4" style="font-size:.85rem">No messages yet. Start the conversation!</div>
          <?php else: foreach ($msgs as $m): ?>
            <?php $isSent = ($m['sender_type'] === 'student'); ?>
            <div class="d-flex <?= $isSent ? 'justify-content-end' : '' ?> mb-3">
              <?php if (!$isSent): ?>
                <div style="width:32px;height:32px;border-radius:50%;background:#2980b9;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;margin-right:.5rem">
                  <?= strtoupper(substr($adviser['first_name'],0,1)) ?>
                </div>
              <?php endif; ?>
              <div style="max-width:70%">
                <?php if (!$isSent): ?>
                  <div style="font-size:.72rem;font-weight:600;color:#6c757d;margin-bottom:.2rem"><?= htmlspecialchars($adviser['first_name']) ?></div>
                <?php endif; ?>
                <div class="msg-bubble <?= $isSent ? 'sent' : 'received' ?>">
                  <div style="font-weight:700;font-size:.78rem;margin-bottom:.3rem"><?= htmlspecialchars($m['subject']) ?></div>
                  <?= nl2br(htmlspecialchars($m['message'])) ?>
                  <div class="msg-time"><?= formatDateTime($m['created_at']) ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>

        <!-- Compose Area -->
        <div class="card-footer bg-white border-top p-3">
          <?php if ($sendSuccess): ?>
            <div class="alert alert-success alert-dismissible py-2 mb-2" style="font-size:.82rem"><i class="bi bi-check-circle me-1"></i><?= $sendSuccess ?><button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button></div>
          <?php endif; ?>
          <?php if ($sendError): ?>
            <div class="alert alert-danger py-2 mb-2" style="font-size:.82rem"><?= $sendError ?></div>
          <?php endif; ?>
          <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">
            <input type="hidden" name="send_message" value="1">
            <div class="mb-2">
              <input type="text" name="subject" class="form-control form-control-sm" placeholder="Subject" required>
            </div>
            <div class="d-flex gap-2">
              <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="Type your message..." required style="resize:none"></textarea>
              <button type="submit" class="btn btn-danger btn-sm align-self-end">
                <i class="bi bi-send-fill"></i>
              </button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-badge me-2 text-danger"></i>Adviser Info</div>
      <div class="card-body">
        <?php if ($adviser): ?>
          <div class="text-center mb-3">
            <div class="avatar-initials-lg mx-auto mb-2" style="width:60px;height:60px;font-size:1.3rem">
              <?= strtoupper(substr($adviser['first_name'],0,1).substr($adviser['last_name'],0,1)) ?>
            </div>
            <div style="font-weight:700"><?= htmlspecialchars($adviser['first_name'].' '.$adviser['last_name']) ?></div>
            <div style="font-size:.8rem;color:#6c757d"><?= htmlspecialchars($adviser['program']) ?> — Year <?= $adviser['year_level'] ?></div>
          </div>
          <div class="divider"></div>
          <div class="d-flex align-items-center gap-2 mb-2" style="font-size:.82rem">
            <i class="bi bi-envelope text-muted"></i><?= htmlspecialchars($adviser['email']) ?>
          </div>
          <?php if ($adviser['contact_number']): ?>
          <div class="d-flex align-items-center gap-2" style="font-size:.82rem">
            <i class="bi bi-telephone text-muted"></i><?= htmlspecialchars($adviser['contact_number']) ?>
          </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-state py-2"><i class="bi bi-person-x fs-3 d-block mb-2"></i><p style="font-size:.85rem">No adviser assigned</p></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mt-3">
      <div class="card-header"><i class="bi bi-lightbulb me-2 text-warning"></i>Tips</div>
      <div class="card-body" style="font-size:.82rem;color:#6c757d">
        <ul class="ps-3 mb-0" style="line-height:2">
          <li>Be respectful and professional in all messages.</li>
          <li>Include specific details when reporting concerns.</li>
          <li>Check your notifications for adviser replies.</li>
          <li>Use the daily survey to flag urgent concerns.</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<script>
// Auto-scroll message thread to bottom
const thread = document.getElementById('msgThread');
if (thread) thread.scrollTop = thread.scrollHeight;
</script>

<?php include __DIR__ . '/../components/footer.php'; ?>
