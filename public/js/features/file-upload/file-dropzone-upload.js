import {dispatchCommand, formatFileSize, readFileAsBase64} from "../../utils";

const extensionOf = (name) => name.split('.').pop().toLowerCase();

export default class FileDropzoneUpload {
    constructor(rootNode) {
        this.rootNode = rootNode;
        this.dropzone = rootNode.querySelector('#dropzone');
        this.input = rootNode.querySelector('#file-input');
        this.list = rootNode.querySelector('#file-list');
        this.listHeader = rootNode.querySelector('#file-list-header');
        this.fileCount = rootNode.querySelector('#file-count');
        this.uploadBtn = rootNode.querySelector('#upload-btn');
        this.clearBtn = rootNode.querySelector('#clear-btn');
        this.form = rootNode.querySelector('#upload-form');
        this.supportedFileExtension = JSON.parse(this.form.getAttribute('data-supported-file-extensions'));
        this.translations = JSON.parse(this.form.getAttribute('data-translations'));

        this.files = [];
    }

    init() {
        if (!this.dropzone || !this.input || !this.form) return;

        this.list.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-remove-file]');
            if (!btn) return;
            this.files.splice(Number(btn.dataset.index), 1);
            this.render();
        });

        this.input.addEventListener('change', () => {
            this.addFiles(this.input.files);
            this.input.value = '';
        });

        ['dragenter', 'dragover'].forEach((evt) =>
            this.dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                this.dropzone.classList.add('is-dragging');
            }));
        ['dragleave', 'dragend', 'drop'].forEach((evt) =>
            this.dropzone.addEventListener(evt, (e) => {
                e.preventDefault();
                this.dropzone.classList.remove('is-dragging');
            }));
        this.dropzone.addEventListener('drop', (e) => {
            if (e.dataTransfer?.files) this.addFiles(e.dataTransfer.files);
        });

        this.clearBtn.addEventListener('click', () => {
            this.files = [];
            this.render();
        });

        this.form.addEventListener('submit', (e) => this.upload(e));
    }

    addFiles(fileList) {
        for (const file of fileList) {
            this.files.push({
                file,
                name: file.name,
                size: file.size,
                ok: this.supportedFileExtension.includes(extensionOf(file.name)),
                status: 'pending',
            });
        }
        this.render();
    }

    async upload(e) {
        e.preventDefault();

        const pending = this.files.filter((f) => f.ok && f.status !== 'uploaded' && f.status !== 'uploading');
        if (pending.length === 0) return;

        this.clearBtn.disabled = true;

        for (const item of pending) {
            item.status = 'uploading';
            item.error = null;
            this.render();

            try {
                await dispatchCommand('upload-activity-file', {
                    filename: item.name,
                    content: await readFileAsBase64(item.file),
                });
                item.status = 'uploaded';
            } catch (err) {
                item.status = 'error';
                item.error = err.message;
            }

            this.render();
        }

        this.uploadBtn.classList.remove('is-loading');
        this.clearBtn.disabled = false;
        this.render();
    }

    render() {
        this.list.innerHTML = '';
        const hasFiles = this.files.length > 0;
        const uploadableCount = this.files.filter((f) => f.ok && f.status !== 'uploaded' && f.status !== 'uploading').length;
        const uploadedCount = this.files.filter((f) => f.status === 'uploaded').length;
        const isUploading = this.files.some((f) => f.status === 'uploading');

        this.list.hidden = !hasFiles;
        this.listHeader.hidden = !hasFiles;
        this.clearBtn.classList.toggle('hidden', !hasFiles);
        this.uploadBtn.disabled = uploadableCount === 0 || isUploading;

        if (uploadableCount === 0 && uploadedCount > 0) {
            this.fileCount.textContent = this.translations.filesUploaded.replace('{count}', uploadedCount);
        } else {
            this.fileCount.textContent = this.translations.filesReadyToUpload.replace('{count}', uploadableCount);
        }

        this.files.forEach((file, index) => {
            const li = document.createElement('li');
            const classNames = ['file-drop-zone__item'];
            if (!file.ok || file.status === 'error') classNames.push('is-invalid');
            if (file.status === 'uploading') classNames.push('is-uploading');
            if (file.status === 'uploaded') classNames.push('is-uploaded');
            li.className = classNames.join(' ');
            li.innerHTML = `
                <span class="icon" aria-hidden="true">${this.iconFor(file)}</span>
                <span class="body">
                    <span class="name">${file.name}</span>
                    <span class="meta">${this.metaFor(file)}</span>
                </span>
                <button type="button" data-remove-file class="remove" data-index="${index}" aria-label="Remove">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>`;
            this.list.appendChild(li);
        });
    }

    iconFor(file) {
        switch (file.ok ? file.status : 'invalid') {
            case 'uploading':
                return `<svg class="animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" opacity="0.9"/></svg>`;
            case 'uploaded':
                return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6L9 17l-5-5"/></svg>`;
            case 'error':
            case 'invalid':
                return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>`;
            default:
                return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>`;
        }
    }

    metaFor(file) {
        if (!file.ok) return this.translations.unsupportedFileType;
        switch (file.status) {
            case 'uploading':
                return this.translations.uploading;
            case 'uploaded':
                return this.translations.uploaded;
            case 'error':
                return file.error || this.translations.uploadFailed;
            default:
                return formatFileSize(file.size);
        }
    }
}
