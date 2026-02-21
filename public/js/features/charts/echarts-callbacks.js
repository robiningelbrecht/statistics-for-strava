const CALLBACK_PREFIX = 'callback:';

export const resolveEchartsCallbacks = (obj) => {
    if (!obj || typeof obj !== 'object') return;

    for (const key of Object.keys(obj)) {
        const value = obj[key];
        if (typeof value === 'string' && value.startsWith(CALLBACK_PREFIX)) {
            const callbackName = value.slice(CALLBACK_PREFIX.length);
            if (callbackName in window.statisticsForStrava.callbacks) {
                obj[key] = window.statisticsForStrava.callbacks[callbackName];
            } else {
                console.error(`ECharts callback "${callbackName}" not found. Did you register it in window.statisticsForStrava.callbacks?`);
            }
        } else if (typeof value === 'object' && value !== null) {
            resolveEchartsCallbacks(value);
        }
    }
};

const formatSeconds = (secondsToFormat) => {
    const hours = String(Math.floor(secondsToFormat / 3600)).padStart(2, '0');
    const minutes = String(Math.floor((secondsToFormat % 3600) / 60)).padStart(2, '0');
    const seconds = String(secondsToFormat % 60).padStart(2, '0');

    return `${hours}:${minutes}:${seconds}`;
};

export const registerEchartsCallbacks = () => {
    window.statisticsForStrava.callbacks = {
        formatSeconds,
        formatSecondsTrimZero: (secondsToFormat) => {
            const time = formatSeconds(secondsToFormat);

            let [hours, minutes, seconds] = time.split(':');
            return String(Number(hours)) === "0" ? `${minutes}:${seconds}` : `${hours}:${minutes}:${seconds}`;
        },
        formatPace: (params) => {
            const paceSymbol = window.statisticsForStrava.unitSystem.paceSymbol;
            const secondsToFormat = params[0].value;
            if (secondsToFormat < 60) {
                return `<strong>${secondsToFormat}s</strong>${paceSymbol}`;
            }
            const minutes = Math.floor(secondsToFormat / 60);
            const secs = secondsToFormat % 60;
            return `<strong>${minutes}:${secs.toString().padStart(2, '0')}</strong>${paceSymbol}`;
        },
        formatCombinedProfileTooltip: (params) => {
            if (!Array.isArray(params)) params = [params];
            return [...params].sort((a, b) => a.seriesIndex - b.seriesIndex).map(p => {
                if (p.seriesName === '__pace') {
                    return `${p.marker} ${window.statisticsForStrava.callbacks.formatPace([p])}`;
                }

                const extra = p.data?.extra !== undefined ? ` (${p.data.extra})` : '';
                return `${p.marker} <strong>${p.value}</strong> ${p.seriesName}${extra}`;
            }).join('<br/>');
        },
        symbolSize: (params) => {
            return (params[2] / 100) * 15 + 5;
        },
        toInteger: (value) => {
            return value.toFixed(0);
        },
        formatPercentage: (value) => {
            return `${value.toFixed(0)}%`;
        },
        formatHours: (value) => {
            if (value === undefined || value === null) {
                return '-';
            }
            return `${value.toFixed(0)}h`;
        },
        formatDistance: (value) => {
            if (value === undefined || value === null) {
                return '-';
            }
            const distanceSymbol = window.statisticsForStrava.unitSystem.distanceSymbol;
            return `${value.toFixed(0)}${distanceSymbol}`;
        },
        formatElevation: (value) => {
            if (value === undefined || value === null) {
                return '-';
            }
            const elevationSymbol = window.statisticsForStrava.unitSystem.elevationSymbol;
            return `${value.toFixed(0)}${elevationSymbol}`;
        },
        formatActivityGridTooltip: (value) => {
            if (value === undefined || value === null) {
                return '-';
            }

            const dateFormat = window.statisticsForStrava.unitSystem.name === 'metric' ? '{dd}-{MM}-{yyyy}' : '{MM}-{dd}-{yyyy}';
            const date = echarts.time.format(value.data[0], dateFormat, false);
            if ('movingTime' === value.seriesName) {
                const secondsToFormat = value.data[1] * 60;
                const hours = String(Math.floor(secondsToFormat / 3600)).padStart(2, '0');
                const minutes = String(Math.floor((secondsToFormat % 3600) / 60)).padStart(2, '0');
                return date + ': ' + `${hours}h${minutes}`;
            }
            return date + ': ' + value.data[1];
        },
    };
};
