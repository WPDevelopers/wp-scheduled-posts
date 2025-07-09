import React, { useState, useEffect, useCallback, useMemo } from 'react';
import CustomSocialTemplateModal from './CustomSocialTemplateModal';
import { fetchSocialProfileData } from '../helper';

const {
  components: { Button },
  data: { useSelect, useDispatch },
} = wp;
const { __ } = wp.i18n;

// Separate hook for image handling
const useImagePreview = () => {
  const { post, meta } = useSelect((select) => ({
    post: select('core/editor').getCurrentPost(),
    meta: select('core/editor').getEditedPostAttribute('meta') || {}
  }));

  const [imageUrl, setImageUrl] = useState('');

  const fetchImage = useCallback(async (imageId) => {
    if (!imageId) return null;
    const attachment = wp.media.attachment(imageId);
    try {
      await attachment.fetch();
      return attachment.get('url');
    } catch (error) {
      console.error(`Error fetching image ${imageId}:`, error);
      return null;
    }
  }, []);

  useEffect(() => {
    let isMounted = true;

    const updateImage = async () => {
      setImageUrl(''); // Reset on dependencies change

      const socialShareImageId = meta._wpscppro_custom_social_share_image;
      const featuredMediaId = post.featured_media;

      // Try custom social share image first
      if (socialShareImageId) {
        const url = await fetchImage(socialShareImageId);
        if (url && isMounted) {
          setImageUrl(url);
          return;
        }
      }

      // Fallback to featured image
      if (featuredMediaId) {
        const url = await fetchImage(featuredMediaId);
        if (url && isMounted) {
          setImageUrl(url);
        }
      }
    };

    updateImage();

    return () => {
      isMounted = false;
    };
  }, [post.featured_media, meta._wpscppro_custom_social_share_image, fetchImage]);

  return imageUrl;
};

// Separate hook for post data
const usePostData = () => {
  return useSelect((select) => {
    const post = select('core/editor').getCurrentPost();
    const postId = select('core/editor').getCurrentPostId();
    
    return {
      postTitle: post.title || '',
      postContent: post.excerpt || post.content?.substring(0, 100) + '...' || '',
      postUrl: post.link || `${window.location.origin}/?p=${postId}`
    };
  });
};

// Separate hook for social profile data
const useSocialProfileData = () => {
  const [profileData, setProfileData] = useState({
    facebookProfileData: [],
    twitterProfileData: [],
    linkedinProfileData: [],
    pinterestProfileData: [],
    instagramProfileData: [],
    mediumProfileData: [],
    threadsProfileData: []
  });

  useEffect(() => {
    const fetchAllProfileData = async () => {
      try {
        const apiUrl = '/wp-scheduled-posts/v1/get-option-data';
        const response = await fetchSocialProfileData(apiUrl, null, false);
        if (response) {
          const data = JSON.parse(response);
          const boardsData = [];
          const pinterest_profile_data = data?.pinterest_profile_list || [];          
          pinterest_profile_data.forEach((user) => {
            const thumbnail = user.thumbnail_url;
            boardsData.push({
              id: user?.default_board_name?.value,
              name: user?.default_board_name?.label,
              thumbnail_url: thumbnail
            });
          });
          setProfileData({
            facebookProfileData: data.facebook_profile_list || [],
            twitterProfileData: data.twitter_profile_list || [],
            linkedinProfileData: data.linkedin_profile_list || [],
            pinterestProfileData: boardsData || [],
            instagramProfileData: data.instagram_profile_list || [],
            mediumProfileData: data.medium_profile_list || [],
            threadsProfileData: data.threads_profile_list || []
          });
        }
      } catch (error) {
        console.error('Error fetching social profile data:', error);
      }
    };

    fetchAllProfileData();
  }, []);

  return profileData;
};

const CustomSocialTemplate = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const uploadSocialShareBanner = useImagePreview();
  const { postTitle, postContent, postUrl } = usePostData();
  const socialProfileData = useSocialProfileData();

  // Get enable/disable meta field
  const isCustomTemplateEnabled = useSelect((select) => {
    const meta = select('core/editor').getEditedPostAttribute('meta') || {};
    return typeof meta._wpsp_enable_custom_social_template === 'boolean' ? meta._wpsp_enable_custom_social_template : false;
  }, []);
  
  const meta = useSelect((select) => select('core/editor').getEditedPostAttribute('meta') || {}, []);
  const { editPost } = useDispatch('core/editor');

  const openModal = useCallback(() => setIsModalOpen(true), []);
  const closeModal = useCallback(() => setIsModalOpen(false), []);

  const modalProps = useMemo(() => ({
    isOpen: isModalOpen,
    onClose: closeModal,
    facebookProfileData: socialProfileData.facebookProfileData,
    twitterProfileData: socialProfileData.twitterProfileData,
    linkedinProfileData: socialProfileData.linkedinProfileData,
    pinterestProfileData: socialProfileData.pinterestProfileData,
    instagramProfileData: socialProfileData.instagramProfileData,
    mediumProfileData: socialProfileData.mediumProfileData,
    threadsProfileData: socialProfileData.threadsProfileData,
    postTitle,
    postContent,
    postUrl,
    uploadSocialShareBanner
  }), [
    isModalOpen,
    closeModal,
    socialProfileData,
    postTitle,
    postContent,
    postUrl,
    uploadSocialShareBanner
  ]);

  return (
    <>
      <div className="wpsp-custom-template-button-wrapper">
        <h4 style={{ margin: '0 0 10px 0', fontSize: '14px', fontWeight: '600', color: '#1e1e1e' }}>
          {__('Custom Social Templates', 'wp-scheduled-posts')}
        </h4>
        <p style={{ margin: '0 0 15px 0', fontSize: '13px', color: '#666', lineHeight: '1.4' }}>
          {__('Add custom messages for the profiles you want to post from.', 'wp-scheduled-posts')}
        </p>
        <div className='wpsp-custom-template-label-wrap'>
          <span>{ __('Enable Custom Social Template','wp-scheduled-posts') }</span>
          <div>
            <input
              type="checkbox"
              id="customTemplateEnabled"
              checked={isCustomTemplateEnabled}
              onChange={e => editPost({ meta: { ...meta, _wpsp_enable_custom_social_template: e.target.checked } })}
            />
            <label htmlFor="customTemplateEnabled"></label>
          </div>
        </div>
        <Button
          isSecondary
          onClick={openModal}
          className="wpsp-add-template-btn"
          disabled={!isCustomTemplateEnabled}
        >
          {__('Add Social Message', 'wp-scheduled-posts')}
        </Button>
      </div>
      <CustomSocialTemplateModal {...modalProps} />
    </>
  );
};

export default CustomSocialTemplate;