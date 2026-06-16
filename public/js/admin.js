import Sidebar from "./components/sidebar";
import {initDrawers} from "flowbite";

initDrawers();

const sidebar = new Sidebar();
sidebar.init();

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
