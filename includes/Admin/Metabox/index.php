<div id="wpsp-post-panel-wrapper">
    <button id="wpsp-post-panel-button" type="button">
        Schedule And Share
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const openBtn = document.getElementById('wpsp-post-panel-button');

        // Create modal dynamically
        const modal = document.createElement('div');
        modal.id = 'wpsp-post-panel-modal';
        modal.classList.add('wpsp-post-panel-modal');
        modal.innerHTML = `
        <div class="wpsp-post-panel-overlay"></div>
        <div class="wpsp-post-panel-content">
            <button class="wpsp-post-panel-close" type="button">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                    <path d="m13.06 12 6.47-6.47-1.06-1.06L12 10.94 5.53 4.47 4.47 5.53 10.94 12l-6.47 6.47 1.06 1.06L12 13.06l6.47 6.47 1.06-1.06L13.06 12Z"></path>
                </svg>
            </button>
            <div id="wpsp-post-panel-react-root"></div>
        </div>
    `;
        document.body.appendChild(modal);

        const closeBtn = modal.querySelector('.wpsp-post-panel-close');
        const overlay = modal.querySelector('.wpsp-post-panel-overlay');

        // Open modal
        const openModal = () => {
            modal.classList.add('wpsp-post-panel-active');
            document.body.style.overflow = 'hidden';
        };

        // Close modal
        const closeModal = () => {
            modal.classList.remove('wpsp-post-panel-active');
            document.body.style.overflow = '';
        };

        // Event listeners
        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);
    });
</script>