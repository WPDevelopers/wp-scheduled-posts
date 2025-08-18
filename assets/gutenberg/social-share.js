import { fetchPinterestSection, fetchSocialProfileData } from "./helper";
import ProModal from "./utils/ProModal";
import SelectedSocialProfileModal from "./utils/SelectedSocialProfileModal";

const {
	data: { useSelect, useDispatch, select },
	plugins: { registerPlugin },
	date: { dateI18n },
	element: { useState, useEffect,createElement,Fragment },
	components: { TextControl,Button,DateTimePicker,Popover,Modal,RadioControl },
	editPost: { PluginDocumentSettingPanel },
  media: { wp_get_attachment_image },
  editor : { MediaUpload, MediaUploadCheck },
} = wp;
const { __ } = wp.i18n;

const SocialShare = ( { is_pro_active, isSocialShareDisable } ) => {
    const {
      meta,
      meta: { _wpscppro_dont_share_socialmedia, _facebook_share_type, _google_business_share_type, _twitter_share_type, _linkedin_share_type, _pinterest_share_type, _selected_social_profile, _linkedin_share_type_page, _instagram_share_type, _medium_share_type, _threads_share_type },
    } = useSelect((select) => ({
      meta: select('core/editor').getEditedPostAttribute('meta') || {},
    }));
    const { editPost } = useDispatch('core/editor');
    const postid = select('core/editor').getCurrentPostId()
    const [isOpen, setIsOpen] = useState(null); // Use null to represent no accordion open
    const [facebookProfileData,setFacebookProfileData] = useState([]);
    const [instagramProfileData,setInstagramProfileData] = useState([]);
    const [twiiterProfileData,setTwitterProfileData] = useState([]);
    const [linkedinProfileData,setLinkedinProfileData] = useState([]);
    const [ mediumProfileData,setMediumProfileData] = useState([]);
    const [ threadsProfileData,setThreadsProfileData] = useState([]);
    const [ googleBusinessProfileData,setGoogleBusinessProfileData] = useState([]);
    const [pinterestProfileData,setPinterestProfileData] = useState([]);
    const [isOpenModal,setIsOpenModal] = useState( false );
    const [selectedSocialProfile,setSelectedSocialProfile] = useState( [] );
    const [responseMessage,setResponseMessage] = useState([]);
    const [selectedSection, setSelectedSection] = useState([]);
    const [facebookShareType,setFacebookShareType] = useState( _facebook_share_type ? _facebook_share_type : 'default' );
    const [instagramShareType,setInstagramShareType] = useState( _instagram_share_type ? _instagram_share_type : 'default' );
    const [mediumShareType,setMediumShareType] = useState( _medium_share_type ? _medium_share_type : 'default' );
    const [threadsShareType,setThreadsShareType] = useState( _threads_share_type ? _threads_share_type : 'default' );
    const [twitterShareType,setTwitterShareType] = useState( _twitter_share_type ? _twitter_share_type : 'default' );
    const [linkedinShareType,setLinkedinShareType] = useState( _linkedin_share_type ? _linkedin_share_type : 'default');
    const [googleBusinessShareType,setGoogleBusinessShareType] = useState( _google_business_share_type ? _google_business_share_type : 'default');
    const [ pinterestShareType, setPinterestShareType ] = useState( _pinterest_share_type ? _pinterest_share_type : 'default' );
    const [linkedinShareTypePage,setLinkedinShareTypePage] = useState( _linkedin_share_type_page ? _linkedin_share_type_page : 'default');
    const [wpspSettings,setWpspSettings] = useState(null);
    const [activeTab, setActiveTab] = useState('profile');
    const [proModal,setProModal] = useState(false);
    
    useEffect(() => {
      editPost({
        meta: {
          ...meta,
          _facebook_share_type       : facebookShareType,
          _instagram_share_type      : instagramShareType,
          _twitter_share_type        : twitterShareType,
          _linkedin_share_type       : linkedinShareType,
          _linkedin_share_type_page  : linkedinShareTypePage,
          _pinterest_share_type      : pinterestShareType,
          _medium_share_type         : mediumShareType,
          _threads_share_type        : threadsShareType,
          _google_business_share_type: googleBusinessShareType,
          _selected_social_profile   : selectedSocialProfile,
        },
      })
    }, [facebookShareType, instagramShareType, twitterShareType, linkedinShareType, pinterestShareType, selectedSocialProfile])
    
    // Get social profile data from wp_options table
    useEffect(() => {
      // fetch facebook profile data
      const apiUrl = '/wp-scheduled-posts/v1/get-option-data';
      fetchSocialProfileData(apiUrl,null, false).then( (res) => {
        const wpsp_settings = JSON.parse( res );
        if( wpsp_settings?.facebook_profile_status ) {
          const filtered_facebook_profile_list = wpsp_settings?.facebook_profile_list.filter( item => item.status === true );
          setFacebookProfileData( filtered_facebook_profile_list  );
        }
        if( wpsp_settings?.instagram_profile_status ) {
          const filtered_instagram_profile_list = wpsp_settings?.instagram_profile_list.filter( item => item.status === true );
          setInstagramProfileData( filtered_instagram_profile_list  );
        }
        if( wpsp_settings?.twitter_profile_status ) {
          const filtered_twitter_profile_list = wpsp_settings?.twitter_profile_list.filter( item => item.status === true );
          setTwitterProfileData( filtered_twitter_profile_list );
        }
        if( wpsp_settings?.linkedin_profile_status ) {
          const filtered_linkedin_profile_list = wpsp_settings?.linkedin_profile_list.filter( item => item.status === true );
          setLinkedinProfileData( filtered_linkedin_profile_list );
        }
        if( wpsp_settings?.medium_profile_status ) {
          const filtered_medium_profile_list = wpsp_settings?.medium_profile_list.filter( item => item.status === true );
          setMediumProfileData( filtered_medium_profile_list );
        }
        if( wpsp_settings?.threads_profile_status ) {
          const filtered_threads_profile_list = wpsp_settings?.threads_profile_list.filter( item => item.status === true );
          setThreadsProfileData( filtered_threads_profile_list );
        }
        if( wpsp_settings?.google_business_profile_status ) {
          const filtered_google_business_profile_list = wpsp_settings?.google_business_profile_list.filter( item => item.status === true );
          setGoogleBusinessProfileData( filtered_google_business_profile_list );
        }
        let default_selected_social_profile = [];
        if( wpsp_settings?.pinterest_profile_status ) {
          let default_selected_section = [];
          let filtered_pinterest_profile_list = wpsp_settings?.pinterest_profile_list.filter( item => item.status === true );
          if( filtered_pinterest_profile_list.length > 0 ) {
            filtered_pinterest_profile_list.map( (pinterest_profile,index) => {
              let data = {
                defaultBoard: pinterest_profile?.default_board_name?.value,
                profile: pinterest_profile,
              };
              let findSelectedPinterestIndex = selectedSocialProfile.findIndex( ( __selectedProfile ) => __selectedProfile?.id == pinterest_profile?.default_board_name?.value );
              if( findSelectedPinterestIndex != -1 ) {
                default_selected_section.push( { board_id : pinterest_profile?.default_board_name?.value, section_id : findSelectedPinterestIndex[findSelectedPinterestIndex].pinterest_custom_section_name  } );
              }
              default_selected_section.push( { board_id : pinterest_profile?.default_board_name?.value, section_id : pinterest_profile?.defaultSection?.value  } );
              fetchPinterestSection(data).then( ( res ) => {
                filtered_pinterest_profile_list[index].sections = res.data;
              } )
              default_selected_social_profile.push( { id : pinterest_profile?.default_board_name?.value, platform : 'pinterest', platformKey: index, pinterest_custom_board_name:  pinterest_profile?.default_board_name?.value, pinterest_custom_section_name : pinterest_profile?.defaultSection?.value , name : pinterest_profile?.default_board_name?.label, thumbnail_url : pinterest_profile?.thumbnail_url } );

            } )
          }
          setSelectedSection(default_selected_section);
          setPinterestProfileData([...filtered_pinterest_profile_list]);
        }
        setWpspSettings(wpsp_settings);

        // Set default selection for facebook
        if( wpsp_settings?.facebook_profile_status ) {
          let facebook_profile_list = wpsp_settings?.facebook_profile_list.filter( item => item.status === true );
          facebook_profile_list.map( (profile,index) => {
            default_selected_social_profile.push( { id: profile.id, platform: 'facebook', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : facebookShareType } );
          } )
        }
        // Set default selection for instagram
        if( wpsp_settings?.instagram_profile_status ) {
          let instagram_profile_list = wpsp_settings?.instagram_profile_list.filter( item => item.status === true );
          instagram_profile_list.map( (profile,index) => {
            default_selected_social_profile.push( { id: profile.id, platform: 'instagram', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : instagramShareType } );
          } )
        }
        // Set default selection for medium
        if( wpsp_settings?.medium_profile_status ) {
          let medium_profile_list = wpsp_settings?.medium_profile_list.filter( item => item.status === true );
          medium_profile_list.map( (profile,index) => {
            default_selected_social_profile.push( { id: profile.id, platform: 'medium', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : mediumShareType } );
          } )
        }
        // Set default selection for medium
        if( wpsp_settings?.threads_profile_status ) {
          let threads_profile_list = wpsp_settings?.threads_profile_list.filter( item => item.status === true );
          threads_profile_list.map( (profile,index) => {
            default_selected_social_profile.push( { id: profile.id, platform: 'threads', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : threadsShareType } );
          } )
        }
        // Handle twiiter default selection
        if( wpsp_settings?.twitter_profile_status ) {
          let twitter_profile_list = wpsp_settings?.twitter_profile_list.filter( item => item.status === true );
          twitter_profile_list.map( (profile,index) => {
            default_selected_social_profile.push( { id: profile.id, platform: 'twitter', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : twitterShareType } );
          } )
        }
        // Handle linkedin default selection
        if( wpsp_settings?.linkedin_profile_status ) {
          let linkedin_profile_list = wpsp_settings?.linkedin_profile_list.filter( item => item.status === true );
          linkedin_profile_list.map( (profile,index) => {
            default_selected_social_profile.push( { id: profile.id, platform: 'linkedin', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : linkedinShareType } );
          } )
        }
        // Handle linkedin default selection
        if( wpsp_settings?.google_business_profile_status ) {
          let google_business_profile_list = wpsp_settings?.google_business_profile_list.filter( item => item.status === true );
          google_business_profile_list.map( (profile,index) => {
            default_selected_social_profile.push( { id: profile.id, platform: 'google_business', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : googleBusinessShareType } );
          } )
        }
        if( _selected_social_profile.length > 0 ) {
          const default_object_ids = default_selected_social_profile.map(item => item.id);
          let _final_selected_social_profile = _selected_social_profile.filter(item => default_object_ids.includes(item.id)); 
          setSelectedSocialProfile( [..._final_selected_social_profile] );
        }else {
          setSelectedSocialProfile( [...default_selected_social_profile] );
        }
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
          if( profile?.pinterest_board_type && pinterestShareType !== profile?.pinterest_board_type ) {
            return;
          }
          profile.postid = postid;
          profile.nonce = WPSchedulePostsFree?.nonce;
          let queryParams = profile;
          const apiUrl = '/wp-scheduled-posts/v1/instant-social-share';
          fetchSocialProfileData(apiUrl,queryParams).then( (res) => {
            if( profile.id ) {
              responseMessage.push( { id: profile.id, message : JSON.stringify( res.data ), success : res.success } );
              setResponseMessage( [...responseMessage] );
            }
          } )
        } )
      }
    }
    const closeModal = () => {
      setResponseMessage([]);
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
    // Function to update the boards with new section IDs
    function updateBoardSections(boards, sectionsToUpdate) {
      return boards.map(board => {
          const update = sectionsToUpdate.find(section => section.board_id === board.id);
          if (update) {
              return {
                  ...board,
                  pinterest_custom_section_name: update.section_id,
              };
          }
          return board;
      });
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
      const updateSelectedProfile = updateBoardSections(selectedSocialProfile, updateSectionArray);
      setSelectedSocialProfile(updateSelectedProfile);
      setSelectedSection(updateSectionArray);
    }

    // Handle pinterest board type selection 
    const handlePinterestBoardTypeSelection = (value) => {
      let default_selected_social_profile = selectedSocialProfile;
      setPinterestShareType( value );
      if( value == 'default' ) {
        if( wpspSettings?.pinterest_profile_status ) {
          let default_selected_section = selectedSection;
          let filtered_pinterest_profile_list = wpspSettings?.pinterest_profile_list.filter( item => item.status === true );
          if( filtered_pinterest_profile_list.length > 0 ) {
            filtered_pinterest_profile_list.map( (pinterest_profile,index) => {
              if( selectedSocialProfile.findIndex((item) => item.id === pinterest_profile?.default_board_name?.value) === -1 ) {
                let data = {
                  defaultBoard: pinterest_profile?.default_board_name?.value,
                  profile: pinterest_profile,
                };
                default_selected_section.push( { board_id : pinterest_profile?.default_board_name?.value, section_id : pinterest_profile?.defaultSection?.value  } );
                fetchPinterestSection(data).then( ( res ) => {
                  filtered_pinterest_profile_list[index].sections = res.data;
                } )
                default_selected_social_profile.push( { id : pinterest_profile?.default_board_name?.value, platform : 'pinterest', platformKey: index, pinterest_custom_board_name:  pinterest_profile?.default_board_name?.value, pinterest_custom_section_name : pinterest_profile?.defaultSection?.value , name : pinterest_profile?.default_board_name?.label, thumbnail_url : pinterest_profile?.thumbnail_url } );
              }
            } )
          }
          setSelectedSection(default_selected_section);
          setPinterestProfileData([...filtered_pinterest_profile_list]);
        }
        setSelectedSocialProfile( [...default_selected_social_profile] );
      }
      
    }

    // Handle share type 
    const handleShareType = ( platform,value ) => {
      let default_selected_social_profile = selectedSocialProfile;
      // Handle share type instagram
      if( platform === 'instagram' ) {
        setInstagramShareType(value);
        if( value === 'default' ) {
          // Set default selection for instagram
          if( wpspSettings?.instagram_profile_status ) {
            let instagram_profile_list = wpspSettings?.instagram_profile_list.filter( item => item.status === true );
            instagram_profile_list.map( (profile,index) => {
              if( selectedSocialProfile.findIndex((item) => item.id === profile.id) === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'instagram', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : instagramShareType } );
              }
            } )
          }
        }
      }

      // Handle share type medium
      if( platform === 'medium' ) {
        setMediumShareType(value);
        if( value === 'default' ) {
          // Set default selection for medium
          if( wpspSettings?.medium_profile_status ) {
            let medium_profile_list = wpspSettings?.medium_profile_list.filter( item => item.status === true );
            medium_profile_list.map( (profile,index) => {
              if( selectedSocialProfile.findIndex((item) => item.id === profile.id) === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'medium', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : mediumShareType } );
              }
            } )
          }
        }
      }

      // Handle share type threads
      if( platform === 'threads' ) {
        setThreadsShareType(value);
        if( value === 'default' ) {
          // Set default selection for threads
          if( wpspSettings?.threads_profile_status ) {
            let threads_profile_list = wpspSettings?.threads_profile_list.filter( item => item.status === true );
            threads_profile_list.map( (profile,index) => {
              if( selectedSocialProfile.findIndex((item) => item.id === profile.id) === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'threads', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : threadsShareType } );
              }
            } )
          }
        }
      }

      // Handle share type google business
      if( platform === 'google_business' ) {
        setGoogleBusinessShareType(value);
        if( value === 'default' ) {
          // Set default selection for threads
          if( wpspSettings?.google_business_profile_status ) {
            let google_business_profile_list = wpspSettings?.google_business_profile_list.filter( item => item.status === true );
            google_business_profile_list.map( (profile,index) => {
              if( selectedSocialProfile.findIndex((item) => item.id === profile.id) === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'google_business', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : googleBusinessShareType } );
              }
            } )
          }
        }
      }

      // Handle share type facebook
      if( platform === 'facebook' ) {
        setFacebookShareType(value);
        if( value === 'default' ) {
          // Set default selection for facebook
          if( wpspSettings?.facebook_profile_status ) {
            let facebook_profile_list = wpspSettings?.facebook_profile_list.filter( item => item.status === true );
            facebook_profile_list.map( (profile,index) => {
              if( selectedSocialProfile.findIndex((item) => item.id === profile.id) === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'facebook', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : facebookShareType } );
              }
            } )
          }
        }
      }
      // Handle twiiter default selection
      if( platform === 'twitter' ) {
        setTwitterShareType(value)
        if( value == 'default' ) {
          if( wpspSettings?.twitter_profile_status ) {
            let twitter_profile_list = wpspSettings?.twitter_profile_list.filter( item => item.status === true );
            twitter_profile_list.map( (profile,index) => {
              const checkTwitterExists = selectedSocialProfile.findIndex( ( item ) => item.id === profile.id );
              if( checkTwitterExists === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'twitter', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : twitterShareType } );
              }
            } )
          }
        }
      }
      
      // Handle linkedin default selection
      if( platform === 'linkedin' ) {
        setLinkedinShareType(value)
        if( value == 'default' ) {
          if( wpspSettings?.linkedin_profile_status ) {
            let linkedin_profile_list = wpspSettings?.linkedin_profile_list.filter( item => item.status === true && item.type === 'person' );
            linkedin_profile_list.map( (profile,index) => {
              const checkLinkedinExists = selectedSocialProfile.findIndex( ( item ) => item.id === profile.id );
              if( checkLinkedinExists === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'linkedin', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : linkedinShareType } );
              } 
            } )
          }
        }
      }

      // Handle linkedin default selection
      if( platform === 'linkedin_page' ) {
        setLinkedinShareTypePage(value)
        if( value == 'default' ) {
          if( wpspSettings?.linkedin_profile_status ) {
            let linkedin_page_list = wpspSettings?.linkedin_profile_list.filter( item => item.status === true && item.type !== 'person' );
            linkedin_page_list.map( (profile,index) => {
              const checkLinkedinExists = selectedSocialProfile.findIndex( ( item ) => item.id === profile.id );
              if( checkLinkedinExists === -1 ) {
                default_selected_social_profile.push( { id: profile.id, platform: 'linkedin', platformKey: index, name : profile.name, type : profile?.type, thumbnail_url : profile.thumbnail_url, share_type : linkedinShareType } );
              }
            } )
          }
        }
      }
      setSelectedSocialProfile( [...default_selected_social_profile] );
    }
    const handleActiveLinkedinPage = (page) => {
      if( !is_pro_active ) {
        setProModal(true);
      }else{
        setActiveTab(page);
      }
    };    

    return (
      <div className={`social-share`} style={ { display: isSocialShareDisable ? 'none' : 'block' } } id="wpspSocialShare">
        { (!isSocialShareDisable) && 
          <Fragment>
            <div className="social-share-wrapper">
              <h3>{ __('Choose Social Share Platform','wp-scheduled-posts') }</h3>
                <div className="social-accordion-item">
                  <div className="social-accordion-button" onClick={() => toggleAccordion('isOpen')}>
                      <img src={ WPSchedulePostsFree.assetsURI + '/images/facebook.svg' } alt="" />
                      <span>Facebook</span>
                  </div>
                  { isOpen === 'isOpen' && (
                    <div className="accordion-content">
                      { facebookProfileData.length > 0 ?
                        <Fragment>
                          <div className="facebook-share-type">
                            <RadioControl
                              selected={ facebookShareType }
                              options={ [
                                  { label: <div className="wpsp-tooltip">Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext"> { __('Content will be shared on all the activated social accounts','wp-scheduled-posts') } </span> </div>, value: 'default' },
                                  { label: <div className="wpsp-tooltip custom">Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">{ __('Specify your social account choice where you want to share the content','wp-scheduled-posts') }</span></div>, value: 'custom' }
                              ] }
                              onChange={ ( value ) => handleShareType( 'facebook', value ) }
                            />
                          </div>
                          { facebookShareType === 'custom' && facebookProfileData.map( ( facebook, index ) => (
                            <div className="facebook-profile social-profile">
                              <input type="checkbox" checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === facebook.id ) != -1) ? true : false } onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'facebook', index, facebook?.id, facebook?.name, facebook?.type,facebook?.thumbnail_url ) } />
                              <h3>{ facebook?.name } ( { facebook.type ? facebook.type : __('Profile','wp-scheduled-posts') } ) </h3>
                            </div>
                          ) ) }
                        </Fragment>
                      : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div>
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
                        <RadioControl
                          selected={ twitterShareType }
                          options={ [
                                { label: <div className="wpsp-tooltip" dangerouslySetInnerHTML={ { __html: `Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Content will be shared on all the activated social accounts </span>` } }></div>, value: 'default' },
                                { label: <div className="wpsp-tooltip custom" dangerouslySetInnerHTML={ { __html: `Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Specify your social account choice where you want to share the content</span>` } }></div>, value: 'custom' }
                          ] }
                          onChange={ ( value ) => handleShareType( 'twitter', value ) }
                        />
                        { twitterShareType === 'custom' && twiiterProfileData.map( ( twitter, index ) => (
                          <div className="twitter-profile social-profile">
                              <input checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === twitter.id ) != -1) ? true : false } type="checkbox" onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'twitter', index, twitter?.id,twitter?.name, twitter?.type, twitter?.thumbnail_url ) } />
                              <h3>{ twitter?.name } ( { twitter.type ? twitter.type : __('Profile','wp-scheduled-posts') } ) </h3>
                          </div>
                        ) ) }
                      </Fragment>
                    : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div> }
                    </div>
                  )}
                </div>
                <div className="social-accordion-item">
                  <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenLinkedin')}>
                      <img src={ WPSchedulePostsFree.assetsURI + '/images/linkedin.svg' } alt="" />
                      <span>Linkedin</span>
                  </div>
                  {isOpen === 'isOpenLinkedin' && (
                    <Fragment>
                      <div className="accordion-content">
                        <div className="wpsp-custom-tabs">
                          <div className="wpsp-tab-header">
                            <div className={ `tab-profile ${ activeTab === 'profile' ? 'active' : '' }` } onClick={ () => setActiveTab('profile') }>{ __('Profile','wp-scheduled-posts') }</div>
                            <div className={ `tab-page ${ activeTab === 'page' ? 'active' : '' } ${ !is_pro_active ? 'pro-deactivated' : '' }` } onClick={ () => handleActiveLinkedinPage('page') }>{ __('Page','wp-scheduled-posts') }</div>
                          </div>
                          <div className="wpsp-tab-content">
                            { activeTab == 'profile' && 
                              <div className="content-profile">
                                { linkedinProfileData.filter((item) => item.type === 'person').length > 0 ?
                                  <Fragment>
                                    <RadioControl
                                      selected={ linkedinShareType }
                                      options={ [
                                        { label: <div className="wpsp-tooltip" dangerouslySetInnerHTML={ { __html: `Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Content will be shared on all the activated social accounts </span>` } }></div>, value: 'default' },
                                      { label: <div className="wpsp-tooltip custom" dangerouslySetInnerHTML={ { __html: `Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Specify your social account choice where you want to share the content</span>` } }></div>, value: 'custom' }
                                      ] }
                                      onChange={ ( value ) => handleShareType( 'linkedin', value ) }
                                    />
                                    { linkedinShareType === 'custom' && linkedinProfileData.filter((item) => item.type === 'person').map( ( linkedin, index ) => (
                                      <div className="linkedin-profile social-profile">
                                          <input checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === linkedin.id ) != -1) ? true : false } type="checkbox" onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'linkedin', index, linkedin?.id, linkedin?.name, linkedin?.type, linkedin?.thumbnail_url ) } />
                                          <h3>{ linkedin?.name } ( { linkedin?.type == 'organization' ? __('Page','wp-scheduled-posts') : __('Profile','wp-scheduled-posts')  } ) </h3>
                                      </div>
                                    ) ) }
                                  </Fragment>
                                  : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div>
                                }
                              </div>
                            }
                            { activeTab == 'page' &&
                              <div className="content-page">
                                { linkedinProfileData.filter((item) => item.type !== 'person').length > 0 ?
                                  <Fragment>
                                    <RadioControl
                                      selected={ linkedinShareTypePage }
                                      options={ [
                                        { label: <div className="wpsp-tooltip" dangerouslySetInnerHTML={ { __html: `Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Content will be shared on all the activated social accounts </span>` } }></div>, value: 'default' },
                                        { label: <div className="wpsp-tooltip custom" dangerouslySetInnerHTML={ { __html: `Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Specify your social account choice where you want to share the content</span>` } }></div>, value: 'custom' }
                                      ] }
                                      onChange={ ( value ) => handleShareType( 'linkedin_page', value ) }
                                    />
                                    { linkedinShareTypePage === 'custom' && linkedinProfileData.filter((item) => item.type !== 'person').map( ( linkedin, index ) => (
                                      <div className="linkedin-profile social-profile">
                                          <input checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === linkedin.id ) != -1) ? true : false } type="checkbox" onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'linkedin', index, linkedin?.id, linkedin?.name, linkedin?.type, linkedin?.thumbnail_url ) } />
                                          <h3>{ linkedin?.name } ( { linkedin?.type == 'organization' ? __('Page','wp-scheduled-posts') : __('Profile','wp-scheduled-posts')  } ) </h3>
                                      </div>
                                    ) ) }
                                  </Fragment>
                                  : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div>
                                }
                              </div>
                            }
                          </div>
                        </div>
                      </div>
                    </Fragment>
                  )}
                </div>
                <div className="social-accordion-item">
                  <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenPinterest')}>
                      <img src={ WPSchedulePostsFree.assetsURI + '/images/pinterest.svg' } alt="" />
                      <span>Pinterest</span>
                  </div>
                  {isOpen === 'isOpenPinterest' && (
                    <div className="accordion-content">
                      { pinterestProfileData.length > 0 ?
                      <Fragment>
                        <RadioControl
                          selected={ pinterestShareType }
                          options={ [
                                { label: <div className="wpsp-tooltip" dangerouslySetInnerHTML={ { __html: `Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Content will be shared on all the activated social accounts </span>` } }></div>, value: 'default' },
                                { label: <div className="wpsp-tooltip custom" dangerouslySetInnerHTML={ { __html: `Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">Specify your social account choice where you want to share the content</span>` } }></div>, value: 'custom' }
                          ] }
                          onChange={ ( value ) => handlePinterestBoardTypeSelection( value ) }
                        />
                        { pinterestShareType === 'custom' && pinterestProfileData.map( ( pinterest, index ) => (
                          <div className="pinterest-profile social-profile">
                              <input checked={ ( selectedSocialProfile.findIndex( ( item ) => item.id === pinterest?.default_board_name?.value ) != -1 ) ? true : false } type="checkbox" onClick={ (event) =>  handlePinterestProfileSelectionCheckbox( event, pinterest, index, pinterest?.thumbnail_url) } />
                              <h3>{ pinterest?.default_board_name?.label } </h3>
                              <select className="pinterest-sections" onChange={ (event) =>  handleSectionChange(pinterest?.default_board_name?.value,event.target.value) }>
                                <option value="No Section">No Section</option>
                                { pinterest?.sections?.map((section, key ) => {
                                  const isSelectedBasedOnProfile = selectedSocialProfile.findIndex((_item) => _item.id == pinterest?.default_board_name?.value);
                                  const isSelectedBasedOnSection = selectedSection.findIndex((__item) => __item.board_id === pinterest?.default_board_name?.value);
                                  if( isSelectedBasedOnProfile != -1 ) {
                                    return (
                                      <option value={section?.id} selected={ selectedSocialProfile[isSelectedBasedOnProfile]?.pinterest_custom_section_name == section?.id ? true : false } > {section?.name} </option>
                                    );
                                  }else {
                                    return (
                                      <option value={section?.id} selected={ selectedSocialProfile[isSelectedBasedOnSection]?.pinterest_custom_section_name == section?.id ? true : false } > {section?.name} </option>
                                    );
                                  }
                                })}
                              </select>
                          </div>
                        ) ) }
                      </Fragment>
                     : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div> }
                    </div>
                  )}
                </div>
                <div className="social-accordion-item">
                  <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenInstagram')}>
                      <img src={ WPSchedulePostsFree.assetsURI + '/images/instagram.png' } alt="" />
                      <span>{ __('Instagram', 'wp-scheduled-posts') }</span>
                  </div>
                  { isOpen === 'isOpenInstagram' && (
                    <div className="accordion-content">
                      { instagramProfileData.length > 0 ?
                        <Fragment>
                          <div className="instagram-share-type">
                            <RadioControl
                              selected={ instagramShareType }
                              options={ [
                                  { label: <div className="wpsp-tooltip">Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext"> { __('Content will be shared on all the activated social accounts','wp-scheduled-posts') } </span> </div>, value: 'default' },
                                  { label: <div className="wpsp-tooltip custom">Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">{ __('Specify your social account choice where you want to share the content','wp-scheduled-posts') }</span></div>, value: 'custom' }
                              ] }
                              onChange={ ( value ) => handleShareType( 'instagram', value ) }
                            />
                          </div>
                          { instagramShareType === 'custom' && instagramProfileData.map( ( instagram, index ) => (
                            <div className="instagram-profile social-profile">
                              <input type="checkbox" checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === instagram.id ) != -1) ? true : false } onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'instagram', index, instagram?.id, instagram?.name, instagram?.type,instagram?.thumbnail_url ) } />
                              <h3>{ instagram?.name } ( { instagram.type ? instagram.type : __('Profile','wp-scheduled-posts') } ) </h3>
                            </div>
                          ) ) }
                        </Fragment>
                      : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div>
                      }
                    </div>
                  )}
                </div>
                <div className="social-accordion-item">
                  <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenMedium')}>
                      <img src={ WPSchedulePostsFree.assetsURI + '/images/icon-medium-small-white.png' } alt="" />
                      <span>{ __('Medium', 'wp-scheduled-posts') }</span>
                  </div>
                  { isOpen === 'isOpenMedium' && (
                    <div className="accordion-content">
                      { mediumProfileData.length > 0 ?
                        <Fragment>
                          <div className="instagram-share-type">
                            <RadioControl
                              selected={ mediumShareType }
                              options={ [
                                  { label: <div className="wpsp-tooltip">Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext"> { __('Content will be shared on all the activated social accounts','wp-scheduled-posts') } </span> </div>, value: 'default' },
                                  { label: <div className="wpsp-tooltip custom">Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">{ __('Specify your social account choice where you want to share the content','wp-scheduled-posts') }</span></div>, value: 'custom' }
                              ] }
                              onChange={ ( value ) => handleShareType( 'medium', value ) }
                            />
                          </div>
                          { mediumShareType === 'custom' && mediumProfileData.map( ( medium, index ) => (
                            <div className="medium-profile social-profile">
                              <input type="checkbox" checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === medium.id ) != -1) ? true : false } onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'medium', index, medium?.id, medium?.name, medium?.type,medium?.thumbnail_url ) } />
                              <h3>{ medium?.name } ( { medium.type ? medium.type : __('Profile','wp-scheduled-posts') } ) </h3>
                            </div>
                          ) ) }
                        </Fragment>
                      : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div>
                      }
                    </div>
                  )}
                </div>
                <div className="social-accordion-item">
                  <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenThreads')}>
                      <img src={ WPSchedulePostsFree.assetsURI + '/images/icon-threads-small-white.png' } alt="" />
                      <span>{ __('Threads', 'wp-scheduled-posts') }</span>
                  </div>
                  { isOpen === 'isOpenThreads' && (
                    <div className="accordion-content">
                      { threadsProfileData.length > 0 ?
                        <Fragment>
                          <div className="instagram-share-type">
                            <RadioControl
                              selected={ threadsShareType }
                              options={ [
                                  { label: <div className="wpsp-tooltip">Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext"> { __('Content will be shared on all the activated social accounts','wp-scheduled-posts') } </span> </div>, value: 'default' },
                                  { label: <div className="wpsp-tooltip custom">Select <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">{ __('Specify your social account choice where you want to share the content','wp-scheduled-posts') }</span></div>, value: 'custom' }
                              ] }
                              onChange={ ( value ) => handleShareType( 'threads', value ) }
                            />
                          </div>
                          { threadsShareType === 'custom' && threadsProfileData.map( ( medium, index ) => (
                            <div className="medium-profile social-profile">
                              <input type="checkbox" checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === medium.id ) != -1) ? true : false } onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'threads', index, medium?.id, medium?.name, medium?.type,medium?.thumbnail_url ) } />
                              <h3>{ medium?.name } ( { medium.type ? medium.type : __('Profile','wp-scheduled-posts') } ) </h3>
                            </div>
                          ) ) }
                        </Fragment>
                      : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div>
                      }
                    </div>
                  )}
                </div>
                <div className="social-accordion-item">
                  <div className="social-accordion-button" onClick={() => toggleAccordion('isOpenGoogleBusiness')}>
                      <img src={ WPSchedulePostsFree.assetsURI + '/images/google-my-business-logo-small.png' } alt="" />
                      <span>{ __('Google Business Profile', 'wp-scheduled-posts') }</span>
                  </div>
                  { isOpen === 'isOpenGoogleBusiness' && (
                    <div className="accordion-content">
                      { googleBusinessProfileData.length > 0 ?
                        <Fragment>
                          <div className="instagram-share-type">
                            <RadioControl
                              selected={ googleBusinessShareType }
                              options={ [
                                  { label: <div className="wpsp-tooltip">Default <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext"> { __('Content will be shared on all the activated social accounts','wp-scheduled-posts') } </span> </div>, value: 'default' },
                                  { label: <div className="wpsp-tooltip custom">Custom <span class="dashicons dashicons-info"></span><span class="wpsp-tooltiptext">{ __('Specify your social account choice where you want to share the content','wp-scheduled-posts') }</span></div>, value: 'custom' }
                              ] }
                              onChange={ ( value ) => handleShareType( 'google_business', value ) }
                            />
                          </div>
                          { googleBusinessShareType === 'custom' && googleBusinessProfileData.map( ( medium, index ) => (
                            <div className="medium-profile social-profile">
                              <input type="checkbox" checked={ (selectedSocialProfile.findIndex( ( item ) => item.id === medium.id ) != -1) ? true : false } onChange={ (event) =>  handleProfileSelectionCheckbox( event, 'google_business', index, medium?.id, medium?.name, medium?.type,medium?.thumbnail_url ) } />
                              <h3>{ medium?.name } ( { medium.type ? medium.type : __('Profile','wp-scheduled-posts') } ) </h3>
                            </div>
                          ) ) }
                        </Fragment>
                      : <div dangerouslySetInnerHTML={ { __html: `You may forget to add or enable profile/page from <a href='${WPSchedulePostsFree?.adminURL}admin.php?page=schedulepress&tab=social-profile'>SchedulePress settings</a>.` } }></div>
                      }
                    </div>
                  )}
                </div>

              { isOpenModal && (
                <Modal className="social-share-modal" onRequestClose={ closeModal }>
                  <SelectedSocialProfileModal platform="facebook" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                  <SelectedSocialProfileModal platform="twitter" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                  <SelectedSocialProfileModal platform="linkedin" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                  <SelectedSocialProfileModal platform="pinterest" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                  <SelectedSocialProfileModal platform="instagram" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                  <SelectedSocialProfileModal platform="medium" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                  <SelectedSocialProfileModal platform="threads" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                  <SelectedSocialProfileModal platform="google_business" selectedSocialProfile={ selectedSocialProfile } responseMessage={ responseMessage } pinterest_board_type={pinterestShareType} />
                </Modal>
              ) }
            </div>

            <button onClick={ handleShareNow } className="components-button is-primary share-btn" disabled={ selectedSocialProfile.length > 0 ? false : true }>{ __('Share Now','wp-scheduled-posts') }</button>
          </Fragment>
        }
        <ProModal isOpenModal={ proModal } setProModal={setProModal} />
      </div>
    );
  };

export default SocialShare;