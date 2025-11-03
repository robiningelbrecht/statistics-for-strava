import autoComplete from "../../libraries/autocomplete";

export default class Chat {
    constructor(chatModal) {
        this.chatWrapper = chatModal.querySelector('.chat--wrapper');
        this.form = chatModal.querySelector('form');
        this.button = this.form.querySelector('button.send-message');
        this.textInput = this.form.querySelector('input.message');
        this.spinner = this.form.querySelector('div.spinner');

        this.placeholderIdle = this.textInput.getAttribute('data-placeholder-idle');
        this.placeholderProcessing = this.textInput.getAttribute('data-placeholder-processing');

        this.commands = JSON.parse(this.chatWrapper.getAttribute('data-chat-commands') || '{}');

        this.autoCompleteJS = null;
    }

    toggleElements(disabled) {
        this.textInput.disabled = disabled;
        this.button.disabled = disabled;

        this.button.classList.toggle('inline-flex', !disabled);
        this.button.classList.toggle('hidden', disabled);

        this.form.classList.toggle('disabled', disabled);
        this.spinner.classList.toggle('hidden', !disabled);

        this.textInput.value = '';
        this.textInput.placeholder = disabled ? this.placeholderProcessing : this.placeholderIdle;
    }

    initAutoComplete() {
        if (!this.commands || Object.keys(this.commands).length === 0) return;

        this.autoCompleteJS = new autoComplete({
            selector: () => this.textInput,
            data: { src: Object.keys(this.commands) },
            threshold: 1,
            trigger: query => query.startsWith('/'),
            resultsList: { tabSelect: true, position: 'beforebegin' },
            resultItem: {
                highlight: true,
                element: (item, data) => {
                    item.innerHTML = `
                        <div>${data.match}</div>
                        <div class="text-xs text-gray-500">${this.commands[data.value]}</div>
                    `;
                }
            },
            events: {
                input: {
                    focus: () => {
                        if (this.autoCompleteJS.input.value.length) this.autoCompleteJS.start();
                    }
                }
            }
        });

        this.autoCompleteJS.input.addEventListener('selection', event => {
            const feedback = event.detail;
            this.autoCompleteJS.input.value = this.commands[feedback.selection.value] || '';
        });
    }

    handleSSE(message) {
        const source = new EventSource(`/chat/sse?message=${encodeURIComponent(message)}`);

        source.addEventListener('fullMessage', event => {
            this.chatWrapper.innerHTML += event.data.replace(/\\n/g, '\n');
        });

        source.addEventListener('removeThinking', () => {
            const thinkingEl = this.chatWrapper.querySelector('.thinking');
            thinkingEl?.remove();
        });

        source.addEventListener('agentResponse', event => {
            const lastMessage = this.chatWrapper.querySelector('div.message-wrapper:last-child > div.message');
            if (lastMessage) {
                lastMessage.innerHTML += event.data.replace(/\\n/g, '\n');
            }
        });

        source.addEventListener('done', () => {
            source.close();
            this.toggleElements(false);
            this.textInput.focus();
        });
    }

    bindEvents() {
        // Form submission
        this.form.addEventListener('submit', e => {
            e.preventDefault();
            const formData = new FormData(this.form);
            this.toggleElements(true);
            this.handleSSE(formData.get('form[message]'));
        });

        // Enter key submits
        this.textInput.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.form.requestSubmit();
            }
        });
    }

    render() {
        this.initAutoComplete();
        this.bindEvents();
    }
}