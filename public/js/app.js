import {Heatmap} from "./ui/heatmap";
import {DataTableStorage} from "./filters";
import Router from "./router";
import Chat from "./ui/chat";
import {updateGithubLatestRelease} from "./github";
import Sidebar from "./ui/sidebar";
import ChartManager from "./ui/charts";
import ModalManager from "./ui/modals";
import {PhotoWall} from "./ui/photo-wall";
import MapManager from "./ui/maps";
import TabsManager from "./ui/tabs";
import LazyLoad from "../libraries/lazyload.min";
import DataTableManager from "./ui/data-tables";

const $main = document.querySelector("main");
const dataTableStorage = new DataTableStorage();

// Boot router.
const router = new Router($main);
router.boot();

const sidebar = new Sidebar($main);
const modalManager = new ModalManager(router);
const chartManager = new ChartManager(router, dataTableStorage, modalManager);
const mapManager = new MapManager();
const tabsManager = new TabsManager(chartManager);
const dataTableManager = new DataTableManager(dataTableStorage);
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
    chartManager.init(rootNode);
    mapManager.init(rootNode);
}

modalManager.setInitElements(initElements)
sidebar.init();

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
document.addEventListener('pageWasLoaded.heatmap', () => {
    const $heatmapWrapper = document.querySelector('.heatmap-wrapper');
    new Heatmap($heatmapWrapper, modalManager).render();
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
    dataTableStorage.set(JSON.parse(e.detail.link.getAttribute('data-dataTable-filters')));
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

document.addEventListener('modalWasLoaded.ai-chat', (e) => {
    const $modal = e.detail.modal;
    new Chat($modal).render();
});

(async () => {
    await updateGithubLatestRelease();
})();