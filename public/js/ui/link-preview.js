export default class LinkPreviewManager {
    constructor() {
        this.$sharedPopoverEl = document.getElementById('popover-content');
        this.showDelayInMilliseconds = 1000;
        this.hideDelayInMilliSeconds = 150;
        this.cache = new Map();
    }


    async fetchContent(url, abortController) {
        if (this.cache.has(url)) {
            return this.cache.get(url);
        }

        try {
            const response = await fetch(url, {
                signal: abortController.signal,
            });

            const html = await response.text();
            this.cache.set(url, html);

            return html;
        } catch (err) {
            if (err.name !== 'AbortError') {
                throw err;
            }
        }
    }

    init(rootNode) {
        rootNode.querySelectorAll('[data-link-preview-url]').forEach(($triggerEl) => {
            const contentToFetch = $triggerEl.getAttribute('data-link-preview-url');

            const popover = new Popover(this.$sharedPopoverEl, $triggerEl, {
                offset: 10,
                triggerType: 'none',
            }, {
                id: 'popover-content',
                override: true
            });

            let showTimeout;
            let hideTimeout;
            let abortController = null;


            const clearTimers = () => {
                clearTimeout(showTimeout);
                clearTimeout(hideTimeout);
            };

            $triggerEl.addEventListener('mouseenter', () => {
                clearTimers();
                abortController = new AbortController();

                showTimeout = setTimeout(async () => {
                    this.$sharedPopoverEl.innerHTML = await this.fetchContent(contentToFetch);
                    popover.show();
                }, this.showDelayInMilliseconds);
            });

            $triggerEl.addEventListener('mouseleave', () => {
                clearTimers();
                abortController?.abort();

                hideTimeout = setTimeout(() => popover.hide(), this.hideDelayInMilliSeconds);
            });
        });
    }
}