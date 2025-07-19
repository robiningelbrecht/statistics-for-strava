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

            const formData = new FormData($form);

            disableElements();

            const response = await fetch($form.getAttribute('action'), {
                method: 'POST',
                body: JSON.stringify(Object.fromEntries(formData)),
                headers: {
                    'Content-Type': 'application/json',
                },
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