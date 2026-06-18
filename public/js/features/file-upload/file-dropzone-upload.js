import {formatFileSize} from "../../utils";

const extensionOf = (name) => name.split('.').pop().toLowerCase();

export default class FileDropzoneUpload {
    constructor(rootNode) {
        this.rootNode = rootNode;
        this.dropzone = rootNode.querySelector('#dropzone');
        this.input = rootNode.querySelector('#file-input');
        this.list = rootNode.querySelector('#file-list');
        this.listHeader = rootNode.querySelector('#file-list-header');
        this.fileCount = rootNode.querySelector('#file-count');
        this.fileInvalid = rootNode.querySelector('#file-invalid');
        this.uploadBtn = rootNode.querySelector('#upload-btn');
        this.clearBtn = rootNode.querySelector('#clear-btn');
        this.form = rootNode.querySelector('#upload-form');
        this.supportedFileExtension = JSON.parse(this.form.getAttribute('data-supported-file-extensions'))

        this.files = [];
    }

    init() {
        if (!this.dropzone || !this.input) return;

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

        this.form?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.clearBtn.disabled = true;
            this.clearBtn.classList.add('hidden');
        });
    }

    addFiles(fileList) {
        for (const file of fileList) {
            this.files.push({
                name: file.name,
                size: file.size,
                ok: this.supportedFileExtension.includes(extensionOf(file.name)),
            });
        }
        this.render();
    }

    render() {
        this.list.innerHTML = '';
        const hasFiles = this.files.length > 0;
        const validCount = this.files.filter((f) => f.ok).length;
        const invalidCount = this.files.length - validCount;

        this.list.hidden = !hasFiles;
        this.listHeader.hidden = !hasFiles;
        this.clearBtn.classList.toggle('hidden', !hasFiles);
        this.uploadBtn.disabled = validCount === 0;

        this.fileCount.textContent = `${validCount} file${validCount === 1 ? '' : 's'} ready to upload`;
        this.fileInvalid.hidden = invalidCount === 0;
        this.fileInvalid.textContent = `${invalidCount} unsupported`;

        this.files.forEach((file, index) => {
            const li = document.createElement('li');
            li.className = file.ok ? 'file-drop-zone__item' : 'file-drop-zone__item is-invalid';
            li.innerHTML = `
                <span class="icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <path d="M14 2v6h6"/>
                    </svg>
                </span>
                <span class="body">
                    <span class="name">${file.name}</span>
                    <span class="meta">${file.ok ? formatFileSize(file.size) : 'Unsupported file type'}</span>
                </span>
                <button type="button" data-remove-file class="remove" data-index="${index}" aria-label="Remove">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>`;
            this.list.appendChild(li);
        });
    }
}
