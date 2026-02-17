import {eventBus, Events} from "../core/event-bus";

export default class DarkModeManager {
    constructor() {
        this.storageKey = 'theme';
        this.$toggleButton = document.querySelector('.dark-mode-toggle input');
        this.$themeElement = document.documentElement;
        this.dataAttributeName = 'data-theme';
    }

    attachEventListeners() {
        this.$toggleButton.checked = this.isDarkModeEnabled();
        this.$toggleButton.addEventListener('change', () => {
            this.setDark(!this.isDarkModeEnabled());
        });
    }

    isDarkModeEnabled() {
        return this.$themeElement.hasAttribute(this.dataAttributeName)
            && this.$themeElement.getAttribute(this.dataAttributeName) === 'dark';
    }

    setDark(enabled) {
        const theme = enabled ? 'dark' : 'light';
        localStorage.setItem(this.storageKey, theme);
        this.$themeElement.setAttribute(this.dataAttributeName, theme);

        this.$toggleButton.checked = enabled;

        eventBus.emit(Events.DARK_MODE_TOGGLED, {darkModeEnabled: enabled});
    }
}
