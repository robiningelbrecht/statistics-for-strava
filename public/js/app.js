import {eventBus, Events} from "./core/event-bus";
import {FilterStorage} from "./features/data-table/storage";
import Router from "./core/router";
import {updateGithubLatestRelease} from "./services/github";
import Sidebar from "./components/sidebar";
import ChartManager from "./features/charts/chart-manager";
import ModalManager from "./components/modals";
import PhotoWall from "./features/photos/photo-wall";
import MapManager from "./features/maps/map-manager";
import TabsManager from "./components/tabs";
import LazyLoad from "../libraries/lazyload.min";
import DataTableManager from "./features/data-table/data-table-manager";
import FullscreenManager from "./components/fullscreen";
import Heatmap from "./features/heatmap/heatmap";
import DarkModeManager from "./components/dark-mode";

const $main = document.querySelector("main");

// Boot router.
const router = new Router($main);
router.boot();

const sidebar = new Sidebar($main);
const modalManager = new ModalManager(router);
const chartManager = new ChartManager(router, modalManager);
const mapManager = new MapManager();
const tabsManager = new TabsManager();
const dataTableManager = new DataTableManager();
const fullscreenManager = new FullscreenManager();
const darkModeManager = new DarkModeManager();
const lazyLoad = new LazyLoad({
    thresholds: "50px",
    callback_error: (img) => {
        img.setAttribute("src", window.statisticsForStrava.placeholderBrokenImage);
    }
});

const initElements = (rootNode) => {
    lazyLoad.update();

    tabsManager.init(rootNode);
    initPopovers();
    initTooltips();
    initDropdowns();
    initAccordions();

    modalManager.init(rootNode);
    dataTableManager.init(rootNode);
    chartManager.init(rootNode, darkModeManager.isDarkModeEnabled());
    mapManager.init(rootNode);
    fullscreenManager.init(rootNode);
}

sidebar.init();
darkModeManager.attachEventListeners();

eventBus.on(Events.DARK_MODE_TOGGLED, ({darkModeEnabled}) => {
    chartManager.toggleDarkTheme(darkModeEnabled);
});
eventBus.on(Events.FULLSCREEN_ENABLED, () => {
    chartManager.resizeAll();
});
eventBus.on(Events.TAB_CHANGED, ({activeTabId}) => {
    chartManager.resizeInTab(activeTabId);
});

eventBus.on(Events.PAGE_LOADED, async ({page, modalId}) => {
    modalManager.close();

    chartManager.reset();
    initElements(document);

    if (modalId) {
        modalManager.open(modalId);
    }

    if (page === 'heatmap') {
        const $heatmapWrapper = document.querySelector('.heatmap-wrapper');
        await new Heatmap($heatmapWrapper, modalManager).render();
    }
    if (page === 'photos') {
        const $photoWallWrapper = document.querySelector('.photo-wall-wrapper');
        await new PhotoWall($photoWallWrapper).render();
    }
});
eventBus.on(Events.NAVIGATION_CLICKED, ({link}) => {
    if (!link || !link.hasAttribute('data-filters')) {
        return;
    }
    const filters = JSON.parse(link.getAttribute('data-filters'));
    Object.entries(filters).forEach(([tableName, tableFilters]) => {
        FilterStorage.set(tableName, tableFilters);
    });
});
eventBus.on(Events.MODAL_LOADED, async ({node, modalName}) => {
    initElements(node);

    if (modalName === 'ai-chat') {
        const {default: Chat} = await import(
            /* webpackChunkName: "chat" */ './features/chat/chat'
            );
        new Chat(node).render();
    }
});
eventBus.on(Events.DATA_TABLE_CLUSTER_CHANGED, ({node}) => {
    modalManager.init(node);
});
window.addEventListener('resize', function () {
    chartManager.resizeAll();
});
eventBus.on(Events.SIDEBAR_RESIZED, () => {
    chartManager.resizeAll();
});

const $modalAIChat = document.querySelector('a[data-modal-custom-ai]');
if ($modalAIChat) {
    $modalAIChat.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const modalId = $modalAIChat.getAttribute('data-modal-custom-ai');
        modalManager.open(modalId);
        router.pushCurrentRouteToHistoryState(modalId);
    });
}

(async () => {
    await updateGithubLatestRelease();
})();
