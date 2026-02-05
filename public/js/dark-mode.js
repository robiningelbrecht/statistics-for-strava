export default class DarkModeManager {
    constructor() {
        this.storageKey = 'theme';
    }

    init() {
        const stored = localStorage.getItem(this.storageKey);

        if (stored === 'dark') {
            this.setDark(true);
        } else if (stored === 'light') {
            this.setDark(false);
        } else {
            this.setDark(window.matchMedia('(prefers-color-scheme: dark)'));
        }
    }

    toggle() {
        this.setDark(!this.isDarkModeEnabled());
        localStorage.setItem(this.storageKey, this.isDarkModeEnabled() ? 'dark' : 'light');
    }

    isDarkModeEnabled() {
        return document.body.classList.contains('dark');
    }

    setDark(enabled) {
        document.body.classList.toggle('dark', enabled);

        document.dispatchEvent(new CustomEvent('darkModeWasToggled', {
            bubbles: true,
            detail: {darkModeEnabled: enabled}
        }));
    }
}
