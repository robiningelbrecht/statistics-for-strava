import {dispatchCommand} from "../utils";

const showError = (box, message) => {
    if (!box) return;
    box.textContent = message;
    box.classList.remove('hidden');
};

const hideError = (box) => {
    if (!box) return;
    box.textContent = '';
    box.classList.add('hidden');
};

const resetLoading = (buttons) => {
    buttons.forEach((button) => {
        button.classList.remove('is-loading');
        button.disabled = false;
    });
};

export default function initDispatchCommandForm(rootNode = document) {
    rootNode.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-dispatch-command]')) {
            return;
        }

        event.preventDefault();

        const commandName = form.getAttribute('data-dispatch-command');
        const errorBox = form.querySelector('[data-form-error]');
        const loadingButtons = form.querySelectorAll('button[data-has-loading-state]');

        hideError(errorBox);

        const payload = {};
        new FormData(form).forEach((value, key) => {
            payload[key] = value;
        });

        try {
            await dispatchCommand(commandName, payload);

            const redirectUrl = form.getAttribute('data-redirect');
            if (redirectUrl) {
                window.location.assign(redirectUrl);
            } else {
                window.location.reload();
            }
        } catch (error) {
            showError(errorBox, error.message);
            resetLoading(loadingButtons);
        }
    });
}
