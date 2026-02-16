export class FilterStorage {
    static storageKey = 'filters';

    static clearAll(tableName) {
        FilterStorage.set(tableName, {});
    }

    static get(tableName) {
        const storedJson = localStorage.getItem(FilterStorage.storageKey);
        if (!storedJson) return {};
        const parsed = JSON.parse(storedJson);
        return parsed[tableName] || {};
    }

    static set(tableName, object) {
        const storedJson = localStorage.getItem(FilterStorage.storageKey);
        const existing = storedJson ? JSON.parse(storedJson) : {};
        existing[tableName] = object;
        localStorage.setItem(FilterStorage.storageKey, JSON.stringify(existing));
    }
}
