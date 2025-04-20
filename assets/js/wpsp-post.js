// Classic editor modal open
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('wpsp-post-modal');
    const closeBtn = document.getElementById('wpsp-modal-close');
    console.log('modal',modal);
    
    window.mypluginOpenModal = () => modal.classList.add('active');
    const closeModal = () => modal.classList.remove('active');

    closeBtn.addEventListener('click', closeModal);
    modal.querySelector('.wpsp-modal-backdrop')?.addEventListener('click', closeModal);
});    