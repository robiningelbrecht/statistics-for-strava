import autoComplete from "../../libraries/autocomplete";
import { marked } from 'marked';

class ChatMessageRenderer {
    constructor(messageEl) {
        this.el = messageEl;
        this.buffer = '';

        this.md = marked.setOptions({
            gfm: true,
            breaks: true,
        });
    }

    setText(text) {
        this.buffer = text;
    }

    append(chunk) {
        this.buffer += chunk;
        this.renderStreaming();
    }

    renderStreaming() {
        // VERY lightweight streaming render.
        const openFences = (this.buffer.match(/```/g) || []).length % 2 === 1;

        let text = this.buffer
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        if (!openFences) {
            text = text
                .replace(/`([^`]+)`/g, '<code>$1</code>')
                .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        }

        this.el.innerHTML = text.replace(/\n/g, '<br>');
    }

    renderFinal() {
        this.el.innerHTML = this.md.parse(this.buffer.trim());
    }
}

export default class Chat {
    constructor(chatModal) {
        this.chatWrapper = chatModal.querySelector('.chat--wrapper');
        this.form = chatModal.querySelector('form');
        this.button = this.form.querySelector('button.send-message');
        this.textInput = this.form.querySelector('input.message');
        this.spinner = this.form.querySelector('div.spinner');
        this.clearButton = chatModal.querySelector('button.clear-chat');

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
        let renderer = null;

        source.addEventListener('fullMessage', event => {
            this.chatWrapper.insertAdjacentHTML(
                'beforeend',
                event.data.replace(/\\n/g, '\n')
            );

            const messageEl = this.chatWrapper.querySelector('div.message-wrapper:last-child > div.message');
            renderer = new ChatMessageRenderer(messageEl);
        });

        source.addEventListener('removeThinking', () => {
            this.chatWrapper.querySelector('.thinking')?.remove();
        });

        source.addEventListener('agentResponse', event => {
            if (!renderer) return;

            renderer.append(event.data.replace(/\\n/g, '\n'));
        });

        source.addEventListener('done', () => {
            source.close();
            renderer?.renderFinal();
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

        // Clear chat
        if (this.clearButton) {
            this.clearButton.addEventListener('click', () => this.clearChat());
        }
    }

    async clearChat() {
        if (!confirm('Are you sure you want to clear the chat history?')) {
            return;
        }

        try {
            await fetch('/chat/clear', { method: 'POST' });
            this.chatWrapper.innerHTML = '';
        } catch (error) {
            console.error('Failed to clear chat:', error);
        }
    }

    parseExistingMessages() {
        const messages = this.chatWrapper.querySelectorAll('div.message');
        messages.forEach(messageEl => {
            if (messageEl.dataset.parsed === 'true') return;

            const rawText = messageEl.textContent;
            if (!rawText) return;

            const renderer = new ChatMessageRenderer(messageEl);
            renderer.setText(rawText);
            renderer.renderFinal();
            messageEl.dataset.parsed = 'true';
        });
    }

    render() {
        this.parseExistingMessages();
        this.initAutoComplete();
        this.bindEvents();
    }
}