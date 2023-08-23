import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';

import { __ } from '@wordpress/i18n';
import Modal from 'react-modal';
import Swal from 'sweetalert2';
import { SweetAlertDeleteMsg, SweetAlertProMsg } from '../ToasterMsg';
import { socialProfileRequestHandler } from '../helper/helper';
import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import SocialModal from './Modals/SocialModal';
import MainProfile from './utils/MainProfile';
import SelectedProfile from './utils/SelectedProfile';
import ViewMore from './utils/ViewMore';

const Linkedin = (props) => {
  const propsValue = props?.value || [];
  const sortedSelectedValue  = [...propsValue].sort((a, b) => {
    return b.status - a.status; // Sort in descending order by status
  });
  const cachedLocalData = JSON.parse(localStorage.getItem('linkedin'));
  const builderContext = useBuilderContext();
  const [apiCredentialsModal, setApiCredentialsModal] = useState(false);
  const [platform, setPlatform] = useState('');
  const [selectedProfile, setSelectedProfile] = useState( sortedSelectedValue ?? [] );
  const [selectedProfileViewMore, setSelectedProfileViewMore] = useState(false);
  const [cachedStatus, setCashedStatus] = useState(cachedLocalData ?? {});
  const [activeStatusCount,setActiveStatusCount] = useState(0);
  const [profileStatus, setProfileStatus] = useState(
    builderContext?.savedValues?.linkedin_profile_status
  );

  localStorage.setItem('linkedin',JSON.stringify(cachedStatus));

  // @ts-ignore 
  const is_pro = wpspSettingsGlobal?.pro_version ? true : false;
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

  const openApiCredentialsModal = (accountType) => {
    localStorage.setItem('account_type', accountType);
    setPlatform('linkedin');
    setApiCredentialsModal(true);
  };
  const closeApiCredentialsModal = () => {
    setPlatform('linkedin');
    setApiCredentialsModal(false);
  };

  // @ts-ignore
  let { profiles = [], pages = [], ...appData } = selectedProfile ?? {};
  let account_type = localStorage.getItem('account_type');
  let all_profiles = [];
  let all_pages = [];
  if (account_type == 'profile') {
    all_profiles = profiles ? profiles : [];
    all_profiles = all_profiles.map((val, i) => {
      return { ...appData, ...val };
    });
  } else if (account_type == 'page') {
    all_pages = pages ? pages : [];
    all_pages = all_pages.map((val, i) => {
      return { ...appData, ...val };
    });
  }

  // Handle profile & selected profile status onChange event
  const handleProfileStatusChange = (event) => {
    setProfileStatus(event.target.checked);
    const updatedData = selectedProfile.map((selectedItem) => {
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
          status : (cachedStatus?.[selectedItem.id] == undefined) ? builderContext?.savedValues?.linkedin_profile_status : cachedStatus?.[selectedItem.id], 
        };
      }
    });
    setSelectedProfile(updatedData);
  };

  // Handle selected profile status changing 
  const handleSelectedProfileStatusChange = (item, event) => {
    if( !is_pro && item?.type === 'organization' ){
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

  const handleDeleteSelectedProfile = (item) => {
    SweetAlertDeleteMsg({ item }, deleteFile);
  };
  const deleteFile = (item) => {
    const updatedData = selectedProfile.filter(
      (selectedItem) => selectedItem.id !== item.id
    );
    setSelectedProfile(updatedData);
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
        name: 'linkedin_profile_status',
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
      <div className="social-profile-card">
        <div className="main-profile">
          <MainProfile
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
          <div className="selected-linkedin-scrollbar">
            {selectedProfileData.map((item, index) => (
              <div
                className="selected-linkedin-wrapper"
                key={index}>
                <SelectedProfile
                  platform={'linkedin'}
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
          { ( !selectedProfileViewMore && selectedProfile && selectedProfile.length >= 3) && (
            <ViewMore setSelectedProfileViewMore={setSelectedProfileViewMore} />
          )}
        </div>
      </div>
      {/* API Credentials Modal  */}
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
        <ApiCredentialsForm
          props={props}
          platform={platform}
          requestHandler={socialProfileRequestHandler}
          appInfo={appInfo}
        />
      </Modal>

      {/* Profile Data Modal  */}
      <SocialModal
        setSelectedProfile={setSelectedProfile}
        props={props}
        type="linkedin"
        profileStatus={profileStatus}
      />
    </div>
  );
};

export default Linkedin;
