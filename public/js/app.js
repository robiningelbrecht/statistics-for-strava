import {Heatmap} from "./leaflet/heatmap";
import {DataTable, DataTableStorage} from "./data-table";
import Router from "./router";
import Chat from "./chat";
import {updateGithubLatestRelease} from "./github";
import Sidebar from "./ui/sidebar";
import ChartManager from "./ui/charts";
import ModalManager from "./ui/modals";
import MapManager from "./ui/maps";
import TabsManager from "./ui/tabs";
import LightGalleryManager from "./ui/lightgallery";
import LazyLoad from "../libraries/lazyload.min";

const $main = document.querySelector("main");
const dataTableStorage = new DataTableStorage();

// Boot router.
const router = new Router($main);
router.boot();

const sidebar = new Sidebar($main);
const chartManager = new ChartManager(router, dataTableStorage);
const modalManager = new ModalManager(router);
const mapManager = new MapManager();
const tabsManager = new TabsManager(chartManager);
const lightGalleryManager = new LightGalleryManager();
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
    lightGalleryManager.init(rootNode);
}

modalManager.setInitElements(initElements)
sidebar.init();

document.addEventListener('pageWasLoaded', (e) => {
    modalManager.close();

    document.querySelectorAll('div[data-dataTable-settings]').forEach(function ($dataTableWrapperNode) {
        new DataTable($dataTableWrapperNode).render()
    });

    chartManager.reset();
    initElements(document);

    if (e.detail && e.detail.modalId) {
        // Open modal.
        modalManager.open(e.detail.modalId);
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