import {eventBus, Events} from "../core/event-bus";

export default class FullscreenManager {
    init(rootNode) {
        rootNode.querySelectorAll('[data-fullscreen-trigger]').forEach((el) => {
            el.addEventListener('click', (e) => {
                e.preventDefault();

                if (document.fullscreenElement) {
                    return;
                }

                const fullScreenContent = el.closest('[data-fullscreen-content]');
                fullScreenContent.requestFullscreen().then(() => {
                    eventBus.emit(Events.FULLSCREEN_ENABLED);
                });

                fullScreenContent.addEventListener('fullscreenchange', () => {
                    el.classList.toggle('hidden', Boolean(document.fullscreenElement));
                    fullScreenContent.classList.toggle('fullscreen-is-enabled', Boolean(document.fullscreenElement));
                    fullScreenContent.classList.toggle('group', Boolean(document.fullscreenElement));
                });

            });
        });
    }
}