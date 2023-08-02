import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';

import Modal from 'react-modal';
import { socialProfileRequestHandler } from '../helper/helper';
import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import SocialModal from './Modals/SocialModal';

import { SweetAlertDeleteMsg } from '../ToasterMsg';
import MainProfile from './utils/MainProfile';
import SelectedProfile from './utils/SelectedProfile';
import ViewMore from './utils/ViewMore';

const Twitter = (props) => {
  const builderContext = useBuilderContext();
  const [apiCredentialsModal, setApiCredentialsModal] = useState(false);
  const [platform, setPlatform] = useState('');
  const [selectedProfile, setSelectedProfile] = useState(props?.value);
  const [isErrorMessage, setIsErrorMessage] = useState(false);
  const [selectedProfileViewMore, setSelectedProfileViewMore] = useState(false);
  const [profileStatus, setProfileStatus] = useState(
    builderContext?.savedValues?.twitter_profile_status
  );

  const openApiCredentialsModal = (platform) => {
    setPlatform('twitter');
    setApiCredentialsModal(true);
  };
  const closeApiCredentialsModal = () => {
    setPlatform('');
    setApiCredentialsModal(false);
  };

  // Handle profile & selected profile status onChange event
  const handleProfileStatusChange = (event) => {
    setProfileStatus(event.target.checked);
    const updatedData = selectedProfile.map((selectedItem) => {
      if (!event.target.checked) {
        return {
          ...selectedItem,
          status: false,
        };
      } else {
        return {
          ...selectedItem,
          status: true,
        };
      }
    });
    setSelectedProfile(updatedData);
  };
  const handleSelectedProfileStatusChange = (item, event) => {
    if (event.target.checked) {
      setProfileStatus(true);
    }
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
  }, [selectedProfile]);

  // Save profile status data
  let { onChange } = props;
  useEffect(() => {
    onChange({
      target: {
        type: 'checkbox-select',
        name: 'twitter_profile_status',
        value: profileStatus,
      },
    });
  }, [profileStatus]);
  let selectedProfileData = [];
  if (selectedProfile && selectedProfileViewMore) {
    selectedProfileData = selectedProfile;
  } else if (selectedProfile && !selectedProfileViewMore) {
    selectedProfileData = selectedProfile.slice(0, 1);
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
          <div className="selected-pinterest-scrollbar">
            {selectedProfileData.map((item, index) => (
              <div
                className="selected-twitter-wrapper"
                key={index}>
                <SelectedProfile
                  key={index}
                  platform={'twitter'}
                  item={item}
                  handleSelectedProfileStatusChange={
                    handleSelectedProfileStatusChange
                  }
                  handleDeleteSelectedProfile={handleDeleteSelectedProfile}
                  handleEditSelectedProfile={''}
                />
              </div>
            ))}
          </div>
          {selectedProfileData && selectedProfileData.length == 1 && (
            <ViewMore setSelectedProfileViewMore={setSelectedProfileViewMore} />
          )}
        </div>
      </div>
      {/* API Credentials Modal  */}
      <Modal
        isOpen={apiCredentialsModal}
        onRequestClose={closeApiCredentialsModal}
        ariaHideApp={false}
        className="modal_wrapper"
        shouldCloseOnOverlayClick={false}>
        <button
          className="close-button"
          onClick={closeApiCredentialsModal}>
          <i className="wpsp-icon wpsp-close"></i>
        </button>
        <ApiCredentialsForm
          props={props}
          platform={platform}
          requestHandler={socialProfileRequestHandler}
        />
      </Modal>

      {/* Profile Data Modal  */}
      {/* @ts-ignore */}
      <SocialModal
        setSelectedProfile={setSelectedProfile}
        props={props}
        type="twitter"
      />
    </div>
  );
};

export default Twitter;
