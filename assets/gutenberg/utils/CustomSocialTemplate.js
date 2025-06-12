import React, { useState, useEffect } from 'react';
import CustomSocialTemplateModal from './CustomSocialTemplateModal';
import { fetchSocialProfileData } from '../helper';

const {
  components: { Button },
  data: { useSelect },
} = wp;
const { __ } = wp.i18n;

const CustomSocialTemplate = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [socialProfileData, setSocialProfileData] = useState({
    facebookProfileData: [],
    twitterProfileData: [],
    linkedinProfileData: [],
    pinterestProfileData: [],
    instagramProfileData: [],
    mediumProfileData: [],
    threadsProfileData: []
  });

  // Get current post data for template preview
  const { postTitle, postContent, postUrl } = useSelect((select) => {
    const post = select('core/editor').getCurrentPost();
    const postId = select('core/editor').getCurrentPostId();

    return {
      postTitle: post.title || '',
      postContent: post.excerpt || post.content?.substring(0, 100) + '...' || '',
      postUrl: post.link || `${window.location.origin}/?p=${postId}`
    };
  });

  // Fetch all social profile data when component mounts
  useEffect(() => {
    const fetchAllProfileData = async () => {
      try {
        const apiUrl = '/wp-scheduled-posts/v1/get-option-data';
        const profileData = await fetchSocialProfileData(apiUrl,null, false);
        if (profileData) {
          const data = JSON.parse(profileData);
          setSocialProfileData({
            facebookProfileData: data.facebook_profile_list || [],
            twitterProfileData: data.twitter_profile_list || [],
            linkedinProfileData: data.linkedin_profile_list || [],
            pinterestProfileData: data.pinterest_profile_list || [],
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

  const openModal = () => setIsModalOpen(true);
  const closeModal = () => setIsModalOpen(false);

  return (
    <>
      <div className="wpsp-custom-template-button-wrapper">
        <h4 style={{ margin: '0 0 10px 0', fontSize: '14px', fontWeight: '600', color: '#1e1e1e' }}>
          {__('Custom Social Templates', 'wp-scheduled-posts')}
        </h4>
        <p style={{ margin: '0 0 15px 0', fontSize: '13px', color: '#666', lineHeight: '1.4' }}>
          {__('Create custom templates for specific social media platforms and profiles.', 'wp-scheduled-posts')}
        </p>
        <Button
          isSecondary
          onClick={openModal}
          className="wpsp-add-template-btn"
        >
          {__('Add Social Template', 'wp-scheduled-posts')}
        </Button>
      </div>

      <CustomSocialTemplateModal
        isOpen={isModalOpen}
        onClose={closeModal}
        facebookProfileData={socialProfileData.facebookProfileData}
        twitterProfileData={socialProfileData.twitterProfileData}
        linkedinProfileData={socialProfileData.linkedinProfileData}
        pinterestProfileData={socialProfileData.pinterestProfileData}
        instagramProfileData={socialProfileData.instagramProfileData}
        mediumProfileData={socialProfileData.mediumProfileData}
        threadsProfileData={socialProfileData.threadsProfileData}
        postTitle={postTitle}
        postContent={postContent}
        postUrl={postUrl}
      />
    </>
  );
};

export default CustomSocialTemplate;