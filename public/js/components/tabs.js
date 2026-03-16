import {eventBus, Events} from "../core/event-bus";

let _isSyncing = false;

export default class TabsManager {
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

            let tabsInstance;
            tabsInstance = new Tabs($triggerEl, tabItems, {
                defaultTabId: defaultTabId,
                activeClasses: 'active',
                inactiveClasses: 'inactive',
                onShow: (tabs, activeTab) => {
                    const activeTabId = activeTab.id.replace('#', '');
                    // Trigger a chart resize to make sure charts are rendered and displayed.
                    eventBus.emit(Events.TAB_CHANGED, {activeTabId});

                    if (syncGroupId && !_isSyncing && tabsInstance) {
                        const activeIndex = tabItems.findIndex(item => item.id === activeTab.id);
                        eventBus.emit(Events.TAB_SYNCED, {syncGroupId, activeIndex, source: tabsInstance});
                    }
                },
            });

            if (syncGroupId) {
                eventBus.on(Events.TAB_SYNCED, ({syncGroupId: groupId, activeIndex, source}) => {
                    if (groupId === syncGroupId && source !== tabsInstance && tabItems[activeIndex]) {
                        _isSyncing = true;
                        tabsInstance.show(tabItems[activeIndex].id);
                        _isSyncing = false;
                    }
                });
            }
        });
    }
}
