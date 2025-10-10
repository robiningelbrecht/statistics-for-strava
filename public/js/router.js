export default class Router {
    constructor(app) {
        this.app = app;
        this.appContent = app.querySelector('#js-loaded-content');
        this.spinner = app.querySelector('#spinner');
        this.menu = document.querySelector('aside');
        this.menuItems = document.querySelectorAll(
            'nav a[data-router-navigate]:not([data-router-disabled]), aside li a[data-router-navigate]:not([data-router-disabled])'
        );
        this.mobileNavTriggerEl = document.querySelector('[data-drawer-target="drawer-navigation"]');
        this.defaultRoute = '/dashboard';
    }

    showLoader() {
        this.spinner.classList.remove('hidden');
        this.spinner.classList.add('flex');
        this.appContent.classList.add('hidden');
    }

    hideLoader() {
        this.spinner.classList.remove('flex');
        this.spinner.classList.add('hidden');
        this.appContent.classList.remove('hidden');
    }

    determineActiveMenuLink(url) {
        const activeLink = document.querySelector(`aside li a[data-router-navigate="${url}"]`);
        if (activeLink) {
            return activeLink;
        }

        const newUrl = url.replace(/\/[^\/]*$/, '');
        if (newUrl === url || newUrl === '') {
            return null;
        }

        return this.determineActiveMenuLink(newUrl);
    }

    async renderContent(page, modalId) {
        // Close mobile nav if open
        if (!this.menu.hasAttribute('aria-hidden')) {
            this.mobileNavTriggerEl.dispatchEvent(
                new MouseEvent('click', { bubbles: true, cancelable: true, view: window })
            );
        }

        this.showLoader();

        const response = await fetch(`${page}.html`, { cache: 'no-store' });
        this.appContent.innerHTML = await response.text();
        window.scrollTo(0, 0);

        this.hideLoader();

        this.app.setAttribute('data-router-current', page);
        this.app.setAttribute('data-modal-current', modalId);

        // Update active states
        this.menuItems.forEach(node => node.setAttribute('aria-selected', 'false'));

        const activeLink = this.determineActiveMenuLink(page);
        activeLink?.setAttribute('aria-selected', 'true');

        if (activeLink?.hasAttribute('data-router-sub-menu')) {
            activeLink.closest('ul')?.classList.remove('hidden');
        }

        // Re-register nav items that may have been added dynamically
        const newNavItems = document.querySelectorAll('main a[data-router-navigate]:not([data-router-disabled])');
        this.registerNavItems(newNavItems);

        const fullPageName = page.replace(/^\/+/, '').replaceAll('/', '-');

        document.dispatchEvent(new CustomEvent('pageWasLoaded', {
            bubbles: true,
            detail: { page: fullPageName, modalId }
        }));

        document.dispatchEvent(new CustomEvent(`pageWasLoaded.${fullPageName}`, {
            bubbles: true,
            detail: { page: fullPageName, modalId }
        }));
    }

    registerNavItems(items) {
        items.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                const route = link.getAttribute('data-router-navigate');

                document.dispatchEvent(new CustomEvent('navigationLinkHasBeenClicked', {
                    bubbles: true,
                    detail: { link }
                }));

                this.navigateTo(
                    route,
                    null,
                    link.hasAttribute('data-router-force-reload')
                );
            });
        });
    }

    registerBrowserBackAndForth() {
        window.onpopstate = e => {
            if (!e.state) return;
            this.renderContent(e.state.route, e.state.modal);
        };
    }

    navigateTo(route, modal, force = false) {
        const currentRoute = this.app.getAttribute('data-router-current');
        if (currentRoute === route && !force) return; // Avoid reloading same page.

        this.renderContent(route, modal);
        this.pushRouteToHistoryState(route, modal);
    }

    pushRouteToHistoryState(route, modal) {
        const fullRoute = modal ? `${route}#${modal}` : route;
        window.history.pushState({ route, modal }, '', fullRoute);
    }

    pushCurrentRouteToHistoryState(modal) {
        this.pushRouteToHistoryState(this.currentRoute(), modal);
    }

    currentRoute() {
        return location.pathname.replace('/', '') ? location.pathname : this.defaultRoute;
    }

    boot() {
        if (this.appContent === null) {
            // App content can be null if SYMFONY routing is used.
            return;
        }

        const route = this.currentRoute();
        const modal = location.hash.replace('#', '');

        this.registerNavItems(this.menuItems);
        this.registerBrowserBackAndForth();
        this.renderContent(route, modal);

        window.history.replaceState({ route, modal }, '', route + location.hash);
    }
}
