import { fetchSocialProfileData } from "./helper";

const {
	data: { useSelect, useDispatch, select },
	plugins: { registerPlugin },
	date: { dateI18n },
	element: { useState, useEffect,createElement,Fragment },
	components: { TextControl,Button,DateTimePicker,Popover,Modal },
	editPost: { PluginDocumentSettingPanel },
} = wp;

const SocialShare = () => {
    const postid = select('core/editor').getCurrentPostId()

    const [isOpen, setIsOpen] = useState(null); // Use null to represent no accordion open
    const [facebookProfileData,setFacebookProfileData] = useState([]);
    const [twiiterProfileData,setTwitterProfileData] = useState([]);
    const [linkedinProfileData,setLinkedinProfileData] = useState([]);
    const [isOpenModal,setIsOpenModal] = useState( false );
    const [selectedSocialProfile,setSelectedSocialProfile] = useState([]);
    const [isCheckedFacebook, setIsCheckedFacebook] = useState(false);

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
            console.log('res',res);
          } ).catch( (error) => {
            console.log('error',error);
          } )
        } )
      }
    }
    const closeModal = () => setIsOpenModal( false );

    // Handle main profile selection
    const handleMailProfileSelection = (event,platform) => {
      setIsCheckedFacebook(!isCheckedFacebook);
    }

    // Handle profile selection
    const handleProfileSelectionCheckbox = ( event, platform, index,id ) => {
      if( event.target.checked ) {
        setSelectedSocialProfile((prevData) => {
          prevData.push( { id : id, platform : platform,platformKey: index } );
          return prevData;
        });
      }else{
        if( selectedSocialProfile.length > 0 ) {
          const filteredSelectedProfile = selectedSocialProfile.filter((item) => item.id !== id);
          setSelectedSocialProfile(filteredSelectedProfile);
        }
      }
    }

    useEffect(() => {
      console.log('sele',selectedSocialProfile);
    }, [selectedSocialProfile])
    
    return (
      <div className='social-share'>
        <h3>Choose Social Share Platform</h3>
        <div className="social-accordion-item">
            <div className="social-accordion-button">
                <input type="checkbox" onClick={ (event) => handleMailProfileSelection( event, 'facebook' ) }  />
                <img src={ WPSchedulePostsFree.assetsURI + '/images/facebook.svg' } alt="" />
                <button className="accordion-button" onClick={() => toggleAccordion('isOpen')}>Facebook</button>
            </div>
          { isOpen === 'isOpen' && (
            <div className="accordion-content">
                { facebookProfileData.map( ( facebook, index ) => (
                  <div className="facebook-profile social-profile">
                      <input type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'facebook', index, facebook.id ) } />
                      <h3>{ facebook?.name } ( { facebook?.type } ) </h3>
                  </div>
                ) ) }
            </div>
          )}
        </div>
        <div className="accordion-item">
            <div className="accordion-button">
                <input type="checkbox" />
                <img src={ WPSchedulePostsFree.assetsURI + '/images/twitter.svg' } alt="" />
                <button className="accordion-button" onClick={() => toggleAccordion('isOpenTwitter')}>Twitter</button>
            </div>
          {isOpen === 'isOpenTwitter' && (
            <div className="accordion-content">
                { twiiterProfileData.map( ( twitter, index ) => (
                  <div className="twitter-profile social-profile">
                      <input type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'twitter', index, twitter.id ) } />
                      <h3>{ twitter?.name } ( { twitter?.type } ) </h3>
                  </div>
                ) ) }
            </div>
          )}
        </div>
        <div className="accordion-item">
            <div className="accordion-button">
                <input type="checkbox" />
                <img src={ WPSchedulePostsFree.assetsURI + '/images/linkedin.svg' } alt="" />
                <button className="accordion-button" onClick={() => toggleAccordion('isOpenTwitter')}>Linkedin</button>
            </div>
          {isOpen === 'isOpenTwitter' && (
            <div className="accordion-content">
                { twiiterProfileData.map( ( twitter, index ) => (
                  <div className="twitter-profile social-profile">
                      <input type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'twitter', index, twitter.id ) } />
                      <h3>{ twitter?.name } ( { twitter?.type } ) </h3>
                  </div>
                ) ) }
            </div>
          )}
        </div>
        { isOpenModal && (
          <Modal title="This is my modal" onRequestClose={ closeModal }>
              <div className="profile-facebook">
                  <h2>Facebook</h2>
                  { facebookProfileData.map( ( profile ) => (
                    <div className="profile-list">
                      { profile?.name }
                    </div>
                  ) ) }
                  
              </div>
          </Modal>
        ) }
        <button onClick={ handleShareNow }>Share Now</button>
      </div>
    );
  };

export default SocialShare;