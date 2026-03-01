export default class ScrollTo {
    init(rootNode) {
        rootNode.addEventListener('click', (e) => {
            const trigger = e.target.closest('[data-scroll-to]');
            if (!trigger) return;

            const target = document.getElementById(trigger.dataset.scrollTo);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        });
    }
}
