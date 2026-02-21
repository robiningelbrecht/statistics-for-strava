export const debounce = (func, timeout = 300) => {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            func.apply(this, args);
        }, timeout);
    };
}
export const numberFormat = (number, decimals, decPoint, thousandsSep) => {
    number = (number + '').replace(/[^0-9+\-Ee.]/g, '')
    const n = !isFinite(+number) ? 0 : +number
    const prec = !isFinite(+decimals) ? 0 : Math.abs(decimals)
    const sep = typeof thousandsSep === 'undefined' ? ',' : thousandsSep
    const dec = typeof decPoint === 'undefined' ? '.' : decPoint
    let s = ''

    const toFixedFix = function (n, prec) {
        if (('' + n).indexOf('e') === -1) {
            return +(Math.round(n + 'e+' + prec) + 'e-' + prec)
        } else {
            const arr = ('' + n).split('e')
            let sig = ''
            if (+arr[1] + prec > 0) {
                sig = '+'
            }
            return (+(Math.round(+arr[0] + 'e' + sig + (+arr[1] + prec)) + 'e-' + prec)).toFixed(prec)
        }
    }

    // @todo: for IE parseFloat(0.55).toFixed(0) = 0;
    s = (prec ? toFixedFix(n, prec).toString() : '' + Math.round(n)).split('.')
    if (s[0].length > 3) {
        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep)
    }
    if ((s[1] || '').length < prec) {
        s[1] = s[1] || ''
        s[1] += new Array(prec - s[1].length + 1).join('0')
    }

    return s.join(dec)
}

const CALLBACK_PREFIX = 'callback:';

export const resolveEchartsCallbacks = (obj) => {
    if (!obj || typeof obj !== 'object') return;

    for (const key of Object.keys(obj)) {
        const value = obj[key];
        if (typeof value === 'string' && value.startsWith(CALLBACK_PREFIX)) {
            const callbackName = value.slice(CALLBACK_PREFIX.length);
            if (callbackName in window.statisticsForStrava.callbacks) {
                obj[key] = window.statisticsForStrava.callbacks[callbackName];
            }
        } else if (typeof value === 'object' && value !== null) {
            resolveEchartsCallbacks(value);
        }
    }
};

export const fetchJson = async (url) => {
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error(`Failed to fetch ${url}: ${response.status}`);
    }

    return response.json();
}

export const parents = (el, selector) => {
    const matched = [];
    let parent = el.parentElement;

    while (parent) {
        if (!selector || parent.matches(selector)) {
            matched.push(parent);
        }
        parent = parent.parentElement;
    }

    return matched;
}

