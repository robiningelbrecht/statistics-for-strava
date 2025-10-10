import LeafletMap from "./leaflet/leaflet-map";
import {Heatmap} from "./leaflet/heatmap";
import {DataTable, DataTableStorage} from "./data-table";
import Router from "./router";
import Chat from "./chat";
import {resolveEchartsCallbacks, compareVersions} from "./utils";

const $main = document.querySelector("main");
const $sideNav = document.querySelector("aside");
const $topNav = document.querySelector("nav");

// Toggle sidebar collapsed state.
const sideNavCollapsed = localStorage.getItem('sideNavCollapsed');
if (sideNavCollapsed === 'true') {
    $main.classList.add('sidebar-is-collapsed');
    $sideNav.classList.add('sidebar-is-collapsed');
    $topNav.classList.add('sidebar-is-collapsed');
}

document.getElementById('toggle-sidebar-collapsed-state').addEventListener('click', () => {
    $main.classList.toggle('sidebar-is-collapsed');
    $sideNav.classList.toggle('sidebar-is-collapsed');
    $topNav.classList.toggle('sidebar-is-collapsed');

    localStorage.setItem('sideNavCollapsed', 'false');
    if ($main.classList.contains('sidebar-is-collapsed')) {
        localStorage.setItem('sideNavCollapsed', 'true');
    }
});

// Boot router.
const router = new Router($main);
router.boot();

const dataTableStorage = new DataTableStorage();

const modalSkeletonNode = document.getElementById('modal-skeleton');
const modalContent = modalSkeletonNode.querySelector('#modal-content');
const modalSpinner = modalSkeletonNode.querySelector('.spinner');
let modal = null;
let renderedChartsPerTab = [];
let allRenderedCharts = [];
document.addEventListener('pageWasLoaded', (e) => {
    if (modal) {
        // Close any old modals when a new page is loaded.
        modal.hide();
    }

    document.querySelectorAll('div[data-dataTable-settings]').forEach(function ($dataTableWrapperNode) {
        new DataTable($dataTableWrapperNode).render()
    });

    renderedChartsPerTab = [];
    allRenderedCharts = [];
    initElements(document);

    if (e.detail && e.detail.modalId) {
        // Open modal.
        openModal(e.detail.modalId);
    }
});
document.addEventListener('pageWasLoaded.heatmap', () => {
    const $heatmapWrapper = document.querySelector('.heatmap-wrapper');
    new Heatmap($heatmapWrapper).render();
});
document.addEventListener('navigationLinkHasBeenClicked', (e) => {
    if (!e.detail || !e.detail.link) {
        return;
    }
    if(!e.detail.link.hasAttribute('data-dataTable-filters')){
        return;
    }
    dataTableStorage.set(JSON.parse(e.detail.link.getAttribute('data-dataTable-filters')));
});
document.addEventListener('dataTableClusterWasChanged', () => {
    initModals(document);
});
document.getElementById('toggle-sidebar-collapsed-state').addEventListener('click', () => {
    allRenderedCharts.forEach(chart => {
        chart.resize();
    });
})
window.addEventListener('resize', function () {
    allRenderedCharts.forEach(chart => {
        chart.resize();
    });
});

const $modalAIChat = document.querySelector('a[data-modal-custom-ai]');
if ($modalAIChat) {
    $modalAIChat.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const modalId = $modalAIChat.getAttribute('data-modal-custom-ai');
        openModal(modalId);
        // Add modal to history state.
        router.pushCurrentRouteToHistoryState(modalId);
    });
}

document.addEventListener('modalWasLoaded.ai-chat', (e) => {
    const $modal = e.detail.modal;
    Chat($modal).render();
});

const initElements = (rootNode) => {
    lazyLoad.update();

    initTabs(rootNode);
    initPopovers();
    initTooltips();
    initDropdowns();
    initAccordions();
    initModals(rootNode);

    const chartClickHandlers = {
        handleActivityGridChartClick: (params) => {
            if (!params || !params.value || params.value < 1) {
                return;
            }

            // Make sure results are prefiltered by clicked date.
            dataTableStorage.set({
                'activities' : {
                    "start-date.from": params.value[0],
                    "start-date.to": params.value[0]
                }
            });

            router.navigateTo(`/activities`);
        },
    };

    // Render charts.
    const connectedCharts = [];
    rootNode.querySelectorAll('[data-echarts-options]').forEach(function (chartNode) {
        const chart = echarts.init(chartNode);
        const chartOptions = JSON.parse(chartNode.getAttribute('data-echarts-options'));
        resolveEchartsCallbacks(chartOptions, 'tooltip.formatter');
        resolveEchartsCallbacks(chartOptions, 'tooltip.valueFormatter');
        resolveEchartsCallbacks(chartOptions, 'yAxis.axisLabel.formatter');
        resolveEchartsCallbacks(chartOptions, 'yAxis[].axisLabel.formatter');
        resolveEchartsCallbacks(chartOptions, 'series.symbolSize');

        chart.setOption(chartOptions);

        const clickHandlerName = chartNode.getAttribute('data-echarts-click');
        if (clickHandlerName && chartClickHandlers[clickHandlerName]) {
            chart.on('click', function (params) {
                chartClickHandlers[clickHandlerName](params);
            });
        }
        if (chartNode.hasAttribute('data-echarts-connect')) {
            connectedCharts.push(chart);
        }

        allRenderedCharts.push(chart);

        const $tabPanel = chartNode.closest('div[role="tabpanel"]');
        if ($tabPanel) {
            const tabPanelId = $tabPanel.getAttribute('id');
            renderedChartsPerTab[tabPanelId] = renderedChartsPerTab[tabPanelId] || [];
            renderedChartsPerTab[tabPanelId].push(chart);
        }
    });
    echarts.connect(connectedCharts);

    // Render Leaflet maps.
    rootNode.querySelectorAll('[data-leaflet]').forEach(function (mapNode) {
        const data = JSON.parse(mapNode.getAttribute('data-leaflet'));
        LeafletMap(mapNode, data).render();
    });

    // Init LightGallery.
    rootNode.querySelectorAll('[data-light-gallery-elements]').forEach(function (node) {
        const elements = JSON.parse(node.getAttribute('data-light-gallery-elements'));

        const dynamicGallery = lightGallery(node, {
            dynamic: true,
            plugins: [lgZoom],
            backdropDuration: 200,
            dynamicEl: elements,
        });

        node.addEventListener('click', () => {
            dynamicGallery.openGallery();
        });
    });

};

const initModals = (rootNode) => {
    const modalLinks = rootNode.querySelectorAll('a[data-model-content-url]');

    modalLinks.forEach(function (node) {
        node.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const modalId = node.getAttribute('data-model-content-url');
            openModal(modalId);
            // Add modal to history state.
            router.pushCurrentRouteToHistoryState(modalId);
        });
    });
};

const initTabs = (rootNode) => {
    rootNode.querySelectorAll('[data-tabs]').forEach(($triggerEl) => {
        const tabItems = [];
        let defaultTabId = null;

        $triggerEl
            .querySelectorAll('[role="tab"]')
            .forEach(function ($triggerEl) {
                const dataTabsTarget = $triggerEl.getAttribute('data-tabs-target');
                tabItems.push({
                    id: dataTabsTarget,
                    triggerEl: $triggerEl,
                    targetEl: document.querySelector(dataTabsTarget),
                });
                if ($triggerEl.hasAttribute('data-tab-default')) {
                    defaultTabId = dataTabsTarget;
                }
            });

        new Tabs($triggerEl, tabItems, {
            defaultTabId: defaultTabId,
            activeClasses: 'text-strava-orange border-strava-orange hover:text-gray-600 hover:border-gray-300',
            inactiveClasses: 'text-gray-500 hover:text-gray-600 border-gray-100 hover:border-gray-300',
            onShow: (tabs, activeTab) => {
                const activeTabId = activeTab.id.replace('#', '');
                if (activeTabId in renderedChartsPerTab) {
                    // Trigger a chart resize to make sure charts are rendered and displayed.
                    renderedChartsPerTab[activeTabId].forEach((chart) => chart.resize());
                }

            },
        });
    });
}

export const openModal = (modalId) => {
    if (modal) {
        // Only allow one open modal at a time.
        modal.hide();
    }

    // Show loading state.
    modalSpinner.classList.remove('hidden');
    modalSpinner.classList.add('flex');

    modal = new Modal(modalSkeletonNode, {
        placement: 'bottom',
        closable: true,
        backdropClasses: 'bg-gray-900/50 fixed inset-0 z-1400',
        onShow: async () => {
            const response = await fetch(modalId, {cache: 'no-store'});
            // Remove loading state.
            modalSpinner.classList.add('hidden');
            modalSpinner.classList.remove('flex');

            modalContent.innerHTML = await response.text();
            // Init elements in modal.
            initElements(modalSkeletonNode);
            // Modal close event listeners.
            const closeButton = modalContent.querySelector('button.close');
            if (closeButton) {
                modalContent.querySelector('button.close').addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    modal.hide();
                    router.pushCurrentRouteToHistoryState();
                });
            }

            document.body.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') {
                    return;
                }
                router.pushCurrentRouteToHistoryState();
            }, {once: true});

            document.body.addEventListener('click', (e) => {
                if (e.target.id !== 'modal-skeleton') {
                    return;
                }
                router.pushCurrentRouteToHistoryState();
            }, {once: true});

            // Re-register nav items that may have been added dynamically
            const newNavItems = modalSkeletonNode.querySelectorAll('a[data-router-navigate]:not([data-router-disabled])');
            router.registerNavItems(newNavItems);

            const modalName = modalId.replace(/^\/+/, '').replaceAll('/', '-');
            document.dispatchEvent(new CustomEvent('modalWasLoaded.' + modalName, {
                bubbles: true, cancelable: false, detail: {
                    modal: modalSkeletonNode
                }
            }));
        },
        onHide: () => {
            modalContent.innerHTML = '';
        }
    });

    modal.show();
}

const updateGithubLatestRelease = async () => {
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

const showLatestVersion = (latestVersion) => {
    const $latestVersionEl = document.querySelector('[data-latest-version]');
    const $link = $latestVersionEl.querySelector('a');
    if ($link) {
        $link.href = $link.href.replace('[LATEST_VERSION]', latestVersion);
        $link.textContent = $link.textContent.replace('[LATEST_VERSION]', latestVersion);
    }
    $latestVersionEl.classList.remove('hidden');
};

updateGithubLatestRelease();