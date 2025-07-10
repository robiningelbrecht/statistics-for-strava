export default function Chat($chatModal) {
    const $button = $chatModal.querySelector('button.send-message');
    const $textInput =  $chatModal.querySelector('input.message');
    const $chatWrapper = $chatModal.querySelector('.chat--wrapper');

    const disableElements = () => {
        $textInput.disabled = true;
        $button.disabled = true;
    };

    const enableElements = () => {
        $textInput.disabled = false;
        $button.disabled = false;
    }

    const render = () => {
        $button.addEventListener('click', (e) => {
            disableElements();
            $chatWrapper.innerHTML += '<div class="flex items-start gap-x-6 bg-white shadow-xs p-6 text-gray-500 rounded-md last:animate-fade-in-chat-message"> <img class="size-6 rounded-full border" src=""  alt="Mark"/> <div>Certainly! Here is a list of potential essay topics related to design principles, along with a brief outline of main points for each topic:</div> </div>'
        });
    };

    return {
        render
    };
}