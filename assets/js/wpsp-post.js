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

const selectedBox = document.getElementById('selectedBox');
  const dropdownOptions = document.getElementById('dropdownOptions');
  const checkboxes = document.querySelectorAll('.profile');
  const selectAll = document.getElementById('selectAll');

  selectedBox.addEventListener('click', () => {
    dropdownOptions.classList.toggle('active');
  });

  function updateSelected() {
    selectedBox.innerHTML = '';
    const selected = Array.from(checkboxes).filter(cb => cb.checked);
    if (selected.length === 0) {
      selectedBox.innerHTML = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 7.5L10 12.5L15 7.5" stroke="#475467" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    } else {
      selected.forEach(cb => {
        const tag = document.createElement('div');
        tag.className = 'avatar-tag';
        tag.innerHTML = `<img src="${cb.dataset.img}">${cb.value}`;
        selectedBox.appendChild(tag);
      });
    }
  }

  checkboxes.forEach(cb => {
    cb.addEventListener('change', updateSelected);
  });

  selectAll.addEventListener('change', (e) => {
    checkboxes.forEach(cb => cb.checked = e.target.checked);
    updateSelected();
  });