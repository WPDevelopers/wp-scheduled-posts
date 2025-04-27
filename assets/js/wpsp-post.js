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

// Select profile 
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

// Instagram Carousel
  const data = {
    reels: {
      files: ['Reel1.png', 'Reel2.png', 'Reel3.png'],
      images: [
        'https://via.placeholder.com/400x300?text=Reel+1',
        'https://via.placeholder.com/400x300?text=Reel+2',
        'https://via.placeholder.com/400x300?text=Reel+3'
      ]
    },
    carousel: {
      files: ['Carousel1.png', 'Carousel2.png', 'Carousel3.png'],
      images: [
        'https://via.placeholder.com/400x300?text=Carousel+1',
        'https://via.placeholder.com/400x300?text=Carousel+2',
        'https://via.placeholder.com/400x300?text=Carousel+3'
      ]
    }
  };

  let currentTab = 'reels';
  let currentIndex = 0;

  function renderTabContent() {
    const { files, images } = data[currentTab];
    const content = `
      <img src="${images[0]}" class="thumbnail" onclick="openPopup(0)">
      <div class="text">${files.join(', ')}</div>
      <div class="subtext">${files.length} image uploaded</div>
      <button class="btn btn-gray" onclick="openPopup(0)">View all</button>
      <button class="btn btn-light">Preview</button>
    `;
    document.getElementById('tabContent').innerHTML = content;
  }

  function switchTab(tabName) {
    currentTab = tabName;
    document.getElementById('tab-reels').classList.toggle('active', tabName === 'reels');
    document.getElementById('tab-carousel').classList.toggle('active', tabName === 'carousel');
    renderTabContent();
  }

  function openPopup(index) {
    currentIndex = index;
    document.getElementById("popupImage").src = data[currentTab].images[currentIndex];
    document.getElementById("popup").style.display = "flex";
  }

  function closePopup() {
    document.getElementById("popup").style.display = "none";
  }

  function nextImage() {
    const images = data[currentTab].images;
    currentIndex = (currentIndex + 1) % images.length;
    document.getElementById("popupImage").src = images[currentIndex];
  }

  function prevImage() {
    const images = data[currentTab].images;
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    document.getElementById("popupImage").src = images[currentIndex];
  }

  // Initialize
  renderTabContent();