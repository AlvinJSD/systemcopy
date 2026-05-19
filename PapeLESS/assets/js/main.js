/* ============================================================
   PapeLESS - Main JavaScript
   ============================================================ */

'use strict';

// ── Sidebar Toggle ────────────────────────────────────────────
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const sidebarToggle  = document.getElementById('sidebarToggle');

if (sidebarToggle) {
  sidebarToggle.addEventListener('click', toggleSidebar);
}
if (sidebarOverlay) {
  sidebarOverlay.addEventListener('click', closeSidebar);
}

function toggleSidebar() {
  sidebar?.classList.toggle('open');
  sidebarOverlay?.classList.toggle('show');
}

function closeSidebar() {
  sidebar?.classList.remove('open');
  sidebarOverlay?.classList.remove('show');
}

// ── Notification Dropdown ────────────────────────────────────
const notifBtn      = document.getElementById('notifBtn');
const notifDropdown = document.getElementById('notifDropdown');

if (notifBtn) {
  notifBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    notifDropdown?.classList.toggle('show');
    if (notifDropdown?.classList.contains('show')) {
      markNotificationsRead();
    }
  });
}

document.addEventListener('click', () => {
  notifDropdown?.classList.remove('show');
});

function markNotificationsRead() {
  fetch('/PapeLESS/api/notifications.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'action=mark_read&csrf_token=' + encodeURIComponent(window.CSRF_TOKEN || '')
  });
  document.querySelector('.notif-btn .dot')?.remove();
}

// ── Toast Notifications ──────────────────────────────────────
function showToast(message, type = 'success', duration = 4000) {
  let container = document.querySelector('.toast-container-custom');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container-custom';
    document.body.appendChild(container);
  }

  const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill', warning: 'bi-exclamation-triangle-fill' };
  const colors = { success: '#27ae60', error: '#e74c3c', info: '#2980b9', warning: '#f39c12' };

  const toast = document.createElement('div');
  toast.className = `toast-custom ${type}`;
  toast.innerHTML = `
    <i class="bi ${icons[type] || icons.info}" style="color:${colors[type]};font-size:1.2rem;flex-shrink:0;margin-top:2px"></i>
    <div style="flex:1">
      <div style="font-size:.85rem;font-weight:600;color:#2d3436">${message}</div>
    </div>
    <button onclick="this.parentElement.remove()" style="background:none;border:none;font-size:1rem;color:#adb5bd;cursor:pointer;padding:0;line-height:1">&times;</button>
  `;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), duration);
}

// ── AJAX Helper ──────────────────────────────────────────────
async function ajaxPost(url, data) {
  const formData = new FormData();
  Object.entries(data).forEach(([k, v]) => {
    if (v instanceof File) formData.append(k, v);
    else formData.append(k, v ?? '');
  });
  formData.append('csrf_token', window.CSRF_TOKEN || '');

  const res = await fetch(url, {
    method: 'POST',
    body: formData,
    credentials: 'same-origin'
});
  const json = await res.json();
  return json;
}

// ── Login Form ───────────────────────────────────────────────
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  // Role tabs
  document.querySelectorAll('.role-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById('loginRole').value = tab.dataset.role;
    });
  });

  loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = loginForm.querySelector('[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Signing in...';

    const data = {
      action:   'login',
      email:    document.getElementById('loginEmail').value,
      password: document.getElementById('loginPassword').value,
      role:     document.getElementById('loginRole').value,
    };

    const res = await ajaxPost('/PapeLESS/auth/auth_handler.php', data);
    if (res.success) {
      showToast('Login successful! Redirecting...', 'success');
      setTimeout(() => window.location.href = res.redirect, 800);
    } else {
      showToast(res.error, 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Sign In';
    }
  });
  
}

// ── Sign Up Form (multi-step) ────────────────────────────────
let currentStep = 1;
const totalSteps = 3;

function goToStep(step) {
  if (step < 1 || step > totalSteps) return;

  // Validate current step before advancing
  if (step > currentStep && !validateStep(currentStep)) return;

  document.querySelectorAll('.step-page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.step-circle').forEach((c, i) => {
    c.classList.remove('active', 'done');
    if (i + 1 < step) c.classList.add('done'), c.innerHTML = '<i class="bi bi-check"></i>';
    else if (i + 1 === step) c.classList.add('active');
  });
  document.querySelectorAll('.step-label').forEach((l, i) => {
    l.classList.toggle('active', i + 1 === step);
  });

  document.getElementById(`stepPage${step}`)?.classList.add('active');
  currentStep = step;
}

function validateStep(step) {
  const page = document.getElementById(`stepPage${step}`);
  if (!page) return true;
  const inputs = page.querySelectorAll('[required]');
  let valid = true;
  inputs.forEach(input => {
    input.classList.remove('is-invalid');
    if (!input.value.trim()) {
      input.classList.add('is-invalid');
      valid = false;
    }
  });

  if (step === 1) {
    const pwd = document.getElementById('signupPassword');
    const cpwd = document.getElementById('signupConfirmPassword');
    if (pwd && cpwd && pwd.value !== cpwd.value) {
      cpwd.classList.add('is-invalid');
      showToast('Passwords do not match.', 'error');
      return false;
    }
    if (pwd && pwd.value.length < 8) {
      pwd.classList.add('is-invalid');
      showToast('Password must be at least 8 characters.', 'error');
      return false;
    }
  }
  if (!valid) showToast('Please fill in all required fields.', 'error');
  return valid;
}

const signupForm = document.getElementById('signupForm');
if (signupForm) {
  signupForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!validateStep(currentStep)) return;

    const btn = document.getElementById('submitSignupBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

    const formData = new FormData(signupForm);
    formData.append('action', 'signup');
    formData.append('csrf_token', window.CSRF_TOKEN || '');

    const res = await fetch('/PapeLESS/auth/auth_handler.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });
    const json = await res.json();

    if (json.success) {
      showToast(json.message, 'success', 6000);
      signupForm.reset();
      goToStep(1);
      bootstrap.Modal.getOrCreateInstance(document.getElementById('signupModal'))?.hide();
    } else {
      showToast(json.error, 'error');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-2"></i>Submit Registration';
  });
}

// ── File Upload with Drag & Drop ─────────────────────────────
document.querySelectorAll('.upload-zone').forEach(zone => {
  const input = zone.querySelector('input[type=file]') || document.getElementById(zone.dataset.input);

  zone.addEventListener('click', () => input?.click());

  zone.addEventListener('dragover', (e) => {
    e.preventDefault();
    zone.classList.add('dragover');
  });

  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));

  zone.addEventListener('drop', (e) => {
    e.preventDefault();
    zone.classList.remove('dragover');
    if (input && e.dataTransfer.files.length) {
      input.files = e.dataTransfer.files;
      updateFileLabel(zone, e.dataTransfer.files[0].name);
    }
  });

  input?.addEventListener('change', () => {
    if (input.files.length) updateFileLabel(zone, input.files[0].name);
  });
});

function updateFileLabel(zone, name) {
  const p = zone.querySelector('p');
  if (p) {
    p.innerHTML = `<i class="bi bi-file-earmark-check text-success me-1"></i><strong>${name}</strong>`;
  }
}

// ── Survey Modal ──────────────────────────────────────────────
// Emoji ratings
document.querySelectorAll('.emoji-rating').forEach(group => {
  group.querySelectorAll('.emoji-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      group.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      const hidden = group.nextElementSibling;
      if (hidden?.type === 'hidden') hidden.value = btn.dataset.value;
    });
  });
});

// Yes/No buttons
document.querySelectorAll('.yes-no-btns').forEach(group => {
  group.querySelectorAll('.yes-no-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      group.querySelectorAll('.yes-no-btn').forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      const hidden = group.nextElementSibling;
      if (hidden?.type === 'hidden') hidden.value = btn.dataset.value;
    });
  });
});

// Survey submission
const surveyForm = document.getElementById('surveyForm');
if (surveyForm) {
  surveyForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = surveyForm.querySelector('[type=submit]');

    // Check all answers filled
    const hiddens = surveyForm.querySelectorAll('input[type=hidden]');
    let allFilled = true;
    hiddens.forEach(h => { if (!h.value) allFilled = false; });
    if (!allFilled) {
      showToast('Please answer all survey questions.', 'error');
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Submitting...';

    const data = Object.fromEntries(new FormData(surveyForm));
    data.action = 'submit_survey';

    const res = await ajaxPost('/PapeLESS/student/survey_handler.php', data);
    if (res.success) {
      document.getElementById('surveyModalOverlay')?.remove();
      showToast('Thank you for your daily check-in!', 'success');
    } else {
      showToast(res.error || 'Failed to submit survey.', 'error');
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send me-2"></i>Submit';
  });
}

// ── Confirm Dialog ────────────────────────────────────────────
function confirmAction(message, callback) {
  const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
  document.getElementById('confirmMessage').textContent = message;
  document.getElementById('confirmOkBtn').onclick = () => {
    modal.hide();
    callback();
  };
  modal.show();
}

// ── Data Tables Search ─────────────────────────────────────────
document.querySelectorAll('[data-search]').forEach(input => {
  const targetId = input.dataset.search;
  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    document.querySelectorAll(`#${targetId} tr`).forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
});

// ── Auto-dismiss alerts ───────────────────────────────────────
document.querySelectorAll('.alert-dismissible').forEach(alert => {
  setTimeout(() => {
    new bootstrap.Alert(alert)?.close();
  }, 5000);
});

// ── Password toggle ───────────────────────────────────────────
document.querySelectorAll('.password-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
  });
});

