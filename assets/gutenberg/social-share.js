import { fetchPinterestSection, fetchSocialProfileData } from "./helper";
import SelectedSocialProfileModal from "./utils/SelectedSocialProfileModal";

const {
	data: { useSelect, useDispatch, select },
	plugins: { registerPlugin },
	date: { dateI18n },
	element: { useState, useEffect,createElement,Fragment },
	components: { TextControl,Button,DateTimePicker,Popover,Modal,RadioControl },
	editPost: { PluginDocumentSettingPanel },
} = wp;
const { __ } = wp.i18n;

const SocialShare = () => {
    const postid = select('core/editor').getCurrentPostId()
    const [ pinterestShareType, setPinterestShareType ] = useState( 'a' );

    const [isOpen, setIsOpen] = useState(null); // Use null to represent no accordion open
    const [facebookProfileData,setFacebookProfileData] = useState([]);
    const [twiiterProfileData,setTwitterProfileData] = useState([]);
    const [linkedinProfileData,setLinkedinProfileData] = useState([]);
    const [pinterestProfileData,setPinterestProfileData] = useState([]);
    const [isOpenModal,setIsOpenModal] = useState( false );
    const [selectedSocialProfile,setSelectedSocialProfile] = useState([]);
    const [responseMessage,setResponseMessage] = useState([]);
    const [selectedSection, setSelectedSection] = useState([]);

    // Get social profile data from wp_options table
    useEffect(() => {
      // fetch facebook profile data
      const optionName = `option_name=wpsp_settings_v5`;
      const apiUrl = '/wp-scheduled-posts/v1/get-option-data';
      fetchSocialProfileData(apiUrl,optionName, false).then( (res) => {
        const wpsp_settings = JSON.parse( res );
        if( wpsp_settings?.facebook_profile_status ) {
          const filtered_facebook_profile_list = wpsp_settings?.facebook_profile_list.filter( item => item.status === true );
          setFacebookProfileData( filtered_facebook_profile_list  );
        }
        if( wpsp_settings?.twitter_profile_status ) {
          const filtered_twitter_profile_list = wpsp_settings?.twitter_profile_list.filter( item => item.status === true );
          setTwitterProfileData( filtered_twitter_profile_list );
        }
        if( wpsp_settings?.linkedin_profile_status ) {
          const filtered_linkedin_profile_list = wpsp_settings?.linkedin_profile_list.filter( item => item.status === true );
          setLinkedinProfileData( filtered_linkedin_profile_list );
        }
        if( wpsp_settings?.pinterest_profile_status ) {
          let default_selected_section = [];
          let filtered_pinterest_profile_list = wpsp_settings?.pinterest_profile_list.filter( item => item.status === true );
          if( filtered_pinterest_profile_list.length > 0 ) {
            filtered_pinterest_profile_list.map( (pinterest_profile,index) => {
              let data = {
                defaultBoard: pinterest_profile?.default_board_name?.value,
                profile: pinterest_profile,
              };
              default_selected_section.push( { board_id : pinterest_profile?.default_board_name?.value, section_id : pinterest_profile?.defaultSection?.value  } );
              fetchPinterestSection(data).then( ( res ) => {
                filtered_pinterest_profile_list[index].sections = res.data;
              } )
            } )
          }
          setSelectedSection(default_selected_section);
          setPinterestProfileData([...filtered_pinterest_profile_list]);
        }
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
      console.log('selected-social-profile',selectedSocialProfile);
      if( selectedSocialProfile.length > 0 ) {
        selectedSocialProfile.map( ( profile ) => {
          profile.postid = postid;
          let queryParams = profile;
          const apiUrl = '/wp-scheduled-posts/v1/instant-social-share';
          fetchSocialProfileData(apiUrl,queryParams).then( (res) => {
            if( profile.id ) {
              responseMessage.push( { id: profile.id, message : JSON.stringify( res.data ), success : true } );
              setResponseMessage( [...responseMessage] );
            }
          } ).catch( (error) => {
            if( profile.id ) {
              responseMessage.push( { id: profile.id, message : JSON.stringify(error), success : false } );
              setResponseMessage( [...responseMessage] );
            }
          } )
        } )
      }
    }
    const closeModal = () => {
      setResponseMessage([]);
      setSelectedSection([]);
      setIsOpenModal( false )
    };
    
    // Handle profile selection
    const handleProfileSelectionCheckbox = ( event, platform, index, id, name, type, thumbnail_url = '' ) => {
      if( event.target.checked ) {
        setSelectedSocialProfile((prevData) => {
          if( id ) {
            return [...prevData,{ id, platform, platformKey: index, name, type, thumbnail_url } ]
          }
          return prevData;
        });
      }else{
        if( selectedSocialProfile.length > 0 ) {
          if( id ) {
            const filteredSelectedProfile = selectedSocialProfile.filter((item) => item.id !== id );
            setSelectedSocialProfile(filteredSelectedProfile);
          }
        }
      }
    }

    // Handle pinterest profile selection 
    const handlePinterestProfileSelectionCheckbox = ( event, pinterest, index, thumbnail_url = ''  ) => {
      if( event.target.checked ) {
        setSelectedSocialProfile((prevData) => {
          const board_id = pinterest?.default_board_name?.value;
          const board_name = pinterest?.default_board_name?.label;
          const findSection = selectedSection.find( ( item ) => item.board_id === board_id );
          if( pinterest?.default_board_name?.value ) {
            return [...prevData, { id : board_id, platform : 'pinterest', platformKey: index, pinterest_board_type : 'custom', pinterest_custom_board_name:  board_id,pinterest_custom_section_name : findSection?.section_id,name : board_name, thumbnail_url } ]
          }
          return prevData;
        });
      }else{
        if( selectedSocialProfile.length > 0 ) {
          const filteredSelectedProfile = selectedSocialProfile.filter((item) => item.id !== pinterest?.default_board_name?.value);
          setSelectedSocialProfile(filteredSelectedProfile);
        }
      }
    }

    // Handle section change event
    const handleSectionChange = (board_id,section_id) => {
      const updateSectionArray = selectedSection.map((item) => {
        if (item.board_id === board_id) {
          const updatedItem = { board_id,section_id };
          return { ...item, ...updatedItem };
        }
        return item;
      });
      setSelectedSection(updateSectionArray);
    }
    useEffect(() => {
      console.log('res',);
    }, [])
    
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
                { facebookProfileData.length > 0 ?
                  <Fragment>
                    { facebookProfileData.map( ( facebook, index ) => (
                      <div className="facebook-profile social-profile">
                          <input type="checkbox" checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === facebook.id ) != -1) ? true : false } onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'facebook', index, facebook?.id, facebook?.name, facebook?.type,facebook?.thumbnail_url ) } />
                          <h3>{ facebook?.name } ( { facebook.type ? facebook.type : __('Profile','wp-scheduled-posts') } ) </h3>
                      </div>
                    ) ) }
                  </Fragment>
                : "No profile found!!"
                }
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
                { twiiterProfileData.length > 0 ?
                  <Fragment>
                    { twiiterProfileData.map( ( twitter, index ) => (
                      <div className="twitter-profile social-profile">
                          <input checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === twitter.id ) != -1) ? true : false } type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'twitter', index, twitter?.id,twitter?.name, twitter?.type, twitter?.thumbnail_url ) } />
                          <h3>{ twitter?.name } ( { twitter.type ? twitter.type : __('Profile','wp-scheduled-posts') } ) </h3>
                      </div>
                    ) ) }
                  </Fragment>
                : "No profile found!!" }
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
                  { linkedinProfileData.length > 0 ?
                    <Fragment>
                      { linkedinProfileData.map( ( linkedin, index ) => (
                        <div className="linkedin-profile social-profile">
                            <input checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === linkedin.id ) != -1) ? true : false } type="checkbox" onClick={ (event) =>  handleProfileSelectionCheckbox( event, 'linkedin', index, linkedin?.id, linkedin?.name, linkedin?.type, linkedin?.thumbnail_url ) } />
                            <h3>{ linkedin?.name } ( { linkedin?.type == 'organization' ? __('Page','wp-scheduled-posts') : __('Profile','wp-scheduled-posts')  } ) </h3>
                        </div>
                      ) ) }
                    </Fragment>
                  : "No profile found!!"
                  }
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
                    <RadioControl
                        selected={ pinterestShareType }
                        options={ [
                            { label: 'Default', value: 'default' },
                            { label: 'Custom', value: 'custom' },
                        ] }
                        onChange={ ( value ) => setPinterestShareType( value ) }
                    />
                    { pinterestShareType === 'custom' && pinterestProfileData.map( ( pinterest, index ) => (
                      <div className="pinterest-profile social-profile">
                          <input checked={ ( selectedSocialProfile.findIndex( ( item ) => item.id === pinterest?.default_board_name?.value ) != -1 ) ? true : false } type="checkbox" onClick={ (event) =>  handlePinterestProfileSelectionCheckbox( event, pinterest, index, pinterest?.thumbnail_url) } />
                          <h3>{ pinterest?.default_board_name?.label } </h3>
                          <select className="pinterest-sections" onChange={ (event) =>  handleSectionChange(pinterest?.default_board_name?.value,event.target.value) }>
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
            <Modal className="social-share-modal" onRequestClose={ closeModal }>
              <SelectedSocialProfileModal platform="facebook" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } />
              <SelectedSocialProfileModal platform="twitter" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage }  />
              <SelectedSocialProfileModal platform="linkedin" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage }  />
              <SelectedSocialProfileModal platform="pinterest" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage }  />
            </Modal>
          ) }
        </div>
        <button onClick={ handleShareNow } className="components-button is-primary share-btn" disabled={ selectedSocialProfile.length > 0 ? false : true }>Share Now</button>
      </div>
    );
  };

export default SocialShare;