import {DataTableStorage} from "./data-table/storage";
import Router from "./router";
import {updateGithubLatestRelease} from "./services/github";
import Sidebar from "./components/sidebar";
import ChartManager from "./components/charts";
import ModalManager from "./components/modals";
import PhotoWall from "./components/photo-wall";
import MapManager from "./components/maps";
import TabsManager from "./components/tabs";
import LazyLoad from "../libraries/lazyload.min";
import DataTableManager from "./components/data-tables";
import FullscreenManager from "./components/fullscreen";
import Heatmap from "./components/heatmap";
import DarkModeManager from "./components/dark-mode";

const $main = document.querySelector("main");
const dataTableStorage = new DataTableStorage();

// Boot router.
const router = new Router($main);
router.boot();

const sidebar = new Sidebar($main);
const modalManager = new ModalManager(router);
const chartManager = new ChartManager(router, dataTableStorage, modalManager);
const mapManager = new MapManager();
const tabsManager = new TabsManager();
const dataTableManager = new DataTableManager(dataTableStorage);
const fullscreenManager = new FullscreenManager(chartManager);
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
    chartManager.init(rootNode, darkModeManager.isDarkModeEnabled());
    mapManager.init(rootNode);
    fullscreenManager.init(rootNode);
}

modalManager.setInitElements(initElements)
sidebar.init();
darkModeManager.attachEventListeners();

document.addEventListener('darkModeWasToggled', (e) => {
    chartManager.toggleDarkTheme(e.detail.darkModeEnabled);
});
document.addEventListener('fullScreenModeWasEnabled', () => {
    chartManager.resizeAll();
});
document.addEventListener('tabChangeWasTriggered', (e) => {
    chartManager.resizeInTab(e.detail.activeTabId)
});

document.addEventListener('pageWasLoaded', (e) => {
    modalManager.close();
    dataTableManager.init();

    chartManager.reset();
    initElements(document);

    if (e.detail && e.detail.modalId) {
        // Open modal.
        modalManager.open(e.detail.modalId);
    }
});
document.addEventListener('pageWasLoaded.heatmap', async () => {
    const $heatmapWrapper = document.querySelector('.heatmap-wrapper');
    await new Heatmap($heatmapWrapper, modalManager).render();
});
document.addEventListener('pageWasLoaded.photos', () => {
    const $photoWallWrapper = document.querySelector('.photo-wall-wrapper');
    new PhotoWall($photoWallWrapper, dataTableStorage).render();
});
document.addEventListener('navigationLinkHasBeenClicked', (e) => {
    if (!e.detail || !e.detail.link) {
        return;
    }
    if (!e.detail.link.hasAttribute('data-dataTable-filters')) {
        return;
    }
    const filters = JSON.parse(e.detail.link.getAttribute('data-dataTable-filters'));
    Object.entries(filters).forEach(([tableName, tableFilters]) => {
        dataTableStorage.set(tableName, tableFilters);
    });
});
document.addEventListener('dataTableClusterWasChanged', () => {
    modalManager.init(document);
});
window.addEventListener('resize', function () {
    chartManager.resizeAll();
});
document.addEventListener('sidebarWasResized', function () {
    chartManager.resizeAll();
});

const $modalAIChat = document.querySelector('a[data-modal-custom-ai]');
if ($modalAIChat) {
    $modalAIChat.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const modalId = $modalAIChat.getAttribute('data-modal-custom-ai');
        modalManager.open(modalId);
        // Add modal to history state.
        router.pushCurrentRouteToHistoryState(modalId);
    });
}

document.addEventListener('modalWasLoaded.ai-chat', async (e) => {
    const {default: Chat} = await import(
        /* webpackChunkName: "chat" */ './components/chat'
        );
    const $modal = e.detail.modal;
    new Chat($modal).render();
});

(async () => {
    await updateGithubLatestRelease();
})();
