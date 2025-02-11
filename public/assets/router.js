const app = document.querySelector("main");
const appContent = app.querySelector('#js-loaded-content');
const spinner = app.querySelector('#spinner');
const menu = document.querySelector('aside');
const menuItems = document.querySelectorAll("aside li a[data-router-navigate]");
const mobileNavTriggerEl = document.querySelector('[data-drawer-target="drawer-navigation"]');
const defaultRoute = 'dashboard';

const renderContent = async (page, modal) => {
    if (!menu.hasAttribute('aria-hidden')) {
        // Trigger click event to close mobile nav.
        mobileNavTriggerEl.dispatchEvent(
            new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            })
        );
    }

    // Show loader.
    spinner.classList.remove('hidden');
    spinner.classList.add('flex');
    appContent.classList.add('hidden');

    // Load content.
    const response = await fetch(page + '.html', {cache: 'no-store'});
    appContent.innerHTML = await response.text();
    window.scrollTo(0, 0);

    // Hide loader.
    spinner.classList.remove('flex');
    spinner.classList.add('hidden');
    appContent.classList.remove('hidden');

    app.setAttribute('data-router-current', page);
    app.setAttribute('data-modal-current', modal);
    // Manage active classes.
    menuItems.forEach(node => {
        node.setAttribute('aria-selected', 'false')
    });
    const $activeMenuLink = document.querySelector('aside li a[data-router-navigate="' + page + '"]');
    if ($activeMenuLink) {
        $activeMenuLink.setAttribute('aria-selected', 'true');
    }

    // There might be other nav links on the newly loaded page, make sure they are registered.
    const nav = document.querySelectorAll("nav a[data-router-navigate], main a[data-router-navigate]");
    registerNavItems(nav);

    document.dispatchEvent(new CustomEvent('pageWasLoaded', {
        bubbles: true,
        cancelable: false,
    }));
    document.dispatchEvent(new CustomEvent('pageWasLoaded.' + page, {
        bubbles: true,
        cancelable: false,
    }));

    if (modal) {
        // Open modal.
        openModal(modal);
    }
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

const currentRoute = () => {
    return location.pathname.replace('/', '') || defaultRoute;
};

const pushCurrentRouteToHistoryState = (modal) => {
    pushRouteToHistoryState(currentRoute(), modal);
}

const pushRouteToHistoryState = (route, modal) => {
    const fullRouteWithModal = modal ? route + '#' + modal : route;

    window.history.pushState({
        route: route,
        modal: modal
    }, "", fullRouteWithModal);
};

(function boot() {
    const route = currentRoute();
    const modal = location.hash.replace('#', '');

    registerNavItems(menuItems);
    registerBrowserBackAndForth();
    renderContent(route, modal);
    window.history.replaceState({
        route: route,
        modal: modal
    }, "", route + location.hash);
})();
