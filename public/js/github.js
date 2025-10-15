const compareVersions = (a, b) => {
    a = a.replace(/^v/, '');
    b = b.replace(/^v/, '');

    const pa = a.split('.').map(Number);
    const pb = b.split('.').map(Number);

    for (let i = 0; i < Math.max(pa.length, pb.length); i++) {
        const na = pa[i] || 0;
        const nb = pb[i] || 0;
        if (na > nb) return 1;
        if (na < nb) return -1;
    }
    return 0;
}

const showLatestVersion = (latestVersion) => {
    const $latestVersionEl = document.querySelector('[data-latest-version]');
    const $link = $latestVersionEl.querySelector('a');
    if ($link) {
        $link.href = $link.href.replace('[LATEST_VERSION]', latestVersion);
        $link.textContent = $link.textContent.replace('[LATEST_VERSION]', latestVersion);
    }
    $latestVersionEl.classList.remove('hidden');
};

export const updateGithubLatestRelease = async () => {
    const $latestVersionEl = document.querySelector('[data-latest-version]');
    if (!$latestVersionEl) return;

    const currentVersion = $latestVersionEl.dataset.currentVersion;
    if (!currentVersion) return;

    const CACHE_KEY = 'latestReleaseCache';
    const CACHE_TTL = 1000 * 60 * 60 * 6; // 6 hours

    const now = Date.now();
    const cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');

    // If cache exists and is recent, use it
    if (cached && now - cached.timestamp < CACHE_TTL) {
        if (compareVersions(currentVersion, cached.latestVersion) < 0) {
            // Update available.
            showLatestVersion(cached.latestVersion);
        }
        return;
    }

    try {
        const releaseResponse = await fetch(
            'https://api.github.com/repos/robiningelbrecht/statistics-for-strava/releases/latest',
            { headers: { 'Accept': 'application/vnd.github+json' } }
        );
        if (!releaseResponse.ok) throw new Error('Failed to fetch latest release');

        const latestVersion = (await releaseResponse.json()).name;

        if (currentVersion === latestVersion) {
            localStorage.setItem(CACHE_KEY, JSON.stringify({ latestVersion, timestamp: now }));
            return;
        }

        // Verify Docker image workflow run success
        const workflowRunsResponse = await fetch(
            `https://api.github.com/repos/robiningelbrecht/statistics-for-strava/actions/runs?event=push&status=completed&conclusion=success&exclude_pull_requests=true&branch=${latestVersion}`,
            { headers: { 'Accept': 'application/vnd.github+json' } }
        );
        if (!workflowRunsResponse.ok) throw new Error('Failed to fetch workflow runs');

        const { workflow_runs = [] } = await workflowRunsResponse.json();
        const dockerWorkflowRun = workflow_runs.find(
            run => run.path === '.github/workflows/docker-image.yml'
        );

        if(!dockerWorkflowRun) return;

        showLatestVersion(latestVersion);
        localStorage.setItem(CACHE_KEY, JSON.stringify({ latestVersion, timestamp: now }));
    } catch (err) {
        console.error('Error checking latest release:', err);
    }
}