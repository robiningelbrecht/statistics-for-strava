export default class DarkModeManager {
    constructor() {
        this.storageKey = 'theme';
        this.$toggleButton = document.querySelector('.dark-mode-toggle input');
    }

    init() {
        const stored = localStorage.getItem(this.storageKey);

        this.setDark(false);
        if (stored === 'dark') {
            this.setDark(true);
        }

        this.$toggleButton.addEventListener('change', () => {
            this.setDark(!this.isDarkModeEnabled());
        });
    }

    isDarkModeEnabled() {
        return document.body.classList.contains('dark');
    }

    setDark(enabled) {
        localStorage.setItem(this.storageKey, enabled ? 'dark' : 'light');
        document.body.classList.toggle('dark', enabled);
        this.$toggleButton.checked = enabled;

        document.dispatchEvent(new CustomEvent('darkModeWasToggled', {
            bubbles: true,
            detail: {darkModeEnabled: enabled}
        }));
    }
}
