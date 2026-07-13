/**
 * Document depot upload modal with progress bar (XHR).
 */
(function () {
    function formatBytes(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function initFilesApp(app) {
        const modalEl = document.getElementById('filesUploadModal');
        const modal = modalEl && window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(modalEl) : null;
        const form = app.querySelector('[data-files-upload-form]');
        const dropzone = app.querySelector('[data-files-dropzone]');
        const input = app.querySelector('.files-dropzone-input');
        const progressWrap = app.querySelector('[data-files-upload-progress]');
        const progressBar = app.querySelector('[data-files-upload-bar]');
        const progressLabel = app.querySelector('[data-files-upload-label]');
        const progressPercent = app.querySelector('[data-files-upload-percent]');
        const submitBtn = form?.querySelector('[type="submit"]');
        const folderFieldWrap = app.querySelector('[data-files-folder-field]');
        const folderInput = app.querySelector('[data-files-folder-input]');
        const folderReadonly = app.querySelector('[data-files-folder-readonly]');
        const folderLabel = app.querySelector('[data-files-folder-label]');
        const selectedList = app.querySelector('[data-files-selected-list]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const labels = window.__filesUploadLabels || {};
        const defaultFolderLabel = app.dataset.defaultFolderLabel || 'Genel';

        function setProgress(percent, label) {
            if (!progressWrap || !progressBar) return;
            progressWrap.hidden = false;
            const pct = Math.min(100, Math.max(0, Math.round(percent)));
            progressBar.style.width = pct + '%';
            progressBar.setAttribute('aria-valuenow', String(pct));
            if (progressPercent) progressPercent.textContent = pct + '%';
            if (progressLabel && label) progressLabel.textContent = label;
        }

        function resetProgress() {
            if (!progressWrap || !progressBar) return;
            progressWrap.hidden = true;
            progressBar.style.width = '0%';
            progressBar.classList.remove('bg-danger', 'bg-success');
            if (progressPercent) progressPercent.textContent = '0%';
        }

        function setUploading(active) {
            if (submitBtn) submitBtn.disabled = active;
            app.classList.toggle('is-uploading', active);
        }

        function updateSelectedList() {
            if (!selectedList || !input?.files) return;
            if (!input.files.length) {
                selectedList.classList.add('d-none');
                selectedList.textContent = '';
                return;
            }
            const names = Array.from(input.files).map(f => f.name).slice(0, 5);
            const extra = input.files.length > 5 ? ` (+${input.files.length - 5})` : '';
            selectedList.textContent = names.join(', ') + extra;
            selectedList.classList.remove('d-none');
        }

        function applyFolderContext(folder, pickMode) {
            const value = folder ?? '';
            if (!folderInput) return;

            folderInput.value = value;

            if (pickMode) {
                folderFieldWrap?.classList.remove('d-none');
                folderReadonly?.classList.add('d-none');
                folderInput.required = false;
                return;
            }

            folderFieldWrap?.classList.add('d-none');
            folderReadonly?.classList.remove('d-none');
            folderInput.required = false;
            if (folderLabel) {
                folderLabel.textContent = value !== '' ? value : defaultFolderLabel;
            }
        }

        document.querySelectorAll('[data-files-upload-open]').forEach(btn => {
            btn.addEventListener('click', () => {
                resetProgress();
                if (input) input.value = '';
                updateSelectedList();
                applyFolderContext(btn.dataset.folder ?? '', btn.dataset.filesUploadPick === '1');
            });
        });

        if (dropzone && input) {
            ['dragenter', 'dragover'].forEach(evt => dropzone.addEventListener(evt, e => {
                e.preventDefault();
                dropzone.classList.add('is-dragover');
            }));
            ['dragleave', 'drop'].forEach(evt => dropzone.addEventListener(evt, e => {
                e.preventDefault();
                dropzone.classList.remove('is-dragover');
            }));
            dropzone.addEventListener('drop', e => {
                if (e.dataTransfer?.files?.length) {
                    input.files = e.dataTransfer.files;
                    updateSelectedList();
                }
            });
            dropzone.addEventListener('click', (e) => {
                if (e.target === input) return;
                input.click();
            });
            input.addEventListener('change', updateSelectedList);
        }

        modalEl?.addEventListener('hidden.bs.modal', () => {
            resetProgress();
            setUploading(false);
            if (input) input.value = '';
            updateSelectedList();
        });

        form?.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!input?.files?.length) {
                alert(labels.noFiles || 'Select at least one file.');
                return;
            }

            if (folderInput && folderFieldWrap && !folderFieldWrap.classList.contains('d-none') && folderInput.required && !folderInput.value.trim()) {
                alert(labels.folderRequired || 'Folder name is required.');
                return;
            }

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            setUploading(true);
            setProgress(0, labels.uploading || 'Uploading…');

            xhr.upload.addEventListener('progress', (ev) => {
                if (!ev.lengthComputable) return;
                const pct = (ev.loaded / ev.total) * 100;
                const loadedLabel = formatBytes(ev.loaded) + ' / ' + formatBytes(ev.total);
                setProgress(pct, (labels.uploading || 'Uploading') + ' — ' + loadedLabel);
            });

            xhr.addEventListener('load', () => {
                setUploading(false);

                if (xhr.status >= 200 && xhr.status < 300) {
                    let data;
                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch (err) {
                        window.location.reload();
                        return;
                    }

                    setProgress(100, labels.done || 'Complete');
                    progressBar?.classList.add('bg-success');
                    modal?.hide();

                    setTimeout(() => {
                        window.location.href = data.redirect || window.location.href;
                    }, 350);
                    return;
                }

                progressBar?.classList.add('bg-danger');
                let message = labels.failed || 'Upload failed.';

                try {
                    const data = JSON.parse(xhr.responseText);
                    if (data.message) message = data.message;
                    if (data.errors) {
                        const first = Object.values(data.errors)[0];
                        if (Array.isArray(first) && first[0]) message = first[0];
                    }
                } catch (err) {
                    // keep default
                }

                if (progressLabel) progressLabel.textContent = message;
            });

            xhr.addEventListener('error', () => {
                setUploading(false);
                progressBar?.classList.add('bg-danger');
                if (progressLabel) progressLabel.textContent = labels.networkError || 'Network error.';
            });

            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.send(formData);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-files-app]').forEach(initFilesApp);
    });
})();
