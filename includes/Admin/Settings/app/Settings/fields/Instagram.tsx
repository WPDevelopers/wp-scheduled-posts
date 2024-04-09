import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';
import Modal from 'react-modal';
import Swal from 'sweetalert2';
import { SweetAlertDeleteMsg } from '../ToasterMsg';
import { socialProfileRequestHandler } from '../helper/helper';
import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import SocialModal from './Modals/SocialModal';
import MainProfile from './utils/MainProfile';
import SelectedProfile from './utils/SelectedProfile';
import ViewMore from './utils/ViewMore';

const Instagram = (props) => {
  const propsValue = props?.value || [];
  const sortedSelectedValue  = [...propsValue].sort((a, b) => {
    return b.status - a.status; // Sort in descending order by status
  });
  const cachedLocalData = JSON.parse(localStorage.getItem('instagram'));
  const builderContext = useBuilderContext();
  const [apiCredentialsModal, setApiCredentialsModal] = useState(false);
  const [selectedProfileViewMore, setSelectedProfileViewMore] = useState(false);
  const [platform, setPlatform] = useState('');
  const [selectedProfile, setSelectedProfile] = useState( sortedSelectedValue ?? [] );
  const [cachedStatus, setCashedStatus] = useState(cachedLocalData ?? {});
  const [activeStatusCount,setActiveStatusCount] = useState(0);

  const [profileStatus, setProfileStatus] = useState(
      builderContext?.savedValues?.instagram_profile_status
  );

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
  const handleDeleteSelectedProfile = (item) => {
    SweetAlertDeleteMsg({ item }, deleteSelectedProfile);
  };
  const deleteSelectedProfile = (item) => {
    const updateSelectedProfile = selectedProfile.filter(
      (selectedItem) => selectedItem.id !== item.id
    );
    setSelectedProfile(updateSelectedProfile);
  };
  // Handle profile & selected profile status onChange event
  const handleProfileStatusChange = (event) => {
      setProfileStatus(event.target.checked);
  };
  
  // @ts-ignore
  const is_pro = wpspSettingsGlobal?.pro_version ? true : false;
  // Open and Close API credentials modal
  const openApiCredentialsModal = (accountType) => {
      localStorage.setItem('account_type', accountType);
      setPlatform('instagram');
      setApiCredentialsModal(true);
  };
  const closeApiCredentialsModal = () => {
    setApiCredentialsModal(false);
  };
  const handleSelectedProfileStatusChange = (item, event) => {
    console.log('event', event.target.checked);
    console.log('item', item);
    
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
  }
  
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
        name: 'instagram_profile_status',
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
          <div className="selected-facebook-scrollbar">
            {selectedProfile &&
              selectedProfileData.map((item, index) => (
                <div
                  className="selected-facebook-wrapper"
                  key={index}>
                  <SelectedProfile
                    platform={'instagram'}
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
          {/* { ( !selectedProfileViewMore && selectedProfile && selectedProfile.length >= 3) && (
            <ViewMore setSelectedProfileViewMore={setSelectedProfileViewMore} />
          )} */}
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
      {/* @ts-ignore */}
      <SocialModal
        setSelectedProfile={setSelectedProfile}
        props={props}
        type="instagram"
        profileStatus={profileStatus}
      />
    </div>
  );
};
export default Instagram