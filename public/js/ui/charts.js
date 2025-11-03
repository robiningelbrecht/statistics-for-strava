import {parents, resolveEchartsCallbacks} from "../utils";

export default class ChartManager {
    constructor(router, dataTableStorage, modalManager) {
        this.router = router;
        this.dataTableStorage = dataTableStorage;
        this.modalManager = modalManager;
        this.allCharts = [];
        this.chartsPerTab = [];

        echarts.registerTheme('v5', v5Theme());
    }

    init(rootNode) {
        const handlers = this.getClickHandlers();
        const connectedCharts = [];
        rootNode.querySelectorAll('[data-echarts-options]').forEach(chartNode => {
            const chart = echarts.init(chartNode, 'v5');
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
                this.dataTableStorage.set({
                    'activities': {
                        "sportType": clickData.sportTypes,
                        "start-date": {"from": weeks[params.dataIndex]['from'], "to": weeks[params.dataIndex]['to']},
                    }
                });

                this.router.navigateTo(`/activities`);
            },
            handleActivityGridChartClick: (params, clickData) => {
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

    reset() {
        this.allCharts = [];
        this.chartsPerTab = [];
    }

    resizeAll() {
        this.allCharts
            .filter(chart => chart.getDom().offsetParent)
            .forEach(chart => chart.resize());
    }

    resizeInTab(tabId) {
        if (tabId in this.chartsPerTab) {
            this.chartsPerTab[tabId]
                .filter(chart => chart.getDom().offsetParent)
                .forEach((chart) => chart.resize());
        }
    }
}

const v5Theme = () => {
    const gradientColor = ['#f6efa6', '#d88273', '#bf444c'];
    const axisCommon = function () {
        return {
            axisLine: {
                lineStyle: {
                    color: '#6E7079'
                }
            },
            axisLabel: {
                color: null
            },
            splitLine: {
                lineStyle: {
                    color: ['#E0E6F1']
                }
            },
            splitArea: {
                areaStyle: {
                    color: ['rgba(250,250,250,0.2)', 'rgba(210,219,238,0.2)']
                }
            },
            minorSplitLine: {
                color: '#F4F7FD'
            }
        };
    };

    return {
        color: [
            '#5470c6',
            '#91cc75',
            '#fac858',
            '#ee6666',
            '#73c0de',
            '#3ba272',
            '#fc8452',
            '#9a60b4',
            '#ea7ccc'
        ],
        gradientColor: gradientColor,
        loading: {
            textColor: 'red'
        },
        bar: {
            defaultBarGap: '20%',
            select: {
                itemStyle: {
                    borderColor: '#212121',
                    borderWidth: 1
                }
            }
        },
        boxplot: {
            emphasis: {
                itemStyle: {
                    shadowColor: 'rgba(0,0,0,0.2)'
                }
            }
        },
        graph: {
            lineStyle: {
                color: '#aaa'
            },
            select: {
                itemStyle: {
                    borderColor: '#212121'
                }
            }
        },
        heatmap: {
            select: {
                itemStyle: {
                    borderColor: '#212121'
                }
            }
        },
        line: {
            symbolSize: 4
        },
        pictorialBar: {
            select: {
                itemStyle: {
                    borderColor: '#212121',
                    borderWidth: 1
                }
            }
        },
        pie: {
            radius: [0, '75%'],
            labelLine: {
                length2: 15
            }
        },
        map: {
            defaultItemStyleColor: '#eee',
            label: {
                color: '#000'
            },
            itemStyle: {
                borderColor: '#444',
                areaColor: '#eee'
            },
            emphasis: {
                label: {
                    color: 'rgb(100,0,0)'
                },
                itemStyle: {
                    areaColor: 'rgba(255,215,0,0.8)'
                }
            },
            select: {
                label: {
                    color: 'rgb(100,0,0)'
                },
                itemStyle: {
                    color: 'rgba(255,215,0,0.8)'
                }
            },
        },
        timeAxis: axisCommon(),
        logAxis: axisCommon(),
        valueAxis: axisCommon(),
        categoryAxis: (() => {
            const axis = axisCommon();
            axis.axisTick = {
                show: true
            };
            return axis;
        })(),
        axisPointer: {
            lineStyle: {
                color: '#B9BEC9'
            },
            shadowStyle: {
                color: 'rgba(210,219,238,0.2)'
            },
            label: {
                backgroundColor: 'auto',
                color: '#fff'
            },
            handle: {
                color: '#333',
                shadowBlur: 3,
                shadowColor: '#aaa',
                shadowOffsetX: 0,
                shadowOffsetY: 2,
            }
        },
        brush: {
            brushStyle: {
                color: 'rgba(210,219,238,0.3)',
                borderColor: '#D2DBEE'
            },
            defaultOutOfBrushColor: '#ddd'
        },
        calendar: {
            splitLine: {
                lineStyle: {
                    color: '#000'
                }
            },
            itemStyle: {
                borderColor: '#ccc'
            },
            dayLabel: {
                margin: '50%',
                color: '#000'
            },
            monthLabel: {
                margin: 5,
                color: '#000'
            },
            yearLabel: {
                margin: 30,
                color: '#ccc'
            }
        },
        dataZoom: {
            borderColor: '#d2dbee',
            borderRadius: 3,
            backgroundColor: 'rgba(47,69,84,0)',
            dataBackground: {
                lineStyle: {
                    color: '#d2dbee',
                    width: 0.5
                },
                areaStyle: {
                    color: '#d2dbee',
                    opacity: 0.2
                }
            },
            selectedDataBackground: {
                lineStyle: {
                    color: '#8fb0f7',
                    width: 0.5
                },
                areaStyle: {
                    color: '#8fb0f7',
                    opacity: 0.2
                }
            },
            handleStyle: {
                color: '#fff',
                borderColor: '#ACB8D1'
            },
            moveHandleStyle: {
                color: '#D2DBEE',
                opacity: 0.7
            },
            textStyle: {
                color: '#6E7079'
            },
            brushStyle: {
                color: 'rgba(135,175,274,0.15)'
            },
            emphasis: {
                handleStyle: {
                    borderColor: '#8FB0F7'
                },
                moveHandleStyle: {
                    color: '#8FB0F7',
                    opacity: 0.7
                }
            },
            defaultLocationEdgeGap: 7
        },
        geo: {
            defaultItemStyleColor: '#eee',
            label: {
                color: '#000'
            },
            itemStyle: {
                borderColor: '#444'
            },
            emphasis: {
                label: {
                    color: 'rgb(100,0,0)'
                },
                itemStyle: {
                    color: 'rgba(255,215,0,0.8)'
                }
            },
            select: {
                label: {
                    color: 'rgb(100,0,0)'
                },
                itemStyle: {
                    color: 'rgba(255,215,0,0.8)'
                }
            }
        },
        grid: {
            left: '10%',
            top: 60,
            bottom: 70,
            borderColor: '#ccc'
        },
        legend: {
            top: 0,
            bottom: null,
            backgroundColor: 'rgba(0,0,0,0)',
            borderColor: '#ccc',
            itemGap: 10,
            inactiveColor: '#ccc',
            inactiveBorderColor: '#ccc',
            lineStyle: {
                inactiveColor: '#ccc',
            },
            textStyle: {
                color: '#333'
            },
            selectorLabel: {
                color: '#666',
                borderColor: '#666'
            },
            emphasis: {
                selectorLabel: {
                    color: '#eee',
                    backgroundColor: '#666'
                }
            },
            pageIconColor: '#2f4554',
            pageIconInactiveColor: '#aaa',
            pageTextStyle: {
                color: '#333'
            }
        },
        title: {
            left: 0,
            top: 0,
            backgroundColor: 'rgba(0,0,0,0)',
            borderColor: '#ccc',
            textStyle: {
                color: '#464646'
            },
            subtextStyle: {
                color: '#6E7079'
            }
        },
        toolbox: {
            borderColor: '#ccc',
            padding: 5,
            itemGap: 8,
            iconStyle: {
                borderColor: '#666',
            },
            emphasis: {
                iconStyle: {
                    borderColor: '#3E98C5'
                }
            }
        },
        tooltip: {
            axisPointer: {
                crossStyle: {
                    color: '#999'
                }
            },
            textStyle: {
                color: '#666'
            },
            backgroundColor: '#fff',
            borderWidth: 1,
            defaultBorderColor: '#fff'
        },
        visualMap: {
            color: [gradientColor[2], gradientColor[1], gradientColor[0]],
            inactive: ['rgba(0,0,0,0)'],
            indicatorStyle: {
                shadowColor: 'rgba(0,0,0,0.2)'
            },
            backgroundColor: 'rgba(0,0,0,0)',
            borderColor: '#ccc',
            contentColor: '#5793f3',
            inactiveColor: '#aaa',
            padding: 5,
            textStyle: {
                color: '#333'
            }
        }
    };
}