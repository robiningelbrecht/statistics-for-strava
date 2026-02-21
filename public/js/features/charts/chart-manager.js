import {parents, fetchJson} from "../../utils";
import {resolveEchartsCallbacks} from "./echarts-callbacks";
import {v5Theme, v5DarkTheme} from "./echarts-themes";
import {FilterStorage, FilterName} from "../data-table/storage";

export default class ChartManager {
    constructor(router, modalManager) {
        this.router = router;
        this.modalManager = modalManager;
        this.allCharts = [];
        this.chartsPerTab = [];

        echarts.registerTheme('v5', v5Theme());
        echarts.registerTheme('v5-dark', v5DarkTheme());
    }

    init(rootNode, isDarkMode) {
        const handlers = this.getClickHandlers();
        const connectedCharts = [];
        rootNode.querySelectorAll('[data-echarts-options]').forEach(chartNode => {
            const chart = echarts.init(chartNode, isDarkMode ? 'v5-dark' : 'v5');
            const rawChartOptions = chartNode.getAttribute('data-echarts-options');

            const loadOptions = rawChartOptions.toLowerCase().endsWith('.json')
                ? fetchJson(rawChartOptions)
                : Promise.resolve(JSON.parse(rawChartOptions));
            chart.showLoading();

            loadOptions.then(chartOptions => {
                resolveEchartsCallbacks(chartOptions);
                chart.setOption(chartOptions);
            }).catch(error => {
                console.error('Failed to load chart data:', error);
            }).finally(() => {
                chart.hideLoading();
            });

            const clickHandlerName = chartNode.getAttribute('data-echarts-click');
            if (clickHandlerName && handlers[clickHandlerName]) {
                chart.on('click', function (params) {
                    const clickData = JSON.parse(chartNode.getAttribute('data-echarts-click-data') || '{}');
                    handlers[clickHandlerName](params, clickData);
                });
            }
            if (chartNode.hasAttribute('data-echarts-connect')) {
                connectedCharts.push(chart);
            }

            this.allCharts.push(chart);

            const tabPanels = parents(chartNode, 'div[role="tabpanel"]');
            for (const tabPanel of tabPanels) {
                const tabPanelId = tabPanel.getAttribute('id');
                this.chartsPerTab[tabPanelId] ??= [];
                this.chartsPerTab[tabPanelId].push(chart);
            }

        });
        echarts.connect(connectedCharts);
    }

    getClickHandlers() {
        return {
            handleMonthlyStatsClick: (params, clickData) => {
                if (!params || !params.dataIndex || !params.seriesName) {
                    return;
                }
                const month = (params.dataIndex + 1).toString().padStart(2, "0");
                const modalId = `month/month-${params.seriesName}-${month}.html`;

                this.modalManager.open(modalId);
                this.router.pushCurrentRouteToHistoryState(modalId);
            },
            handleWeeklyStatsClick: (params, clickData) => {
                if (!params || !params.dataIndex) {
                    return;
                }

                const weeks = clickData.weeks;
                if (!params.dataIndex in weeks) {
                    return;
                }
                FilterStorage.set(FilterName.ACTIVITIES, {
                    "sportType": clickData.sportTypes,
                    "start-date": {"from": weeks[params.dataIndex]['from'], "to": weeks[params.dataIndex]['to']},
                });

                this.router.navigateTo(`/activities`);
            },
            handleActivityGridChartClick: (params, clickData) => {
                if (!params || !params.value || params.value < 1) {
                    return;
                }

                FilterStorage.set(FilterName.ACTIVITIES, {
                    "start-date": {"from": params.value[0], "to": params.value[0]},
                });

                this.router.navigateTo(`/activities`);
            },
        };
    }

    reset() {
        this.allCharts = [];
        this.chartsPerTab = [];
    }

    resizeAll() {
        this.allCharts
            .filter(chart => chart.getDom().offsetParent)
            .forEach(chart => chart.resize());
    }

    toggleDarkTheme(isDarkMode) {
        this.allCharts
            .filter(chart => chart.getDom().offsetParent)
            .forEach(chart => chart.setTheme(isDarkMode ? 'v5-dark' : 'v5'));
    }

    resizeInTab(tabId) {
        if (tabId in this.chartsPerTab) {
            this.chartsPerTab[tabId]
                .filter(chart => chart.getDom().offsetParent)
                .forEach((chart) => chart.resize());
        }
    }
}
