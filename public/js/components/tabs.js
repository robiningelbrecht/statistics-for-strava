import {eventBus, Events} from "../core/event-bus";

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

            new Tabs($triggerEl, tabItems, {
                defaultTabId: defaultTabId,
                activeClasses: 'active',
                inactiveClasses: 'inactive',
                onShow: (tabs, activeTab) => {
                    const activeTabId = activeTab.id.replace('#', '');
                    // Trigger a chart resize to make sure charts are rendered and displayed.
                    eventBus.emit(Events.TAB_CHANGED, {activeTabId});
                },
            });
        });
    }
}
