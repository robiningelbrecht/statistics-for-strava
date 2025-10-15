export default class TabsManager {
    constructor(chartManager) {
        this.chartManager = chartManager;
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

            new Tabs($triggerEl, tabItems, {
                defaultTabId: defaultTabId,
                activeClasses: 'text-strava-orange border-strava-orange hover:text-gray-600 hover:border-gray-300',
                inactiveClasses: 'text-gray-500 hover:text-gray-600 border-gray-100 hover:border-gray-300',
                onShow: (tabs, activeTab) => {
                    const activeTabId = activeTab.id.replace('#', '');
                    // Trigger a chart resize to make sure charts are rendered and displayed.
                    this.chartManager.resizeInTab(activeTabId)
                },
            });
        });
    }
}
