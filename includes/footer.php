<style>
  /* Footer styled like date display */
  .site-footer {
    display: flex;
    justify-content: center;
    padding: 16px 0;
  }

  .site-footer .footer-display {
    color: white !important;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    background: rgba(0,0,0,0.15);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
  }
</style>

<div class="container">
  <footer class="site-footer">
    <div class="footer-display">
      <i class="fas fa-copyright me-2"></i>
      <?php echo date('Y'); ?> VoteSure — Built by Group 10, 12-OPTIMISM.
    </div>
  </footer>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Confirmation Modal -->
<div class="modal fade" id="actionConfirmModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <div class="modal-header bg-light border-0 rounded-top-4">
        <h5 class="modal-title fw-bold text-orange">
          <i class="fas fa-exclamation-triangle me-2"></i>Confirm Action
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">
        <p id="confirmMessage" class="mb-0 text-muted"></p>
      </div>

      <div class="modal-footer bg-light border-0 rounded-bottom-4">
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
          Cancel
        </button>
        <form method="post" id="confirmForm">
          <input type="hidden" name="election_id" id="confirmElectionId">
          <input type="hidden" name="action" id="confirmAction">
          <button type="submit" class="btn btn-orange px-4 fw-bold">
            Yes, Continue
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

</body>
</html>
