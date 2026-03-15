import {eventBus, Events} from "../core/event-bus";

export default class TabsManager {
    constructor() {
        this._syncGroups = {};
    }

    init(rootNode) {
        rootNode.querySelectorAll('[data-tabs]').forEach(($triggerEl) => {
            const tabItems = [];
            let defaultTabId = null;

            $triggerEl
                .querySelectorAll('[role="tab"]')
                .forEach(function ($triggerEl) {
                    const dataTabsTarget = $triggerEl.getAttribute('data-tabs-target');
                    tabItems.push({
                        id: dataTabsTarget,
                        triggerEl: $triggerEl,
                        targetEl: document.querySelector(dataTabsTarget),
                    });
                    if ($triggerEl.hasAttribute('data-tab-default')) {
                        defaultTabId = dataTabsTarget;
                    }
                });

            const syncGroupId = $triggerEl.getAttribute('data-tabs-sync-group');
            if (syncGroupId && !this._syncGroups[syncGroupId]) {
                this._syncGroups[syncGroupId] = {instances: [], activeIndex: 0, syncing: false};
            }

            const tabsInstance = new Tabs($triggerEl, tabItems, {
                defaultTabId: defaultTabId,
                activeClasses: 'active',
                inactiveClasses: 'inactive',
                onShow: (tabs, activeTab) => {
                    if (syncGroupId) {
                        const group = this._syncGroups[syncGroupId];
                        if (!group.syncing) {
                            const newIndex = tabItems.findIndex(item => item.id === activeTab.id);
                            if (newIndex !== -1 && newIndex !== group.activeIndex) {
                                group.activeIndex = newIndex;
                                group.syncing = true;
                                group.instances.forEach(({instance, items}) => {
                                    if (instance !== tabsInstance && items[newIndex]) {
                                        instance.show(items[newIndex].id);
                                    }
                                });
                                group.syncing = false;
                            }
                        }
                    }

                    const activeTabId = activeTab.id.replace('#', '');
                    // Trigger a chart resize to make sure charts are rendered and displayed.
                    eventBus.emit(Events.TAB_CHANGED, {activeTabId});
                },
            });

            if (syncGroupId) {
                this._syncGroups[syncGroupId].instances.push({instance: tabsInstance, items: tabItems});
            }
        });
    }
}
