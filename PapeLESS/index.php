<?php
// ============================================================
// PapeLESS - Landing Page (Login + Sign Up)
// ============================================================
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $map = ['student'=>'student','adviser'=>'adviser','coordinator'=>'coordinator'];
    $role = $map[$_SESSION['user_type']] ?? null;
    if ($role) redirect(BASE_URL . "/$role/dashboard.php");
}

$csrf = generateCSRF();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PapeLESS – OJT Document Tracking System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/main.css" rel="stylesheet">
</head>
<body>
<script>window.CSRF_TOKEN = '<?= $csrf ?>';</script>

<div class="landing-page">
  <!-- Navbar -->
  <nav class="landing-navbar d-flex align-items-center justify-content-between">
    <div class="brand">Pape<span>LESS</span></div>
    <div class="d-flex align-items-center gap-3">
      <span style="color:rgba(255,255,255,.6);font-size:.8rem">OJT Document Tracking System</span>
      <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#signupModal">
        <i class="bi bi-person-plus me-1"></i>Register
      </button>
    </div>
  </nav>

  <!-- Hero -->
  <section class="hero-section">
    <div class="container">
      <div class="hero-badge">Polytechnic University of the Philippines – Sto. Tomas Campus</div>
      <h1>Streamline Your<br><span style="color:#e74c3c">OJT Journey</span></h1>
      <p>PapeLESS is a paperless internship management system that connects students, advisers, and coordinators for seamless OJT document tracking and monitoring.</p>
      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:.75rem 1.5rem;color:#fff;font-size:.85rem;display:flex;align-items:center;gap:.5rem">
          <i class="bi bi-shield-check text-success"></i> Secure & Reliable
        </div>
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:.75rem 1.5rem;color:#fff;font-size:.85rem;display:flex;align-items:center;gap:.5rem">
          <i class="bi bi-phone text-info"></i> Mobile Friendly
        </div>
        <div style="background:rgba(255,255,255,.1);border-radius:10px;padding:.75rem 1.5rem;color:#fff;font-size:.85rem;display:flex;align-items:center;gap:.5rem">
          <i class="bi bi-cloud-arrow-up text-warning"></i> Easy File Submission
        </div>
      </div>
    </div>
  </section>

  <!-- Auth Section -->
  <section class="auth-section">
    <div class="container">
      <div class="row justify-content-center g-4">

        <!-- LOGIN CARD -->
        <div class="col-lg-5 col-md-7">
          <div class="auth-card">
            <div class="auth-card-header">
              <h4><i class="bi bi-box-arrow-in-right me-2"></i>Sign In to PapeLESS</h4>
              <p>Enter your credentials to access the system</p>
            </div>
            <div class="auth-card-body">
              <!-- Role tabs -->
              <div class="role-tabs">
                <button class="role-tab active" data-role="student">
                  <i class="bi bi-mortarboard"></i> Student
                </button>
                <button class="role-tab" data-role="adviser">
                  <i class="bi bi-person-badge"></i> Adviser
                </button>
                <button class="role-tab" data-role="coordinator">
                  <i class="bi bi-person-gear"></i> Coordinator
                </button>
              </div>
              <input type="hidden" id="loginRole" value="student">

              <form id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                <div class="mb-3">
                  <label class="form-label">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:8px 0 0 8px">
                      <i class="bi bi-envelope text-muted"></i>
                    </span>
                    <input type="email" id="loginEmail" name="email" class="form-control border-start-0 ps-0" placeholder="your@email.com" required style="border-radius:0 8px 8px 0">
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:8px 0 0 8px">
                      <i class="bi bi-lock text-muted"></i>
                    </span>
                    <input type="password" id="loginPassword" name="password" class="form-control border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                    <button class="input-group-text bg-light border-start-0 password-toggle" type="button" data-target="loginPassword" style="border-radius:0 8px 8px 0;cursor:pointer">
                      <i class="bi bi-eye"></i>
                    </button>
                  </div>
                </div>

                <button type="submit" class="btn-primary-custom mt-1">
                  <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
              </form>

              <div class="text-center mt-3">
                <span style="font-size:.85rem;color:#6c757d">Don't have an account? </span>
                <a href="#" data-bs-toggle="modal" data-bs-target="#signupModal" style="font-size:.85rem;color:var(--primary);font-weight:600">Register as Student</a>
              </div>

              <!-- Sample credentials hint -->
              <div class="mt-3 p-3 rounded" style="background:#f8f9fa;font-size:.75rem;color:#6c757d">
                <strong>Demo Accounts:</strong><br>
                Student: jose.mercado@student.edu.ph | Student@123<br>
                Adviser: adviser.bsit3@papeless.edu.ph | Adviser@123<br>
                Coordinator: coordinator@papeless.edu.ph | Admin@123
              </div>
            </div>
          </div>
        </div>

        <!-- INFO CARD -->
        <div class="col-lg-4 col-md-5">
          <div class="h-100 d-flex flex-column gap-3">
            <div style="background:rgba(255,255,255,.1);border-radius:14px;padding:1.5rem;color:#fff">
              <i class="bi bi-mortarboard-fill" style="font-size:2rem;color:#e74c3c"></i>
              <h6 class="mt-2 fw-bold">For Students</h6>
              <p style="font-size:.82rem;opacity:.85;margin:0">Submit OJT documents, track approval status, communicate with your adviser, and monitor your internship progress.</p>
            </div>
            <div style="background:rgba(255,255,255,.1);border-radius:14px;padding:1.5rem;color:#fff">
              <i class="bi bi-person-badge-fill" style="font-size:2rem;color:#f39c12"></i>
              <h6 class="mt-2 fw-bold">For Advisers</h6>
              <p style="font-size:.82rem;opacity:.85;margin:0">Review and approve student documents, monitor well-being surveys, and manage your assigned students efficiently.</p>
            </div>
            <div style="background:rgba(255,255,255,.1);border-radius:14px;padding:1.5rem;color:#fff">
              <i class="bi bi-person-gear-fill" style="font-size:2rem;color:#27ae60"></i>
              <h6 class="mt-2 fw-bold">For Coordinators</h6>
              <p style="font-size:.82rem;opacity:.85;margin:0">Full system control — manage advisers, monitor all students, post announcements, and generate comprehensive reports.</p>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<!-- ============================================================ -->
<!-- SIGN UP MODAL                                                  -->
<!-- ============================================================ -->
<div class="modal fade" id="signupModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px">
      <div class="modal-header" style="background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border-radius:16px 16px 0 0">
        <div>
          <h5 class="modal-title fw-bold mb-0"><i class="bi bi-person-plus me-2"></i>Student Registration</h5>
          <small style="opacity:.85">Fill out all required fields to register</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">

        <!-- Steps -->
        <div class="signup-steps mb-4">
          <div class="step-item">
            <div class="step-circle active" id="circle1">1</div>
            <div class="step-label active" id="label1">Personal Info</div>
          </div>
          <div class="step-item">
            <div class="step-circle" id="circle2">2</div>
            <div class="step-label" id="label2">Academic Info</div>
          </div>
          <div class="step-item">
            <div class="step-circle" id="circle3">3</div>
            <div class="step-label" id="label3">Internship Info</div>
          </div>
        </div>

        <form id="signupForm" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

          <!-- STEP 1: Personal Information -->
          <div class="step-page active" id="stepPage1">
            <h6 class="text-muted fw-bold mb-3 text-uppercase" style="font-size:.75rem;letter-spacing:1px"><i class="bi bi-person me-1"></i>Personal Information</h6>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Student Number <span class="text-danger">*</span></label>
                <input type="text" name="student_number" class="form-control" placeholder="e.g. 2021-00001" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" class="form-control" placeholder="Juan" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Middle Name</label>
                <input type="text" name="middle_name" class="form-control" placeholder="Optional">
              </div>
              <div class="col-md-6">
                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" class="form-control" placeholder="Dela Cruz" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Birthdate <span class="text-danger">*</span></label>
                <input type="date" name="birthdate" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" placeholder="juan@example.com" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Contact Number</label>
                <input type="tel" name="contact_number" class="form-control" placeholder="09xx-xxx-xxxx">
              </div>
              <div class="col-md-6">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" id="signupPassword" name="password" class="form-control" placeholder="Min. 8 characters" required>
                  <button class="btn btn-outline-secondary password-toggle" type="button" data-target="signupPassword"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <div class="input-group">
                  <input type="password" id="signupConfirmPassword" name="confirm_password" class="form-control" placeholder="Re-enter password" required>
                  <button class="btn btn-outline-secondary password-toggle" type="button" data-target="signupConfirmPassword"><i class="bi bi-eye"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- STEP 2: Academic Information -->
          <div class="step-page" id="stepPage2">
            <h6 class="text-muted fw-bold mb-3 text-uppercase" style="font-size:.75rem;letter-spacing:1px"><i class="bi bi-mortarboard me-1"></i>Academic Information</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Program / Course <span class="text-danger">*</span></label>
                <select name="program" class="form-select" required>
                  <option value="">Select Program</option>
                  <option value="BSIT">Bachelor of Science in Information Technology (BSIT)</option>
                  <option value="BSCS">Bachelor of Science in Computer Science (BSCS)</option>
                  <option value="BSIS">Bachelor of Science in Information Systems (BSIS)</option>
                  <option value="BSBA">Bachelor of Science in Business Administration (BSBA)</option>
                  <option value="BSA">Bachelor of Science in Accountancy (BSA)</option>
                  <option value="BSHRM">Bachelor of Science in Hotel and Restaurant Management (BSHRM)</option>
                  <option value="BSE">Bachelor of Secondary Education (BSE)</option>
                  <option value="BSED">Bachelor of Secondary Education (BSED)</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Year Level <span class="text-danger">*</span></label>
                <select name="year_level" id="signupYearLevel" class="form-select" required>
                  <option value="">Year</option>
                  <option value="1">1st Year</option>
                  <option value="2">2nd Year</option>
                  <option value="3">3rd Year</option>
                  <option value="4">4th Year</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Section <span class="text-danger">*</span></label>
                <select name="section" class="form-select" required>
                  <option value="">Section</option>
                  <option value="1">Section 1</option>
                  <option value="2">Section 2</option>
                </select>
              </div>
            </div>
            <div class="alert alert-info mt-3" style="font-size:.83rem;border-radius:10px">
              <i class="bi bi-info-circle me-1"></i>
              Your account will be <strong>pending approval</strong> until your assigned adviser reviews and approves your registration.
            </div>
          </div>

          <!-- STEP 3: Internship Information -->
          <div class="step-page" id="stepPage3">
            <h6 class="text-muted fw-bold mb-3 text-uppercase" style="font-size:.75rem;letter-spacing:1px"><i class="bi bi-building me-1"></i>Internship Information</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Company / Organization Name <span class="text-danger">*</span></label>
                <input type="text" name="company_name" class="form-control" placeholder="e.g. TechCorp Philippines" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Department / Role <span class="text-danger">*</span></label>
                <input type="text" name="department_role" class="form-control" placeholder="e.g. Web Developer Intern" required>
              </div>
              <div class="col-12">
                <label class="form-label">Company Address <span class="text-danger">*</span></label>
                <textarea name="company_address" class="form-control" rows="2" placeholder="Full company address" required></textarea>
              </div>
              <div class="col-md-4">
                <label class="form-label">Internship Start Date <span class="text-danger">*</span></label>
                <input type="date" name="internship_start" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Time In <span class="text-danger">*</span></label>
                <input type="time" name="time_in" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Time Out <span class="text-danger">*</span></label>
                <input type="time" name="time_out" class="form-control" required>
              </div>
            </div>
            <div class="alert alert-success mt-3" style="font-size:.83rem;border-radius:10px">
              <i class="bi bi-check-circle me-1"></i>
              Almost done! Click <strong>Submit Registration</strong> to send your application.
            </div>
          </div>

        </form>
      </div><!-- /modal-body -->

      <div class="modal-footer border-0 pt-0 pb-3 px-4">
        <button type="button" class="btn btn-outline-secondary" id="prevStepBtn" onclick="goToStep(currentStep-1)" style="display:none">
          <i class="bi bi-arrow-left me-1"></i>Back
        </button>
        <div class="ms-auto d-flex gap-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" id="nextStepBtn" onclick="goToStep(currentStep+1)">
            Next <i class="bi bi-arrow-right ms-1"></i>
          </button>
          <button type="submit" form="signupForm" class="btn btn-success" id="submitSignupBtn" style="display:none">
            <i class="bi bi-send me-2"></i>Submit Registration
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap + JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  function updateStepButtons() {
    document.getElementById('prevStepBtn').style.display =
      currentStep > 1 ? 'inline-flex' : 'none';

    document.getElementById('nextStepBtn').style.display =
      currentStep < 3 ? 'inline-flex' : 'none';

    document.getElementById('submitSignupBtn').style.display =
      currentStep === 3 ? 'inline-flex' : 'none';
  }

  updateStepButtons();

  const originalGoToStep = window.goToStep;

  window.goToStep = function(step) {
    if (typeof originalGoToStep === 'function') {
      originalGoToStep(step);
      updateStepButtons();
    }
  };

});
</script>
</body>
</html>
