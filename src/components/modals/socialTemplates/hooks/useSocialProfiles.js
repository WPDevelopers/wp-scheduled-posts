import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { fetchSocialProfileData } from '../../../../helper/helper';

const useSocialProfiles = () => {
    const [socialProfiles, setSocialProfiles] = useState({
        facebook: [],
        twitter: [],
        linkedin: [],
        pinterest: [],
        instagram: [],
        medium: [],
        threads: [],
        google_business: []
    });
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const fetchProfiles = async () => {
            try {
                // Using the exact endpoint from CustomSocialTemplate.js
                // Note: The previous code used a helper 'fetchSocialProfileData' which just wraps apiFetch.
                // We'll use apiFetch directly for simplicity and module independence.
                const apiUrl = '/wp-scheduled-posts/v1/get-option-data';
                const fetchSettingsData = await fetchSocialProfileData(apiUrl, null, false);
                const response = JSON.parse(fetchSettingsData);
                console.log('response',response);
                
                if (response) {
                    // Start: Process and Deduplicate Data
                    // We use Maps to ensure unique IDs per platform.
                    
                    const processProfiles = (list) => {
                        if (!Array.isArray(list)) return [];
                        const uniqueMap = new Map();
                        list.forEach(item => {
                            if (item && item.id) {
                                uniqueMap.set(String(item.id), item);
                            }
                        });
                        return Array.from(uniqueMap.values());
                    };

                    const pinterestData = (response.pinterest_profile_list || []).map(user => ({
                        id: user?.default_board_name?.value,
                        name: user?.default_board_name?.label,
                        thumbnail_url: user.thumbnail_url,
                        // Preserve other fields if needed
                        ...user
                    })).filter(item => item.id); // Ensure we have an ID

                    setSocialProfiles({
                        facebook: processProfiles(response.facebook_profile_list),
                        twitter: processProfiles(response.twitter_profile_list),
                        linkedin: processProfiles(response.linkedin_profile_list),
                        pinterest: processProfiles(pinterestData),
                        instagram: processProfiles(response.instagram_profile_list),
                        medium: processProfiles(response.medium_profile_list),
                        threads: processProfiles(response.threads_profile_list),
                        google_business: processProfiles(response.google_business_profile_list),
                    });
                }
            } catch (error) {
                console.error('Error fetching social profiles:', error);
            } finally {
                setIsLoading(false);
            }
        };
        console.log("fetchProfiles",socialProfiles);
        fetchProfiles();
    }, []);

    return { socialProfiles, isLoading };
};

export default useSocialProfiles;
