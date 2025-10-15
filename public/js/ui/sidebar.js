export default class Sidebar {
    constructor($main) {
        this.$main = $main;
        this.$sideNav = document.querySelector("aside");
        this.$topNav = document.querySelector("nav");
    }

    init() {
        const collapsed = localStorage.getItem('sideNavCollapsed') === 'true';
        [this.$main, this.$sideNav, this.$topNav].forEach((el) =>
            el.classList.toggle('sidebar-is-collapsed', collapsed)
        );

        document.getElementById('toggle-sidebar-collapsed-state').addEventListener('click', () => {
            const collapsed = this.$main.classList.toggle('sidebar-is-collapsed');
            [this.$sideNav, this.$topNav].forEach(el => el.classList.toggle('sidebar-is-collapsed', collapsed));

            localStorage.setItem('sideNavCollapsed', String(collapsed));
            document.dispatchEvent(new Event('sidebarWasResized'));
        });
    }
}