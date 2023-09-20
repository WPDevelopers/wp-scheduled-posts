import React from 'react';
const {
	element: { createElement,Fragment },
} = wp;
const { __ } = wp.i18n;

const SelectedSocialProfileModal = ( { platform, selectedSocialProfile, responseMessage } ) => {
  return (
    <>
        { selectedSocialProfile.filter( (profile) => profile.platform === platform ).length > 0 && 
        <div className={`profile-${platform}`}>
            <h2>{platform}</h2>
            { selectedSocialProfile.filter( (profile) => profile.platform === platform ).map( ( profile ) => (
            <div className="profile-list">
                { profile?.name }
                { responseMessage.find( (item) => item.id === profile.id )?.id }
            </div>
            ) ) }
        </div>
        }
    </>
  )
}

export default SelectedSocialProfileModal