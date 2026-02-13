export default class ModalManager {
    constructor(router) {
        this.router = router;
        this.modalSkeletonNode = document.getElementById('modal-skeleton');
        this.modalContent = this.modalSkeletonNode.querySelector('#modal-content');
        this.modalSpinner = this.modalSkeletonNode.querySelector('.spinner');
        this.modal = null;
    }

    init(rootNode) {
        const modalLinks = rootNode.querySelectorAll('a[data-model-content-url]');

        modalLinks.forEach(node => {
            node.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const modalId = node.getAttribute('data-model-content-url');
                this.open(modalId);
                // Add modal to history state.
                this.router.pushCurrentRouteToHistoryState(modalId);
            });
        });
    }

    open(modalId) {
        this.close();

        // Show loading state.
        this.modalSpinner.classList.remove('hidden');
        this.modalSpinner.classList.add('flex');

        this.modal = new Modal(this.modalSkeletonNode, {
            placement: 'bottom',
            closable: true,
            backdropClasses: 'bg-gray-900/50 fixed inset-0 z-1400',
            onShow: async () => {
                const response = await fetch(modalId, {cache: 'no-store'});
                // Remove loading state.
                this.modalSpinner.classList.add('hidden');
                this.modalSpinner.classList.remove('flex');

                this.modalContent.innerHTML = await response.text();
                document.dispatchEvent(new CustomEvent('modalWasLoaded', {
                    bubbles: true,
                    cancelable: false,
                    detail: {node: this.modalSkeletonNode}
                }));
                // Modal close event listeners.
                const closeButton = this.modalContent.querySelector('button.close');
                if (closeButton) {
                    this.modalContent.querySelector('button.close').addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.modal.hide();
                        this.router.pushCurrentRouteToHistoryState();
                    });
                }

                document.body.addEventListener('keydown', (e) => {
                    if (e.key !== 'Escape') {
                        return;
                    }
                    this.router.pushCurrentRouteToHistoryState();
                }, {once: true});

                document.body.addEventListener('click', (e) => {
                    if (e.target.id !== 'modal-skeleton') {
                        return;
                    }
                    this.router.pushCurrentRouteToHistoryState();
                }, {once: true});

                // Re-register nav items that may have been added dynamically
                const newNavItems = this.modalSkeletonNode.querySelectorAll('a[data-router-navigate]:not([data-router-disabled])');
                this.router.registerNavItems(newNavItems);

                const modalName = modalId.replace(/^\/+/, '').replaceAll('/', '-');
                document.dispatchEvent(new CustomEvent('modalWasLoaded.' + modalName, {
                    bubbles: true, cancelable: false, detail: {
                        modal: this.modalSkeletonNode
                    }
                }));
            },
            onHide: () => {
                this.modalContent.innerHTML = '';
            }
        });

        this.modal.show();
    }

    close() {
        if (!this.modal) {
            return;
        }

        this.modal.hide();
    }
}