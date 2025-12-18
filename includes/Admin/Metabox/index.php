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
            <button class="wpsp-post-panel-close" type="button">×</button>
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