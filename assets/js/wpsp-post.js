
(function($) {
  // Classic editor modal open
  document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('wpsp-post-modal');
    const closeBtn = document.getElementById('wpsp-modal-close');
    window.mypluginOpenModal = () => modal.classList.add('active');
    const closeModal = () => modal.classList.remove('active');
    closeBtn.addEventListener('click', closeModal);
    modal.querySelector('.wpsp-modal-backdrop')?.addEventListener('click', closeModal);
  });

  // Social Message Modal Functions
  function openSocialMessageModal() {
    const modal = document.getElementById('wpsp-social-message-modal');
    if (modal) {
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('wpsp-modal-open');

        // Initialize the active platform (Facebook by default)
        const facebookTab = document.querySelector('.wpsp-platform-icon.facebook');
        if (facebookTab && !facebookTab.classList.contains('active')) {
          facebookTab.click();
        }

        // Initialize selected profiles display when modal opens
        setTimeout(() => {
          if (typeof updateSelectedProfiles === 'function') {
            updateSelectedProfiles();
          }
        }, 100);
      }, 10);
      document.body.style.overflow = 'hidden';
    }
  }

  function closeSocialMessageModal() {
    const modal = document.getElementById('wpsp-social-message-modal');
    if (modal) {
      modal.classList.remove('wpsp-modal-open');
      setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }, 300);
    }
  }

  // Update selected profiles display function (global scope)
  function updateSelectedProfiles() {
    // Get current active platform
    const activePlatform = $('.wpsp-platform-icon.active').hasClass('facebook') ? 'facebook' :
                          ($('.wpsp-platform-icon.active').hasClass('instagram') ? 'instagram' :
                          ($('.wpsp-platform-icon.active').hasClass('google_business') ? 'google_business' : 'facebook'));

    const selectedProfilesList = document.querySelector(`#wpsp-profile-${activePlatform} .selected-profile-area ul`);
    const checkedBoxes = document.querySelectorAll(`#wpsp-profile-${activePlatform} .wpsp-modal-profile-checkbox:checked`);

    if (selectedProfilesList) {
      // Clear existing profiles
      selectedProfilesList.innerHTML = '';

      // Add each selected profile
      checkedBoxes.forEach(checkbox => {
        const profileName = checkbox.getAttribute('data-name');
        const profileImg = checkbox.getAttribute('data-img');
        const profileId = checkbox.value;
        const platform = checkbox.getAttribute('data-platform') || activePlatform;

        const listItem = document.createElement('li');
        listItem.className = 'selected-profile';
        listItem.title = profileName;
        listItem.innerHTML = `
          <img src="${profileImg}" alt="${profileName}" class="wpsp-profile-image">
          <div class="wpsp-selected-profile-action">
            <span class="wpsp-remove-profile-btn" data-profile-id="${profileId}" data-platform="${platform}">×</span>
            <span class="wpsp-selected-profile-btn">
              <svg width="10" height="11" viewBox="0 0 10 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="0.6" y="0.900781" width="8.8" height="8.8" rx="4.4" fill="#6C62FF"></rect>
                <rect x="0.6" y="0.900781" width="8.8" height="8.8" rx="4.4" stroke="white" stroke-width="0.8"></rect>
                <g clip-path="url(#clip0_4477_4922)">
                  <path d="M3.58398 5.30078L4.58398 6.30078L6.58398 4.30078" stroke="white" stroke-width="0.64" stroke-linecap="round" stroke-linejoin="round"></path>
                </g>
                <defs>
                  <clipPath id="clip0_4477_4922">
                    <rect width="4" height="4" fill="white" transform="translate(3 3.30078)"></rect>
                  </clipPath>
                </defs>
              </svg>
            </span>
          </div>
        `;

        selectedProfilesList.appendChild(listItem);
      });
    }
  }

  // Social Message Modal Event Listeners
  document.addEventListener('DOMContentLoaded', function () {
    // Open modal button
    const openBtn = document.getElementById('wpsp-add-social-message');
    if (openBtn) {
      openBtn.addEventListener('click', function(e) {
        e.preventDefault();
        openSocialMessageModal();
      });
    }

    // Close modal buttons
    const closeBtn = document.getElementById('wpsp-modal-close');
    const cancelBtn = document.getElementById('wpsp-close-social-message-modal');
    const overlay = document.querySelector('.wpsp-modal-overlay');

    if (closeBtn) {
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // closeSocialMessageModal();
      });
    }

    if (cancelBtn) {
      cancelBtn.addEventListener('click', function(e) {
        e.preventDefault();
        closeSocialMessageModal();
      });
    }

    if (overlay) {
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
          // closeSocialMessageModal();
        }
      });
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        // closeSocialMessageModal();
      }
    });

    // Character counter
    const messageTextarea = document.getElementById('wpsp-social-message');
    const charCount = document.getElementById('wpsp-char-count');

    if (messageTextarea && charCount) {
      messageTextarea.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length;

        if (length > 250) {
          charCount.style.color = '#f59e0b';
        } else {
          charCount.style.color = '#6b7280';
        }
      });
    }

    // Profile selection functionality - use event delegation for dynamic content
    document.addEventListener('click', function(e) {
      // Handle profile card clicks
      if (e.target.closest('.wpsp-profile-card')) {
        const card = e.target.closest('.wpsp-profile-card');
        const checkbox = card.querySelector('.wpsp-modal-profile-checkbox');

        // Don't toggle if clicking directly on checkbox
        if (e.target.type !== 'checkbox' && checkbox) {
          checkbox.checked = !checkbox.checked;
          updateSelectedProfiles();
        }
      }

      // Handle remove profile button clicks
      if (e.target.closest('.wpsp-remove-profile-btn')) {
        e.stopPropagation();
        const removeBtn = e.target.closest('.wpsp-remove-profile-btn');
        const profileId = removeBtn.getAttribute('data-profile-id');
        const platform = removeBtn.getAttribute('data-platform') || ($('.wpsp-platform-icon.active').hasClass('google_business') ? 'google_business' : ($('.wpsp-platform-icon.active').hasClass('instagram') ? 'instagram' : 'facebook'));
        const checkbox = document.querySelector(`#wpsp-profile-${platform} input.wpsp-modal-profile-checkbox[value="${profileId}"]`);
        if (checkbox) {
          checkbox.checked = false;
          updateSelectedProfiles();
        }
      }
    });

    // Profile checkbox change handler - use event delegation
    document.addEventListener('change', function(e) {
      if (e.target.classList.contains('wpsp-modal-profile-checkbox')) {
        updateSelectedProfiles();
      }
    });



    // Form submission
    const socialForm = document.getElementById('wpsp-social-message-form');
    if (socialForm) {
      socialForm.addEventListener('submit', function(e) {
        e.preventDefault();
        // Get form data in the same format as existing wpsp_save_modal_data
        var formData = {
          action           : "wpsp_save_modal_data",
          _ajax_nonce      : '',
          post_id          : parseInt( $('#post_ID').val() ),
          facebook_profiles: [],
          instagram_profiles: [],
          google_business_profiles: []
        };

        // Collect selected Facebook profiles
        $('#wpsp-profile-facebook .wpsp-modal-profile-checkbox:checked').each(function(){
          var id = $(this).val();          // checkbox value = id
          var name = $(this).data('name'); // get name from data-name

          formData.facebook_profiles.push({
              id: id,
              name: name
          });
        });

        // Collect selected Instagram profiles
        $('#wpsp-profile-instagram .wpsp-modal-profile-checkbox:checked').each(function(){
          var id = $(this).val();          // checkbox value = id
          var name = $(this).data('name'); // get name from data-name

          formData.instagram_profiles.push({
              id: id,
              name: name
          });
        });

        // Collect selected Google Business profiles
        $('#wpsp-profile-google_business .wpsp-modal-profile-checkbox:checked').each(function(){
          var id = $(this).val();
          var name = $(this).data('name');

          formData.google_business_profiles.push({
              id: id,
              name: name
          });
        });

        // Get template content for current active platform
        const activePlatform = $('.wpsp-platform-icon.active').hasClass('facebook') ? 'facebook' :
                              ($('.wpsp-platform-icon.active').hasClass('instagram') ? 'instagram' :
                              ($('.wpsp-platform-icon.active').hasClass('google_business') ? 'google_business' : 'facebook'));

        const templateInput = document.getElementById(`wpsp-template-input${activePlatform === 'facebook' ? '' : '-' + activePlatform}`);
        if (templateInput) {
          formData.social_template = templateInput.value;
          formData.active_platform = activePlatform;
        }

        // Get global template checkbox for current platform
        const globalTemplateCheckbox = document.getElementById(`useGlobalTemplate_${activePlatform}`);
        if (globalTemplateCheckbox) {
          formData.use_global_template = globalTemplateCheckbox.checked ? '1' : '0';
        }

        // Submit via AJAX using the same format as existing code
        $.post(ajaxurl, formData, function(response){
          if (response.success) {
            // closeSocialMessageModal();
          }
        });
      });
    }

    // Initialize selected profiles display
    updateSelectedProfiles();
  });

  // Profile dropdown toggle
  $('.select-profile-icon').click(function(){
    $(this).parents('.wpsp-profile-selection-area-wrapper').find('.wpsp-profile-selection-dropdown').slideToggle();
  });

  // Platform tab switching
  $('.wpsp-platform-icon').click(function(){
    const platform = $(this).hasClass('facebook') ? 'facebook' :
                    ($(this).hasClass('instagram') ? 'instagram' :
                    ($(this).hasClass('google_business') ? 'google_business' : 'facebook'));

    // Update active tab
    $('.wpsp-platform-icon').removeClass('active').css({
      'background-color': 'rgb(240, 240, 240)',
      'color': 'rgb(102, 102, 102)',
      'font-weight': 'normal'
    });

    $(this).addClass('active').css({
      'background-color': platform === 'facebook' ? 'rgb(24, 119, 242)' : (platform === 'instagram' ? 'rgb(225, 48, 108)' : 'rgb(66, 133, 244)'),
      'color': 'rgb(255, 255, 255)',
      'font-weight': 'bold'
    });

    // Show/hide profile sections
    $('.wpsp-profile-selection-area-wrapper').hide();
    $('#wpsp-profile-' + platform).show();

    // Update right panel class
    $('.wpsp-modal-right').removeClass('facebook instagram google_business').addClass(platform);

    // Update selected profiles display for current platform
    updateSelectedProfiles();
  });

  /**
  * WP admin sidebar Upload Image
  */
  jQuery('body').on('click', '#wpsp_upload_banner', function (e) {
    e.preventDefault()
    var button = $(this),
        custom_uploader = wp
            .media({
                title: 'Insert image',
                library: {
                    type: 'image',
                },
                button: {
                    text: 'Use this image', // button label text
                },
                multiple: false, // for multiple image selection set to true
            })
            .on('select', function () {
                // it also has "open" and "close" events
                var attachment = custom_uploader
                    .state()
                    .get('selection')
                    .first()
                    .toJSON()
                jQuery('#wpscppro_custom_social_share_image').val(
                    attachment.id
                )
                let wpscppro_custom_social_share_image = jQuery('#wpscppro_custom_social_share_image');
                if( wpscppro_custom_social_share_image?.length > 0 ) {
                    wpscppro_custom_social_share_image.val( attachment.id )
                }else{
                    jQuery('form.metabox-base-form').append(`<input type="hidden" id="wpscppro_custom_social_share_image" name="wpscppro_custom_social_share_image" value='${attachment.id}' />`)
                }
                console.log('attachment.url',attachment.url);
                
                jQuery('#wpscpprouploadimagepreviewold').hide()
                jQuery('#wpsp_social_share_image_preview').html(
                    '<img class="true_pre_image" src="' +
                        attachment.url +
                        '" style="max-width:100%; height: auto; display:block;" />'
                )
                $('#wpscppro_btn_remove_meta_image_upload').show()
            })
            .open()
  })

  // Remove social share banner image
  $('body').on('click','#wpsp_remove_banner', function (e) {
      e.preventDefault()
      $('#wpscppro_custom_social_share_image').val('')
      $('#wpsp_social_share_image_preview').empty()
    }
  )

  // Select profile 
  document.querySelectorAll('.social--item').forEach(container => {
    const selectedBox = container.querySelector('.selectedBox');
    const dropdownOptions = container.querySelector('.dropdownOptions');
    const checkboxes = container.querySelectorAll('.profile');
    const selectAll = container.querySelector('.selectAll');
  
    if( selectedBox ) {
      selectedBox.addEventListener('click', () => {
        dropdownOptions.classList.toggle('active');
      });
    }
  
    function updateSelected() {
      selectedBox.innerHTML = '';
      const selected = Array.from(checkboxes).filter(cb => cb.checked);
      if (selected.length === 0) {
        selectedBox.innerHTML = '<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 7.5L10 12.5L15 7.5" stroke="#475467" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
      } else {
        selected.forEach(cb => {
          const tag = document.createElement('div');
            tag.className = 'avatar-tag';
            tag.innerHTML = `<img src="${cb.dataset.img}">${cb.dataset.name}`;
            selectedBox.appendChild(tag);
        });
      }
    }
  
    checkboxes.forEach(cb => cb.addEventListener('change', updateSelected));
    if( selectAll ) {
      selectAll.addEventListener('change', (e) => {
        checkboxes.forEach(cb => cb.checked = e.target.checked);
        updateSelected();
      });
      updateSelected(); // Initialize with current state
    }
  
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
    // renderTabContent();
  }

  function openPopup(index) {
    currentIndex = index;
    document.getElementById("popupImage").src = data[currentTab].images[currentIndex];
    document.getElementById("popup").style.display = "block";
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
  // renderTabContent();
})(jQuery);

jQuery(document).ready(function($){
  $('#wpsp-save-settings').on('click', function(e){
      e.preventDefault();
      var formData = {
        action           : "wpsp_save_modal_data",
        _ajax_nonce      : '',
        post_id          : parseInt( $('#post_ID').val() ),
        facebook_profiles: []
      };
      
      // Only select checkboxes inside #facebook-profiles
      $('#facebook-profiles input[type="checkbox"]:checked').each(function(){
          var id = $(this).val();          // checkbox value = id
          var name = $(this).data('name'); // get name from data-name
          
          formData.facebook_profiles.push({
              id: id,
              name: name
          });
      });
      $.post(ajaxurl, formData, function(response){
          console.log(response);
      });
  });
});
