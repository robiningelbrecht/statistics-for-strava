import {dispatchCommand} from "../../utils";
import {extensionOf, readFileAsBase64, Status, metaFor} from "./upload-utils";

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
        this.itemTemplate = rootNode.querySelector('#file-item-template');

        this.files = [];
    }

    init() {
        if (!this.dropzone || !this.input || !this.form) return;

        this.supportedFileExtension = JSON.parse(this.form.getAttribute('data-supported-file-extensions'));
        this.translations = JSON.parse(this.form.getAttribute('data-translations'));

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
                status: Status.Pending,
            });
        }
        this.render();
    }

    async upload(e) {
        e.preventDefault();

        const pending = this.files.filter((f) => f.ok && f.status !== Status.Uploaded && f.status !== Status.Uploading);
        if (pending.length === 0) return;

        this.clearBtn.disabled = true;

        for (const item of pending) {
            item.status = Status.Uploading;
            item.error = null;
            this.render();

            try {
                await dispatchCommand('upload-activity-file', {
                    filename: item.name,
                    content: await readFileAsBase64(item.file),
                });
                item.status = Status.Uploaded;
            } catch (err) {
                item.status = Status.Error;
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
        const uploadableCount = this.files.filter((f) => f.ok && f.status !== Status.Uploaded && f.status !== Status.Uploading).length;
        const uploadedCount = this.files.filter((f) => f.status === Status.Uploaded).length;
        const isUploading = this.files.some((f) => f.status === Status.Uploading);

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
            const li = this.itemTemplate.content.firstElementChild.cloneNode(true);
            if (!file.ok || file.status === Status.Error) li.classList.add('is-invalid');
            if (file.status === Status.Uploading) li.classList.add('is-uploading');
            if (file.status === Status.Uploaded) li.classList.add('is-uploaded');
            li.querySelector('.name').textContent = file.name;
            li.querySelector('.meta').textContent = metaFor(file, this.translations);
            li.querySelector('[data-remove-file]').dataset.index = index;
            this.list.appendChild(li);
        });
    }
}
