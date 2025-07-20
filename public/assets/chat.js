export default function Chat($chatModal) {
    const $chatWrapper = $chatModal.querySelector('.chat--wrapper');
    const $form = $chatModal.querySelector('form');
    const $button = $form.querySelector('button.send-message');
    const $textInput = $form.querySelector('input.message');
    const $spinner = $form.querySelector('div.spinner');

    const placeholderIdle = $textInput.getAttribute('data-placeholder-idle');
    const placeholderProcessing = $textInput.getAttribute('data-placeholder-processing');

    const toggleElements = (disabled) => {
        $textInput.disabled = disabled;
        $button.disabled = disabled;

        $button.classList.toggle('inline-flex', !disabled);
        $button.classList.toggle('hidden', disabled);

        $form.classList.toggle('disabled', disabled);
        $spinner.classList.toggle('hidden', !disabled);

        $textInput.value = '';
        $textInput.placeholder = disabled ? placeholderProcessing : placeholderIdle;
    };

    const render = () => {
        $form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData($form);
            toggleElements(true);

            const source = new EventSource(`/chat/sse?message=${encodeURIComponent(formData.get('form[message]'))}`);

            source.addEventListener('fullMessage', (event) => {
                $chatWrapper.innerHTML += event.data.replace(/\\n/g, '\n');
            });

            source.addEventListener('agentResponse', (event) => {
                const $agentAnswerWrapper = $chatWrapper.querySelector('div.message-wrapper:last-child > div.message');

                const $thinkingAnimation = $agentAnswerWrapper.querySelector('.thinking');
                if ($thinkingAnimation) {
                    $thinkingAnimation.remove();
                }

                $agentAnswerWrapper.innerHTML += event.data.replace(/\\n/g, '\n');
            });

            source.addEventListener('done', function () {
                source.close();
                toggleElements(false);
            });
        });

        $textInput.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                $form.requestSubmit();
            }
        });
    };

    return {
        render
    };
}