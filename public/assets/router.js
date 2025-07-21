export default function Router(app) {
    const appContent = app.querySelector('#js-loaded-content');
    const spinner = app.querySelector('#spinner');
    const menu = document.querySelector('aside');
    const menuItems = document.querySelectorAll("aside li a[data-router-navigate]:not([data-router-disabled])");
    const mobileNavTriggerEl = document.querySelector('[data-drawer-target="drawer-navigation"]');
    const defaultRoute = '/dashboard';

    const showLoader = () => {
        spinner.classList.remove('hidden');
        spinner.classList.add('flex');
        appContent.classList.add('hidden');
    };

    const hideLoader = () => {
        spinner.classList.remove('flex');
        spinner.classList.add('hidden');
        appContent.classList.remove('hidden');
    };

    const determineActiveMenuLink = (url) => {
        const $activeMenuLink = document.querySelector('aside li a[data-router-navigate="' + url + '"]');
        if ($activeMenuLink) {
            return $activeMenuLink;
        }

        const newUrl = url.replace(/\/[^\/]*$/, '');
        if (newUrl === url || newUrl === '') {
            return null;
        }

        return determineActiveMenuLink(newUrl);
    };

    const renderContent = async (page, modalId) => {
        if (!menu.hasAttribute('aria-hidden')) {
            // Trigger click event to close mobile nav.
            mobileNavTriggerEl.dispatchEvent(new MouseEvent('click', {
                bubbles: true, cancelable: true, view: window
            }));
        }

        showLoader();

        // Load content.
        const response = await fetch(page + '.html', {cache: 'no-store'});
        appContent.innerHTML = await response.text();
        window.scrollTo(0, 0);

        hideLoader();

        app.setAttribute('data-router-current', page);
        app.setAttribute('data-modal-current', modalId);
        // Manage active classes.
        menuItems.forEach(node => {
            node.setAttribute('aria-selected', 'false')
        });

        const $activeMenuLink = determineActiveMenuLink(page);
        $activeMenuLink?.setAttribute('aria-selected', 'true')
        if ($activeMenuLink && $activeMenuLink.hasAttribute('data-router-sub-menu')) {
            // Make sure the sub menu is opened.
            $activeMenuLink.closest('ul')?.classList.remove('hidden');
        }

        // There might be other nav links on the newly loaded page, make sure they are registered.
        const nav = document.querySelectorAll("nav a[data-router-navigate]:not([data-router-disabled]), main a[data-router-navigate]:not([data-router-disabled])");
        registerNavItems(nav);

        const fullPageName = page.replace(/^\/+/, '').replaceAll('/', '-');
        document.dispatchEvent(new CustomEvent('pageWasLoaded', {
            bubbles: true, cancelable: false, detail: {
                page: fullPageName,
                modalId: modalId
            }
        }));
        document.dispatchEvent(new CustomEvent('pageWasLoaded.' + fullPageName, {
            bubbles: true, cancelable: false, detail: {
                page: fullPageName,
                modalId: modalId
            }
        }));
    };

    const registerNavItems = (items) => {
        items.forEach(function (to) {
            to.addEventListener("click", (e) => {
                e.preventDefault();
                const route = to.getAttribute('data-router-navigate');
                const currentRoute = app.getAttribute('data-router-current');
                if (currentRoute === route) {
                    // Do not reload the same page.
                    return
                }

                renderContent(route, null);
                pushRouteToHistoryState(route);
            });
        });
    };

    const registerBrowserBackAndForth = () => {
        window.onpopstate = function (e) {
            if (!e.state) {
                return;
            }
            renderContent(e.state.route, e.state.modal);
        };
    };

    const pushRouteToHistoryState = (route, modal) => {
        const fullRouteWithModal = modal ? route + '#' + modal : route;

        window.history.pushState({
            route: route, modal: modal
        }, "", fullRouteWithModal);
    };

    const pushCurrentRouteToHistoryState = (modal) => {
        pushRouteToHistoryState(currentRoute(), modal);
    }

    const currentRoute = () => {
        return location.pathname.replace('/', '') ? location.pathname : defaultRoute;
    };

    const boot = () => {
        if (appContent === null) {
            // App content can be null if SYMFONY routing is used.
            return;
        }
        const route = currentRoute();
        const modal = location.hash.replace('#', '');

        registerNavItems(menuItems);
        registerBrowserBackAndForth();
        renderContent(route, modal);
        window.history.replaceState({
            route: route, modal: modal
        }, "", route + location.hash);
    }

    return {
        boot, pushCurrentRouteToHistoryState
    };
}
