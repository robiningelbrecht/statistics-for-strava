import autoComplete from "../../libraries/autocomplete";
import { marked } from 'marked';

// Configure marked for better rendering
marked.setOptions({
    gfm: true,
    breaks: true,
});

export default class Chat {
    constructor(chatModal) {
        this.chatModal = chatModal;
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
        this.currentMessageRaw = '';
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
            // Capture any initial content from the message
            const lastMessage = this.chatWrapper.querySelector('div.message-wrapper:last-child > div.message');
            if (lastMessage) {
                this.currentMessageRaw = lastMessage.textContent.trim();
            } else {
                this.currentMessageRaw = '';
            }
        });

        source.addEventListener('removeThinking', () => {
            const thinkingEl = this.chatWrapper.querySelector('.thinking');
            thinkingEl?.remove();
        });

        source.addEventListener('agentResponse', event => {
            const lastMessage = this.chatWrapper.querySelector('div.message-wrapper:last-child > div.message');
            if (lastMessage) {
                this.currentMessageRaw += event.data.replace(/\\n/g, '\n');
                lastMessage.innerHTML = marked.parse(this.currentMessageRaw);
            }
        });

        source.addEventListener('done', () => {
            source.close();
            this.currentMessageRaw = '';
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

    parseMarkdown(text) {
        // Remove common leading whitespace from all lines (dedent)
        const lines = text.split('\n');
        const nonEmptyLines = lines.filter(line => line.trim());
        if (nonEmptyLines.length === 0) return '';

        const minIndent = Math.min(...nonEmptyLines.map(line => line.match(/^(\s*)/)[1].length));
        const dedented = lines.map(line => line.slice(minIndent)).join('\n').trim();

        return marked.parse(dedented);
    }

    parseExistingMessages() {
        const messages = this.chatWrapper.querySelectorAll('div.message');
        messages.forEach(msg => {
            const raw = msg.textContent;
            if (raw.trim()) {
                msg.innerHTML = this.parseMarkdown(raw);
            }
        });
    }

    render() {
        this.parseExistingMessages();
        this.initAutoComplete();
        this.bindEvents();
    }
}