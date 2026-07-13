/**
 * Document depot upload with progress bar (XHR). Inline panel — no modal dependency.
 */
(function () {
    function formatBytes(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function initUploadPanel(panel) {
        if (panel.dataset.filesUploadInit === '1') {
            return;
        }
        panel.dataset.filesUploadInit = '1';

        const form = panel.querySelector('[data-files-upload-form]');
        const dropzone = panel.querySelector('[data-files-dropzone]');
        const input = panel.querySelector('.files-dropzone-input');
        const progressWrap = panel.querySelector('[data-files-upload-progress]');
        const progressBar = panel.querySelector('[data-files-upload-bar]');
        const progressLabel = panel.querySelector('[data-files-upload-label]');
        const progressPercent = panel.querySelector('[data-files-upload-percent]');
        const submitBtn = form?.querySelector('[type="submit"]');
        const folderInput = panel.querySelector('[data-files-folder-input]');
        const folderHint = panel.querySelector('[data-files-folder-hint]');
        const selectedList = panel.querySelector('[data-files-selected-list]');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const labels = window.__filesUploadLabels || {};
        const defaultFolderLabel = panel.dataset.defaultFolderLabel || 'Genel';
        const pickModeDefault = panel.dataset.pickModeDefault === '1';

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
            panel.classList.toggle('is-uploading', active);
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
            if (!folderInput) return;

            const value = folder ?? '';
            folderInput.value = value;
            folderInput.readOnly = !pickMode && value !== '';

            if (folderHint) {
                if (pickMode) {
                    folderHint.textContent = labels.folderHintPick || folderHint.dataset.pickHint || '';
                } else if (value !== '') {
                    folderHint.textContent = (labels.folderHintFixed || 'Hedef klasör: ') + value;
                } else {
                    folderHint.textContent = (labels.folderHintDefault || 'Hedef klasör: ') + defaultFolderLabel;
                }
            }

            folderInput.focus({ preventScroll: true });
        }

        function scrollToPanel() {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            panel.classList.add('is-highlight');
            window.setTimeout(() => panel.classList.remove('is-highlight'), 1200);
        }

        document.querySelectorAll('[data-files-upload-target]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                resetProgress();
                if (input) input.value = '';
                updateSelectedList();
                applyFolderContext(
                    btn.dataset.folder ?? '',
                    btn.dataset.filesUploadPick === '1'
                );
                scrollToPanel();
            });
        });

        if (pickModeDefault && folderInput && !folderInput.value.trim()) {
            applyFolderContext('', true);
        }

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

        form?.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!input?.files?.length) {
                alert(labels.noFiles || 'En az bir dosya seçin.');
                return;
            }

            const formData = new FormData(form);
            if (!formData.get('folder') && folderInput) {
                formData.set('folder', folderInput.value.trim());
            }

            const xhr = new XMLHttpRequest();

            setUploading(true);
            setProgress(0, labels.uploading || 'Yükleniyor…');

            xhr.upload.addEventListener('progress', (ev) => {
                if (!ev.lengthComputable) return;
                const pct = (ev.loaded / ev.total) * 100;
                const loadedLabel = formatBytes(ev.loaded) + ' / ' + formatBytes(ev.total);
                setProgress(pct, (labels.uploading || 'Yükleniyor') + ' — ' + loadedLabel);
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

                    setProgress(100, labels.done || 'Tamamlandı');
                    progressBar?.classList.add('bg-success');

                    window.setTimeout(() => {
                        window.location.href = data.redirect || window.location.href;
                    }, 400);
                    return;
                }

                progressBar?.classList.add('bg-danger');
                let message = labels.failed || 'Yükleme başarısız.';

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
                if (progressLabel) progressLabel.textContent = labels.networkError || 'Ağ hatası.';
            });

            xhr.open('POST', form.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
            xhr.send(formData);
        });
    }

    function boot() {
        document.querySelectorAll('[data-files-upload-panel]').forEach(initUploadPanel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
