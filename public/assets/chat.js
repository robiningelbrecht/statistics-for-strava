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

    const commands = {
        '/analyse-last-workout': 'You are my bike trainer. Please analyze my most recent ride with regard to aspects such as heart rate, power (if available). Please give me an assessment of my performance level and possible improvements for future training sessions.',
        '/compare-last-two-weeks': 'You are my bike trainer. Please compare my workouts and performance of the last 7 days with the 7 days before and give a short assessment.'
    }

    const initAutoComplete = () => {
        const autoCompleteJS = new autoComplete({
            selector: () => $textInput,
            data: {
                src: Object.keys(commands),
            },
            threshold: 1,
            trigger: (query) => {
                return query.startsWith('/');
            },
            resultsList:{
                tabSelect: true,
                position: 'beforebegin',
            },
            resultItem: {
                highlight: true,
                element: (item, data) => {
                    item.innerHTML = `<div>${data.match}</div><div class="text-xs text-gray-500">${commands[data.value]}</div>`;
                }
            },
            events: {
                input: {
                    focus: () => {
                        if (autoCompleteJS.input.value.length) autoCompleteJS.start();
                    }
                }
            }
        });

        autoCompleteJS.input.addEventListener("selection", function (event) {
            const feedback = event.detail;
            autoCompleteJS.input.value = commands[feedback.selection.value] || null;
        });
    };

    const render = () => {
        initAutoComplete();

        $form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData($form);
            toggleElements(true);

            const source = new EventSource(`/chat/sse?message=${encodeURIComponent(formData.get('form[message]'))}`);

            source.addEventListener('fullMessage', (event) => {
                $chatWrapper.innerHTML += event.data.replace(/\\n/g, '\n');
            });

            source.addEventListener('removeThinking', () => {
                const $thinkingAnimation = $chatWrapper.querySelector('.thinking');
                if ($thinkingAnimation) {
                    $thinkingAnimation.remove();
                }
            });

            source.addEventListener('agentResponse', (event) => {
                const $agentAnswerWrapper = $chatWrapper.querySelector('div.message-wrapper:last-child > div.message');
                $agentAnswerWrapper.innerHTML += event.data.replace(/\\n/g, '\n');
            });

            source.addEventListener('done', function () {
                source.close();
                toggleElements(false);
                $textInput.focus();
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