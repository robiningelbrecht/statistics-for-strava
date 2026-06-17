import initSidebar from "./components/sidebar";
import initPasswordToggle from "./components/password-toggle";
import {initDrawers} from "flowbite";

initDrawers();

initSidebar();
initPasswordToggle();

document.addEventListener('submit', function (event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.querySelectorAll('button[data-has-loading-state]').forEach(function (button) {
        button.classList.add('is-loading');
        button.disabled = true;
    });
});
