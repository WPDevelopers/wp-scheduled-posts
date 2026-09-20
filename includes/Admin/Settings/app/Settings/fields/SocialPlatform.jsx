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
import SelectedProfile from './utils/SelectedProfile';
import PlatformProfile from './Profiles/PlatformProfile';
import ViewMore from './utils/ViewMore';

/**
 * Settings card for a social network registered outside this plugin.
 *
 * Everything platform-specific arrives in the field definition — slug, the
 * settings key holding the on/off switch, logo, copy — so SchedulePress Pro can
 * add a network without shipping a line of React. It is only ever rendered for
 * a platform something is actually serving; the locked upsell card is
 * ProSocialPlatform.
 */
const SocialPlatform = (props) => {
  const platform = props?.platform;
  const statusKey = props?.status_key || `${platform}_profile_status`;

  const propsValue = props?.value || [];
  const sortedSelectedValue = [...propsValue].sort((a, b) => b.status - a.status);

  const cachedLocalData = JSON.parse(localStorage.getItem(platform));
  const builderContext = useBuilderContext();
  const [apiCredentialsModal, setApiCredentialsModal] = useState(false);
  const [selectedProfile, setSelectedProfile] = useState(sortedSelectedValue ?? []);
  const [selectedProfileViewMore, setSelectedProfileViewMore] = useState(false);
  const [cachedStatus, setCashedStatus] = useState(cachedLocalData ?? {});
  const [profileStatus, setProfileStatus] = useState(
    builderContext?.savedValues?.[statusKey]
  );
  const [activeStatusCount, setActiveStatusCount] = useState(0);

  localStorage.setItem(platform, JSON.stringify(cachedStatus));

  // Free allows one profile per network; the server enforces the same cap.
  // @ts-ignore
  const isPro = wpspSettingsGlobal?.pro_version ? true : false;

  // prepare appId and appSecret
  let appInfo = [];
  if (props?.value) {
    props?.value?.map((profile) => {
      if (profile['app_id'] && profile['app_secret']) {
        appInfo['app_id'] = profile['app_id'];
        appInfo['app_secret'] = profile['app_secret'];
      }
    });
  }

  const openApiCredentialsModal = () => setApiCredentialsModal(true);
  const closeApiCredentialsModal = () => setApiCredentialsModal(false);

  const handleProfileStatusChange = (event) => {
    setProfileStatus(event.target.checked);
    const changeProfileStatus = selectedProfile.map((selectedItem) => {
      if (!event.target.checked) {
        setCashedStatus((prevStatus) => ({
          ...prevStatus,
          [selectedItem.id]: selectedItem?.status,
        }));
        return { ...selectedItem, status: false };
      }
      return {
        ...selectedItem,
        status:
          cachedStatus?.[selectedItem.id] == undefined
            ? false
            : cachedStatus?.[selectedItem.id],
      };
    });
    setSelectedProfile(changeProfileStatus);
  };

  const handleSelectedProfileStatusChange = (item, event) => {
    if (event.target.checked) {
      setProfileStatus(true);
    }
    setCashedStatus((prevStatus) =>
      isPro
        ? { ...prevStatus, [item.id]: event.target.checked }
        : { [item.id]: event.target.checked }
    );

    if (isPro) {
      setSelectedProfile(
        selectedProfile.map((selectedItem) =>
          selectedItem.id === item.id
            ? { ...selectedItem, status: event.target.checked }
            : selectedItem
        )
      );
      return;
    }

    if (activeStatusCount > 1) {
      return;
    }

    const currentStatus = event.target.checked;
    const applyExclusiveStatus = () =>
      setSelectedProfile(
        selectedProfile.map((selectedItem) => ({
          ...selectedItem,
          status: selectedItem.id === item.id ? currentStatus : false,
        }))
      );

    if (activeStatusCount === 1 && currentStatus) {
      Swal.fire({
        title: __('Are you sure?', 'wp-scheduled-posts'),
        text: __(
          'Enabling this profile will deactivate other profile automatically.',
          'wp-scheduled-posts'
        ),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        cancelButtonText: '<i class="wpsp-icon wpsp-close"></i>',
        confirmButtonText: __('Yes, Enable it!', 'wp-scheduled-posts'),
      }).then((result) => {
        if (result.isConfirmed) {
          applyExclusiveStatus();
        }
      });
      return;
    }

    applyExclusiveStatus();
  };

  const deleteSelectedProfile = (item) => {
    setSelectedProfile(
      selectedProfile.filter((selectedItem) => selectedItem.id !== item.id)
    );
  };

  const handleDeleteSelectedProfile = (item) => {
    SweetAlertDeleteMsg({ item }, deleteSelectedProfile);
  };

  // Save selected profile data
  useEffect(() => {
    builderContext.setFieldValue([props.name], selectedProfile);
    let count = 0;
    if (selectedProfile) {
      selectedProfile.forEach((element) => {
        if (element.status) {
          count++;
        }
        setActiveStatusCount(count);
      });
    }
  }, [selectedProfile]);

  // Save profile status data
  let { onChange } = props;
  useEffect(() => {
    onChange({
      target: {
        type: 'checkbox-select',
        name: statusKey,
        value: profileStatus,
      },
    });
  }, [profileStatus]);

  const selectedProfileData = selectedProfileViewMore
    ? selectedProfile ?? []
    : (selectedProfile ?? []).slice(0, 2);

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
          <PlatformProfile
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
          {/* The stylesheet keys these off a hyphenated slug. */}
          <div className={`selected-${platform.replace(/_/g, '-')}-scrollbar`}>
            {selectedProfile &&
              selectedProfileData.map((item, index) => (
                <div className="selected-facebook-wrapper" key={index}>
                  <SelectedProfile
                    allProfiles={selectedProfileData}
                    platform={platform}
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
          {!selectedProfileViewMore &&
            selectedProfile &&
            selectedProfile.length >= 3 && (
              <ViewMore setSelectedProfileViewMore={setSelectedProfileViewMore} />
            )}
        </div>
      </div>
      <Modal
        isOpen={apiCredentialsModal}
        onRequestClose={closeApiCredentialsModal}
        ariaHideApp={false}
        shouldCloseOnOverlayClick={false}
        className="modal_wrapper">
        <button className="close-button" onClick={closeApiCredentialsModal}>
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
        type={platform}
        profileStatus={profileStatus}
      />
    </div>
  );
};

export default SocialPlatform;
