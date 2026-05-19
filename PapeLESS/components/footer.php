    </main><!-- /page-content -->
  </div><!-- /main-content -->
</div><!-- /dashboard-wrapper -->

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content border-0 shadow" style="border-radius:14px">
      <div class="modal-body p-4 text-center">
        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:2.5rem"></i>
        <h6 class="mt-3 mb-2 fw-bold">Confirm Action</h6>
        <p class="text-muted mb-3" id="confirmMessage" style="font-size:.9rem"></p>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary flex-fill btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-danger flex-fill btn-sm" id="confirmOkBtn">Confirm</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

<script>
// Logout handler
document.querySelectorAll('#logoutBtn, #logoutBtnTop').forEach(btn => {
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    confirmAction('Are you sure you want to logout?', async () => {
      const res = await ajaxPost('/PapeLESS/auth/auth_handler.php', { action: 'logout' });
      if (res.success) window.location.href = res.redirect;
    });
  });
});
</script>
</body>
</html>
