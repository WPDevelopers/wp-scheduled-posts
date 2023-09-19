import { fetchPinterestSection, fetchSocialProfileData } from "./helper";

const {
	data: { useSelect, useDispatch, select },
	plugins: { registerPlugin },
	date: { dateI18n },
	element: { useState, useEffect,createElement,Fragment },
	components: { TextControl,Button,DateTimePicker,Popover,Modal },
	editPost: { PluginDocumentSettingPanel },
} = wp;
const { __ } = wp.i18n;

const SocialShare = () => {
    const postid = select('core/editor').getCurrentPostId()

    const [isOpen, setIsOpen] = useState(null); // Use null to represent no accordion open
    const [facebookProfileData,setFacebookProfileData] = useState([]);
    const [twiiterProfileData,setTwitterProfileData] = useState([]);
    const [linkedinProfileData,setLinkedinProfileData] = useState([]);
    const [pinterestProfileData,setPinterestProfileData] = useState([]);
    const [isOpenModal,setIsOpenModal] = useState( false );
    const [selectedSocialProfile,setSelectedSocialProfile] = useState([]);
    const [responseMessage,setResponseMessage] = useState([]);
    const [selectionSection, setSelectedSection] = useState('');

    // Get social profile data from wp_options table
    useEffect(() => {
      // fetch facebook profile data
      const optionName = `option_name=wpsp_settings_v5`;
      const apiUrl = '/wp-scheduled-posts/v1/get-option-data';
      fetchSocialProfileData(apiUrl,optionName, false).then( (res) => {
        const wpsp_settings = JSON.parse( res );
        const filtered_facebook_profile_list = wpsp_settings?.facebook_profile_list.filter( item => item.status === true );
        setFacebookProfileData( filtered_facebook_profile_list  );
        const filtered_twitter_profile_list = wpsp_settings?.twitter_profile_list.filter( item => item.status === true );
        setTwitterProfileData( filtered_twitter_profile_list );
        const filtered_linkedin_profile_list = wpsp_settings?.linkedin_profile_list.filter( item => item.status === true );
        setLinkedinProfileData( filtered_linkedin_profile_list );
        let filtered_pinterest_profile_list = wpsp_settings?.pinterest_profile_list.filter( item => item.status === true );
        if( filtered_pinterest_profile_list.length > 0 ) {
          filtered_pinterest_profile_list.map( (pinterest_profile,index) => {
            let data = {
              defaultBoard: pinterest_profile?.default_board_name?.value,
              profile: pinterest_profile,
            };
            fetchPinterestSection(data).then( ( res ) => {
              filtered_pinterest_profile_list[index].sections = res.data;
            } )
          } )
        }
        setPinterestProfileData([...filtered_pinterest_profile_list]);
      } ).catch( (error) => {
        console.log('error',error);
      } )
    }, [])
  
    const toggleAccordion = (type) => {
      if (isOpen === type) {
        // Clicked on an already open accordion, close it
        setIsOpen(null);
      } else {
        // Clicked on a closed accordion, open it
        setIsOpen(type);
      }
    };

    // Handle share now actions
    const handleShareNow = () => {
      setIsOpenModal( true );
      if( selectedSocialProfile.length > 0 ) {
        selectedSocialProfile.map( ( profile ) => {
          profile.postid = postid;
          let queryParams = profile;
          const apiUrl = '/wp-scheduled-posts/v1/instant-social-share';
          fetchSocialProfileData(apiUrl,queryParams).then( (res) => {
            if( profile.id ) {
              responseMessage.push( { id: profile.id, message : res.data } );
              setResponseMessage( [...responseMessage] );
            }
          } ).catch( (error) => {
            console.log('error',error);
          } )
        } )
      }
    }
    const closeModal = () => {
      setResponseMessage([]);
      setIsOpenModal( false )
    };
    
    // Handle profile selection
    const handleProfileSelectionCheckbox = ( event, platform, index, id, name, type ) => {
      if( event.target.checked ) {
        setSelectedSocialProfile((prevData) => {
          if( id ) {
            prevData.push( { id, platform, platformKey: index, name, type } );
          }
          return prevData;
        });
      }else{
        if( selectedSocialProfile.length > 0 ) {
          const filteredSelectedProfile = selectedSocialProfile.filter((item) => item.id !== id);
          setSelectedSocialProfile(filteredSelectedProfile);
        }
      }
    }

    // Handle pinterest profile selection 
    const handlePinterestProfileSelectionCheckbox = ( event, pinterest, index  ) => {
      console.log('event',event);
    }

    // Handle section change event
    const handleSectionChange = (event) => {
      setSelectedSection( event.target.value )
    }
    useEffect(() => {
      console.log('res',selectionSection);
    }, [selectionSection])
    
    return (
      <div className='social-share'>
        <h2 className="social-share-title">Social Share Settings</h2>
        <div className="share-checkbox">
          <input type="checkbox" />
          <span>Disable Social Share</span>
        </div>
        <div className="social-share-wrapper">
          <h3>Choose Social Share Platform</h3>
          <div className="social-accordion-item">
              <div className="social-accordion-button" onClick={() => toggleAccordion('isOpen')}>
                  <img src={ WPSchedulePostsFree.assetsURI + '/images/facebook.svg' } alt="" />
                  <span>Facebook</span>
              </div>
            { isOpen === 'isOpen' && (
              <div className="accordion-content">
                  { facebookProfileData.map( ( facebook, index ) => (
                    <div className="facebook-profile social-profile">
                        <input type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'facebook', index, facebook?.id, facebook?.name, facebook?.type ) } />
                        <h3>{ facebook?.name } ( { facebook.type ? facebook.type : __('Profile','wp-scheduled-posts') } ) </h3>
                    </div>
                  ) ) }
              </div>
            )}
          </div>
          <div className="social-accordion-item">
              <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenTwitter')}>
                  <img src={ WPSchedulePostsFree.assetsURI + '/images/twitter.svg' } alt="" />
                  <span>Twitter</span>
              </div>
            {isOpen === 'isOpenTwitter' && (
              <div className="accordion-content">
                { twiiterProfileData.map( ( twitter, index ) => (
                  <div className="twitter-profile social-profile">
                      <input type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'twitter', index, twitter?.id,twitter?.name, twitter?.type ) } />
                      <h3>{ twitter?.name } ( { twitter.type ? twitter.type : __('Profile','wp-scheduled-posts') } ) </h3>
                  </div>
                ) ) }
              </div>
            )}
          </div>
          <div className="social-accordion-item">
            <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenLinkedin')}>
                <img src={ WPSchedulePostsFree.assetsURI + '/images/linkedin.svg' } alt="" />
                <span>Linkedin</span>
            </div>
            {isOpen === 'isOpenLinkedin' && (
              <div className="accordion-content">
                  { linkedinProfileData.map( ( linkedin, index ) => (
                    <div className="linkedin-profile social-profile">
                        <input type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'linkedin', index, linkedin?.id, linkedin?.name, linkedin?.type ) } />
                        <h3>{ linkedin?.name } ( { linkedin?.type == 'organization' ? __('Page','wp-scheduled-posts') : __('Profile','wp-scheduled-posts')  } ) </h3>
                    </div>
                  ) ) }
              </div>
            )}
          </div>
          <div className="social-accordion-item">
              <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenPinterest')}>
                  <img src={ WPSchedulePostsFree.assetsURI + '/images/pinterest.svg' } alt="" />
                  <span>Pinterest</span>
              </div>
              {isOpen === 'isOpenPinterest' && (
                <div className="accordion-content">
                    { pinterestProfileData.map( ( pinterest, index ) => (
                      <div className="pinterest-profile social-profile">
                          <input type="checkbox" onClick={ (event) =>  handlePinterestProfileSelectionCheckbox( event, pinterest, index ) } />
                          <h3>{ pinterest?.default_board_name?.label } </h3>
                          <select className="pinterest-sections" onChange={ handleSectionChange }>
                            <option value="No Section">No Section</option>
                            { pinterest?.sections?.map( (section) => (
                              <option value={ section?.id }>{ section?.name }</option>
                            ) ) }
                          </select>
                      </div>
                    ) ) }
                </div>
              )}
            </div>
          { isOpenModal && (
            <Modal onRequestClose={ closeModal }>
              { selectedSocialProfile.filter( (profile) => profile.platform === 'facebook' ).length > 0 && 
              <div className="profile-facebook">
                  <h2>Facebook</h2>
                  { selectedSocialProfile.filter( (profile) => profile.platform === 'facebook' ).map( ( profile ) => (
                    <div className="profile-list">
                      { profile?.name }
                      { responseMessage.find( (item) => item.id === profile.id )?.id }
                    </div>
                  ) ) }
                </div>
              }
              { selectedSocialProfile.filter( (profile) => profile.platform === 'twitter' ).length > 0 && 
                <div className="profile-facebook">
                  <h2>Twitter</h2>
                  { selectedSocialProfile.filter( (profile) => profile.platform === 'twitter' ).map( ( profile ) => (
                    <div className="profile-list">
                      { profile?.name }
                    </div>
                  ) ) }
                </div>
              }
              { selectedSocialProfile.filter( (profile) => profile.platform == 'linkedin' ).length > 0 && 
                <div className="profile-linkedin">
                    <h2>Linkedin</h2>
                    { selectedSocialProfile.filter( (profile) => profile.platform === 'linkedin' ).map( ( profile ) => (
                      <div className="profile-list">
                        { profile?.name }
                      </div>
                    ) ) }
                </div>
              }
            </Modal>
          ) }
        </div>
        <button onClick={ handleShareNow } className="components-button is-primary share-btn">Share Now</button>
      </div>
    );
  };

export default SocialShare;