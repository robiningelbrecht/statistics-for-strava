import {resolveEchartsCallbacks} from "../utils";

export default class ChartManager {
    constructor(router, dataTableStorage) {
        this.router = router;
        this.dataTableStorage = dataTableStorage;
        this.allCharts = [];
        this.chartsPerTab = [];
    }

    init(rootNode) {
        const handlers = this.getClickHandlers();
        const connectedCharts = [];
        rootNode.querySelectorAll('[data-echarts-options]').forEach(chartNode => {
            const chart = echarts.init(chartNode);
            const chartOptions = JSON.parse(chartNode.getAttribute('data-echarts-options'));
            [
                'tooltip.formatter',
                'tooltip.valueFormatter',
                'yAxis.axisLabel.formatter',
                'yAxis[].axisLabel.formatter',
                'series.symbolSize'
            ].forEach(path => resolveEchartsCallbacks(chartOptions, path));

            chart.setOption(chartOptions);

            const clickHandlerName = chartNode.getAttribute('data-echarts-click');
            if (clickHandlerName && handlers[clickHandlerName]) {
                chart.on('click', function (params) {
                    handlers[clickHandlerName](params, chartNode);
                });
            }
            if (chartNode.hasAttribute('data-echarts-connect')) {
                connectedCharts.push(chart);
            }

            this.allCharts.push(chart);

            const $tabPanel = chartNode.closest('div[role="tabpanel"]');
            if ($tabPanel) {
                const tabPanelId = $tabPanel.getAttribute('id');
                this.chartsPerTab[tabPanelId] = this.chartsPerTab[tabPanelId] || [];
                this.chartsPerTab[tabPanelId].push(chart);
            }
        });
        echarts.connect(connectedCharts);
    }

    getClickHandlers() {
        return {
            handleWeeklyStatsClick: (params, chartNode) => {
                if (!params || !params.dataIndex) {
                    return;
                }

                const clickData = JSON.parse(chartNode.getAttribute('data-echarts-click-data'));
                const weeks = clickData.weeks;
                if (!params.dataIndex in weeks) {
                    return;
                }
                this.dataTableStorage.set({
                    'activities': {
                        "sportType": clickData.sportTypes,
                        "start-date": {"from": weeks[params.dataIndex]['from'], "to": weeks[params.dataIndex]['to']},
                    }
                });

                this.router.navigateTo(`/activities`);
            },
            handleActivityGridChartClick: (params, chartNode) => {
                if (!params || !params.value || params.value < 1) {
                    return;
                }

                this.dataTableStorage.set({
                    'activities': {
                        "start-date": {"from": params.value[0], "to": params.value[0]},
                    }
                });

                this.router.navigateTo(`/activities`);
            },
        };
    }

    reset(){
        this.allCharts = [];
        this.chartsPerTab = [];
    }

    resizeAll() {
        this.allCharts.forEach(chart => chart.resize());
    }

    resizeInTab(tabId) {
        if (tabId in this.chartsPerTab) {
            this.chartsPerTab[tabId].forEach((chart) => chart.resize());
        }
    }
}