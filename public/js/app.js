import LeafletMap from "./leaflet/leaflet-map";
import {Heatmap} from "./leaflet/heatmap";
import {DataTable, DataTableStorage} from "./data-table";
import Router from "./router";
import Chat from "./chat";
import {resolveEchartsCallbacks} from "./utils";
import {updateGithubLatestRelease} from "./github";
import LazyLoad from "../libraries/lazyload.min";
import lightGallery from "../libraries/lightgallery/lightgallery.umd.min";
import lgZoom from "../libraries/lightgallery/lightgallery.lg-zoom.min";

const $main = document.querySelector("main");
const $sideNav = document.querySelector("aside");
const $topNav = document.querySelector("nav");

const resizeAllCharts = () => allRenderedCharts.forEach(chart => chart.resize());

const collapsed = localStorage.getItem('sideNavCollapsed') === 'true';
[$main, $sideNav, $topNav].forEach((el) =>
    el.classList.toggle('sidebar-is-collapsed', collapsed)
);

document.getElementById('toggle-sidebar-collapsed-state').addEventListener('click', () => {
    const collapsed = $main.classList.toggle('sidebar-is-collapsed');
    [$sideNav, $topNav].forEach(el => el.classList.toggle('sidebar-is-collapsed', collapsed));

    localStorage.setItem('sideNavCollapsed', String(collapsed));
    resizeAllCharts();
});

// Enable image lazy load.
const lazyLoad = new LazyLoad({
    thresholds: "50px",
    callback_error: (img) => {
        img.setAttribute("src", window.statisticsForStrava.placeholderBrokenImage);
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
window.addEventListener('resize', function () {
    resizeAllCharts();
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
    new Chat($modal).render();
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
        handleWeeklyStatsClick: (params, chartNode) => {
            if (!params || !params.dataIndex) {
                return;
            }

            const clickData = JSON.parse(chartNode.getAttribute('data-echarts-click-data'));
            const weeks = clickData.weeks;
            if (!params.dataIndex in weeks) {
                return;
            }
            dataTableStorage.set({
                'activities': {
                    "sportType": clickData.sportTypes,
                    "start-date": {"from": weeks[params.dataIndex]['from'], "to": weeks[params.dataIndex]['to']},
                }
            });

            router.navigateTo(`/activities`);
        },
        handleActivityGridChartClick: (params, chartNode) => {
            if (!params || !params.value || params.value < 1) {
                return;
            }

            dataTableStorage.set({
                'activities' : {
                    "start-date": {"from": params.value[0], "to": params.value[0]},
                }
            });

            router.navigateTo(`/activities`, false);
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
                chartClickHandlers[clickHandlerName](params, chartNode);
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

(async () => {
    await updateGithubLatestRelease();
})();