import {eventBus, Events} from "../core/event-bus";

export default class Sidebar {
    init() {
        document.getElementById('toggle-sidebar-collapsed-state').addEventListener('click', () => {
            const collapsed = document.documentElement.toggleAttribute('data-sidebar-collapsed');
            localStorage.setItem('sideNavCollapsed', String(collapsed));
            eventBus.emit(Events.SIDEBAR_RESIZED);
        });
    }
}