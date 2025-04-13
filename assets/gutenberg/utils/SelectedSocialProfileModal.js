import React from 'react';
const {
	element: { createElement,Fragment },
} = wp;
const { __ } = wp.i18n;

const SelectedSocialProfileModal = ( { platform, selectedSocialProfile, responseMessage, pinterest_board_type = ''} ) => {
    let filteredSelectedSocialProfile = selectedSocialProfile;
    // if( platform === 'pinterest' ) {
    //     filteredSelectedSocialProfile = selectedSocialProfile.filter((profile) => profile?.pinterest_board_type === pinterest_board_type);
    // }

    // Function to handle image load error
    const handleImageError = (e) => {
        // @ts-ignore 
        const placeholderImage = `${WPSchedulePostsFree?.assetsURI}/images/author-logo.jpeg`;
        e.target.onerror = null; // Prevents infinite loop in case placeholder image fails
        e.target.src = placeholderImage; // Set the placeholder image
    };
  return (
    <>
        { selectedSocialProfile.filter( (profile) => profile.platform === platform ).length > 0 && 
        <div className={`profile-${platform} social-profile`}>
            <div className="social-logo">
                <img src={ `${WPSchedulePostsFree.assetsURI}images/${platform}.svg` } alt="" />
                <h2>{platform}</h2>
            </div>
            { filteredSelectedSocialProfile.filter( (profile) => profile.platform === platform ).map( ( profile ) => (
                <Fragment>
                    <div className='single-profile'>
                        <div className="single-profile-content">
                            <div className="modal-content-left">
                                <div className="profile-list">
                                    <img 
                                        src={`${profile?.thumbnail_url}`} 
                                        alt={__(profile?.name, 'wp-scheduled-posts')}
                                        onError={handleImageError} // Attach the error handler
                                    />
                                    <h3>{ profile?.name }</h3>
                                    {
                                        {
                                            facebook: (
                                                <span className={`badge facebook`}>{ profile.type ? profile.type : __('Profile','wp-scheduled-posts') }</span>
                                            ),
                                            twitter: (
                                                <span className={`badge twitter`}>{ profile.type ? profile.type : __('Profile','wp-scheduled-posts') }</span>
                                            ),
                                            linkedin: (
                                                <span className={`badge linkedin`}>{ profile?.type == 'organization' ? __('Page','wp-scheduled-posts') : __('Profile','wp-scheduled-posts')  }</span>
                                            ),
                                            pinterest: (
                                                <span className={`badge pinterest`}>{  __('Board','wp-scheduled-posts') }</span>

                                            ),
                                            instagram: (
                                                <span className={`badge facebook`}>{  __('Profile','wp-scheduled-posts') }</span>
                                            ),
                                        }[platform]
                                    }
                                </div>
                            </div>
                            <div className="modal-content-right">
                            { responseMessage.find( ( item ) => item.id === profile.id ) && 
                                <span>
                                {responseMessage.find((item) => item.id === profile.id)?.success === true
                                  ? <Fragment><img src={`${WPSchedulePostsFree.assetsURI}/images/response_success.svg`} alt="Shared" />Shared</Fragment>
                                  : <Fragment><img src={`${WPSchedulePostsFree.assetsURI}/images/response_failed.svg`} alt="" />Failed</Fragment>
                                }
                              </span>                              
                            }
                            </div>
                        </div>
                        { responseMessage.find( ( item ) => item.id === profile.id ) && 
                            <div className={`message ${ responseMessage.find( (item) => item.id === profile.id )?.success ? 'success' : '' } `}>
                                <span>{ responseMessage.find( (item) => item.id === profile.id )?.message }</span>
                            </div>
                        }
                    </div>
                </Fragment>
            ) ) }
        </div>
        }
    </>
  )
}

export default SelectedSocialProfileModal