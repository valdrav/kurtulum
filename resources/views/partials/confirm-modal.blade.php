<div class="modal fade" id="efConfirmModal" tabindex="-1" aria-labelledby="efConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="efConfirmModalLabel">{{ __('app.confirm_title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('app.close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="efConfirmModalMessage">{{ __('app.confirm_delete') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">{{ __('app.cancel') }}</button>
                <button type="button" class="btn btn-danger" id="efConfirmModalSubmit">{{ __('app.delete') }}</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('efConfirmModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(modalEl);
    const titleEl = document.getElementById('efConfirmModalLabel');
    const messageEl = document.getElementById('efConfirmModalMessage');
    const submitBtn = document.getElementById('efConfirmModalSubmit');
    let pendingForm = null;

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            pendingForm = form;
            titleEl.textContent = form.dataset.confirmTitle || @json(__('app.confirm_title'));
            messageEl.textContent = form.dataset.confirm || @json(__('app.confirm_delete'));
            submitBtn.textContent = form.dataset.confirmButton || @json(__('app.delete'));
            submitBtn.className = 'btn ' + (form.dataset.confirmDanger === 'false' ? 'btn-warning' : 'btn-danger');
            modal.show();
        });
    });

    submitBtn.addEventListener('click', function () {
        if (pendingForm) {
            pendingForm.dataset.confirmed = '1';
            pendingForm.submit();
            pendingForm = null;
        }
        modal.hide();
    });
});
</script>
