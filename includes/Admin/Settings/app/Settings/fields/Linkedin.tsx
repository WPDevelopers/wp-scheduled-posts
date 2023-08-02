import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';

import Modal from 'react-modal';
import { SweetAlertDeleteMsg } from '../ToasterMsg';
import { socialProfileRequestHandler } from '../helper/helper';
import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import SocialModal from './Modals/SocialModal';
import MainProfile from './utils/MainProfile';
import SelectedProfile from './utils/SelectedProfile';
import ViewMore from './utils/ViewMore';

const Linkedin = (props) => {
  const builderContext = useBuilderContext();
  const [apiCredentialsModal, setApiCredentialsModal] = useState(false);
  const [platform, setPlatform] = useState('');
  const [selectedProfile, setSelectedProfile] = useState(props?.value);
  const [selectedProfileViewMore, setSelectedProfileViewMore] = useState(false);
  const [profileStatus, setProfileStatus] = useState(
    builderContext?.savedValues?.linkedin_profile_status
  );

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
        name: 'linkedin_profile_status',
        value: profileStatus,
      },
    });
  }, [profileStatus]);
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
            {selectedProfile &&
              selectedProfile?.slice(0, 1).map((item, index) => (
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
                  />
                </div>
              ))}
          </div>
          {selectedProfile && selectedProfile.length > 1 && (
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
        />
      </Modal>

      {/* Profile Data Modal  */}
      <SocialModal
        setSelectedProfile={setSelectedProfile}
        props={props}
        type="linkedin"
      />
      <Modal
        isOpen={selectedProfileViewMore}
        ariaHideApp={false}
        shouldCloseOnOverlayClick={false}
        className="modal_wrapper">
        <button
          className="close-button"
          onClick={() => setSelectedProfileViewMore(false)}>
          <i className="wpsp-icon wpsp-close"></i>
        </button>
        <div className="selected-profile">
          {selectedProfile &&
            selectedProfile?.map((item, index) => (
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
                />
              </div>
            ))}
        </div>
      </Modal>
    </div>
  );
};

export default Linkedin;
