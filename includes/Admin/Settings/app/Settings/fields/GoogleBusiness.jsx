import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';
import Modal from 'react-modal';
import Swal from 'sweetalert2';
import { SweetAlertDeleteMsg, SweetAlertProMsg } from '../ToasterMsg';
import { socialProfileRequestHandler } from '../helper/helper';
import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import SocialModal from './Modals/SocialModal';
import SelectedProfile from './utils/SelectedProfile';
import GoogleBusinessProfile from './Profiles/GoogleBusinessProfile';
import ProAlert from "./utils/ProAlert";

const GoogleBusiness = (props) => {
  const propsValue = props?.value || [];
  const sortedSelectedValue  = [...propsValue].sort((a, b) => {
    return b.status - a.status; // Sort in descending order by status
  });
  const cachedLocalData = JSON.parse(localStorage.getItem('google_business'));
  const builderContext = useBuilderContext();
  const [apiCredentialsModal, setApiCredentialsModal] = useState(false);
  const [platform, setPlatform] = useState('');
  const [selectedProfile, setSelectedProfile] = useState( sortedSelectedValue ?? [] );
  const [selectedProfileViewMore, setSelectedProfileViewMore] = useState(false);
  const [cachedStatus, setCashedStatus] = useState(cachedLocalData ?? {});
  const [profileStatus, setProfileStatus] = useState(
    builderContext?.savedValues?.google_business_profile_status
  );
  localStorage.setItem('google_business',JSON.stringify(cachedStatus));

  // prepare appId and appSecret
  let appInfo = [];
  if( props?.value ) {
    props?.value?.map( ( profile ) => {
      if( profile['app_id'] && profile['app_secret'] ) {
        appInfo['app_id'] = profile['app_id'];
        appInfo['app_secret'] = profile['app_secret'];
      }
    } );
  }
  
  const [activeStatusCount,setActiveStatusCount] = useState(0);
  
  // @ts-ignore
  const is_pro = wpspSettingsGlobal?.pro_version ? true : false;
  

  // Open and Close API credentials modal
  const openApiCredentialsModal = () => {
    if( !is_pro ) {
      SweetAlertProMsg();
      return;
    }
    setPlatform('google_business');
    setApiCredentialsModal(true);
  };

  const closeApiCredentialsModal = () => {
    setApiCredentialsModal(false);
  };

  // Handle profile & selected profile status onChange event
  const handleProfileStatusChange = (event) => {    
    if( !is_pro && event.target.checked ) {
      SweetAlertProMsg();
      return;
    }
    setProfileStatus(event.target.checked);
    const changeProfileStatus = selectedProfile.map((selectedItem) => {
      if (!event.target.checked) {
        setCashedStatus((prevStatus) => {
          return { ...prevStatus, [selectedItem.id]: selectedItem?.status };
        });
        return {
          ...selectedItem,
          status: false,
        };
      } else {
        return {
          ...selectedItem,
          status : (cachedStatus?.[selectedItem.id] == undefined) ?  false : cachedStatus?.[selectedItem.id], 
        };
      }
    });
    setSelectedProfile(changeProfileStatus);
  };
  
  
    const handleSelectedProfileStatusChange = (item, event) => {
        if( !is_pro ){
          SweetAlertProMsg();
          return;
        }
        if (event.target.checked) {
        setProfileStatus(true);
        }
        setCashedStatus((prevStatus) => {
        if( is_pro ) {
            return { ...prevStatus, [item.id]: event.target.checked };
        }else{
            return { [item.id]: event.target.checked };
        }
        });
        if ( is_pro ) {
        const updatedData = selectedProfile.map((selectedItem) => {
            if (selectedItem.id === item.id) {
            return {
                ...selectedItem,
                status: event.target.checked,
            };
            }
            return selectedItem;
        });
        setSelectedProfile(updatedData);
        }else{
        if( activeStatusCount <= 1 ) {
            let currentStatus = event.target.checked;
            if( activeStatusCount === 1 && currentStatus ) {
            Swal.fire({
                title: __('Are you sure?','wp-scheduled-posts'),
                text: __('Enabling this profile will deactivate other profile automatically.','wp-scheduled-posts'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: '<i class="wpsp-icon wpsp-close"></i>',
                confirmButtonText: __('Yes, Enable it!', 'wp-scheduled-posts'),
            }).then((result) => {
                if (result.isConfirmed) {
                    const updatedData = selectedProfile.map((selectedItem) => {
                    return {
                        ...selectedItem,
                        status: selectedItem.id === item.id ? currentStatus : false,
                    };
                });
                setSelectedProfile(updatedData);
                }
            })
            }else{
            const updatedData = selectedProfile.map((selectedItem) => {
                return {
                    ...selectedItem,
                    status: selectedItem.id === item.id ? currentStatus : false,
                };
            });
            setSelectedProfile(updatedData);
            }
        }
        }
    };

    const deleteSelectedProfile = (item) => {
        const updateSelectedProfile = selectedProfile.filter(
          (selectedItem) => selectedItem.id !== item.id
        );
        setSelectedProfile(updateSelectedProfile);
    };

    const handleDeleteSelectedProfile = (item) => {
        SweetAlertDeleteMsg({ item }, deleteSelectedProfile);
    };

  // Save selected profile data
  useEffect(() => {
    builderContext.setFieldValue([props.name], selectedProfile);
    let count = 0;
    if( selectedProfile ) {
      selectedProfile.forEach(element => {
        if( element.status ) {
            count++;
        }
        setActiveStatusCount( count );
      });
    }
  }, [selectedProfile]);

  // Save profile status data
  let { onChange } = props;
  useEffect(() => {
    onChange({
      target: {
        type: 'checkbox-select',
        name: 'google_business_profile_status',
        value: profileStatus,
      },
    });
  }, [profileStatus]);
  let selectedProfileData = [];
  if (selectedProfile && selectedProfileViewMore) {
    selectedProfileData = selectedProfile;
  } else if (selectedProfile && !selectedProfileViewMore) {
    selectedProfileData = selectedProfile.slice(0, 2);
  }

  return (
    <div
      className={classNames(
        'wprf-control',
        'wprf-social-profile',
        `wprf-${props.name}-social-profile`,
        props?.classes
      )}>
      <div className="social-profile-card" onClick={(event) => {
        if( !is_pro ) {
          event.stopPropagation();
          SweetAlertProMsg();
        }
      }}>
        { !is_pro && <img
          className="wpsppro-icon"
          /* @ts-ignore */
          src={`${wpspSettingsGlobal?.image_path}google-business-pro.svg`}
          alt="ProIcon"
        /> }

        <div className="main-profile">
          <GoogleBusinessProfile
            props={props}
            handleProfileStatusChange={handleProfileStatusChange}
            profileStatus={profileStatus}
            openApiCredentialsModal={openApiCredentialsModal}
          />
        </div>
        <div className="selected-profile">
          {(!selectedProfile || selectedProfile.length == 0) && (
            <img
              className="empty-image"
              /* @ts-ignore */
              src={`${wpspSettingsGlobal?.image_path}EmptyCard.svg`}
              alt="mainLogo"
            />
          )}
          <div className="selected-google-business-scrollbar">
            {selectedProfile &&
                selectedProfileData.map((item, index) => (
                <div
                    className="selected-facebook-wrapper"
                    key={index}>
                    <SelectedProfile
                        platform={'google_business'}
                        item={item}
                        handleSelectedProfileStatusChange={
                            handleSelectedProfileStatusChange
                        }
                        handleDeleteSelectedProfile={handleDeleteSelectedProfile}
                        handleEditSelectedProfile={''}
                        profileStatus={profileStatus}
                    />
                </div>
                ))}
          </div>
        </div>
      </div>
      <Modal
            isOpen={apiCredentialsModal}
            onRequestClose={closeApiCredentialsModal}
            ariaHideApp={false}
            shouldCloseOnOverlayClick={false}
            className="modal_wrapper">
            <button
              className="close-button"
              onClick={closeApiCredentialsModal}>
              <i className="wpsp-icon wpsp-close"></i>
            </button>
            { ( is_pro && builderContext?.is_pro_active > '5.1.3') ? (
               <>
                  <ApiCredentialsForm
                    props={props}
                    platform={platform}
                    requestHandler={socialProfileRequestHandler}
                    appInfo={appInfo}
                />
              </>
            ) : (
                <div dangerouslySetInnerHTML={ { __html: __(`Please update to the latest version of <strong>SchedulePress Pro</strong> to connect with <strong>Google Business Profile</strong>.`,'wp-scheduled-posts') } }></div>       
              ) }
        </Modal>
        {/* @ts-ignore */}
        <SocialModal
            setSelectedProfile={setSelectedProfile}
            props={props}
            type="google_business"
            profileStatus={profileStatus}
        />
    </div>
  );
};

export default GoogleBusiness;
