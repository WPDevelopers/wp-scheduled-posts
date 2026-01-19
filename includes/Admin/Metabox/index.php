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
                <svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g clip-path="url(#clip0_4870_6156)">
                    <path d="M0.520931 10.6667C0.417905 10.6668 0.317189 10.6362 0.23152 10.579C0.145852 10.5218 0.0790804 10.4404 0.0396518 10.3452C0.000223108 10.25 -0.0100913 10.1453 0.0100132 10.0443C0.0301177 9.94322 0.0797379 9.85041 0.152597 9.77757L9.7776 0.152569C9.87529 0.0548806 10.0078 0 10.1459 0C10.2841 0 10.4166 0.0548806 10.5143 0.152569C10.612 0.250257 10.6668 0.38275 10.6668 0.520902C10.6668 0.659054 10.612 0.791547 10.5143 0.889235L0.889264 10.5142C0.840938 10.5627 0.783522 10.6011 0.720312 10.6272C0.657102 10.6534 0.589344 10.6668 0.520931 10.6667Z" fill="#475467"/>
                    <path d="M10.1459 10.6667C10.0775 10.6668 10.0097 10.6534 9.94652 10.6272C9.88331 10.6011 9.82589 10.5627 9.77757 10.5142L0.152569 0.889235C0.0548806 0.791547 0 0.659054 0 0.520902C0 0.38275 0.0548806 0.250257 0.152569 0.152569C0.250257 0.0548806 0.38275 0 0.520902 0C0.659054 0 0.791547 0.0548806 0.889235 0.152569L10.5142 9.77757C10.5871 9.85041 10.6367 9.94322 10.6568 10.0443C10.6769 10.1453 10.6666 10.25 10.6272 10.3452C10.5878 10.4404 10.521 10.5218 10.4353 10.579C10.3496 10.6362 10.2489 10.6668 10.1459 10.6667Z" fill="#475467"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_4870_6156">
                    <rect width="10.6667" height="10.6667" fill="white"/>
                    </clipPath>
                    </defs>
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