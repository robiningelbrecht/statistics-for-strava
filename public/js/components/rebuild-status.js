import {fetchJson} from "../utils";

const POLL_INTERVAL_MS = 30000;

export default function initRebuildStatus() {
    const badge = document.getElementById('rebuild-pending-badge');
    if (!badge) {
        return;
    }

    const url = document.querySelector('meta[name="rebuild-status-url"]')?.getAttribute('content');
    if (!url) {
        return;
    }

    let intervalId = null;

    const stop = () => {
        if (intervalId !== null) {
            clearInterval(intervalId);
            intervalId = null;
        }
    };

    const poll = async () => {
        try {
            const {pending} = await fetchJson(url);
            if (!pending) {
                badge.remove();
                stop();
            }
        } catch {

        }
    };

    const start = () => {
        if (intervalId === null && document.getElementById('rebuild-pending-badge')) {
            intervalId = setInterval(poll, POLL_INTERVAL_MS);
        }
    };

    // Only poll while the tab is visible to avoid needless background requests.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });

    if (!document.hidden) {
        start();
    }
}
