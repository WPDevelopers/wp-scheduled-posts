import { useState, useEffect } from 'react';
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

                    const pinterestData = (response.pinterest_profile_list || []).map((user, index) => {
                        const boardValue = user?.default_board_name?.value;
                        const boardLabel = user?.default_board_name?.label || '';
                        const sectionRaw = user?.defaultSection;
                        const sectionValue = sectionRaw?.value || sectionRaw?.id || '';
                        const sectionLabel = sectionRaw?.label || sectionRaw?.name || (
                            typeof sectionRaw === 'string' ? sectionRaw : ''
                        );
                        // Use a unique UI id to avoid collapsing multiple Pinterest items that share board/profile ids.
                        const uiId = [boardValue, sectionValue || 'no-section', index].filter(Boolean).join('|');

                        return {
                            ...user,
                            id: uiId,
                            boardId: boardValue,
                            // Keep `name` as board name for backward compatibility.
                            name: boardLabel,
                            boardName: boardLabel,
                            sectionName: sectionLabel,
                            displayName: sectionLabel ? `${boardLabel} / ${sectionLabel}` : boardLabel,
                            thumbnail_url: user.thumbnail_url,
                        };
                    }).filter((item) => item.boardId); // Ensure we have a board ID

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
        fetchProfiles();
    }, []);

    return { socialProfiles, isLoading };
};

export default useSocialProfiles;
