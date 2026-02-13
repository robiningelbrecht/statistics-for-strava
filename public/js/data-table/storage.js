export class DataTableStorage {
    constructor(storageKey = 'dataTableFilters') {
        this.storageKey = storageKey;
    }

    clearAll(tableName) {
        this.set(tableName, {});
    }

    get(tableName) {
        const storedJson = localStorage.getItem(this.storageKey);
        if (!storedJson) return {};
        const parsed = JSON.parse(storedJson);
        return parsed[tableName] || {};
    }

    set(tableName, object) {
        const storedJson = localStorage.getItem(this.storageKey);
        const existing = storedJson ? JSON.parse(storedJson) : {};
        existing[tableName] = object;
        localStorage.setItem(this.storageKey, JSON.stringify(existing));
    }
}
