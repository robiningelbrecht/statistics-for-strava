export default function Chat($chatModal) {
    const $chatWrapper = $chatModal.querySelector('.chat--wrapper');
    const $form = $chatModal.querySelector('form');
    const $button = $form.querySelector('button.send-message');
    const $textInput = $form.querySelector('input.message');

    const disableElements = () => {
        $textInput.disabled = true;
        $button.disabled = true;
    };

    const enableElements = () => {
        $textInput.disabled = false;
        $button.disabled = false;
        $textInput.value = '';
    }

    const render = () => {
        $form.addEventListener('submit', async (e) => {
            e.preventDefault();

            disableElements();

            const response = await fetch($form.getAttribute('action'), {
                method: 'POST',
                body: new FormData($form),
            });
            const json = await response.json();

            $chatWrapper.innerHTML += json.response;

            enableElements();
        });
    };

    return {
        render
    };
}