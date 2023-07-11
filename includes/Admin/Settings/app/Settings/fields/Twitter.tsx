import classNames from 'classnames';
import React, { useState,useEffect,useCallback } from 'react'
import { __ } from "@wordpress/i18n";
import {
    useBuilderContext,
    executeChange,
} from "quickbuilder";

import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import Modal from "react-modal";
import { socialProfileRequestHandler,getFormatDateTime } from '../helper/helper';
import SocialModal from './Modals/SocialModal';

import SelectedProfile from './utils/SelectedProfile';
import MainProfile from './utils/MainProfile';
import ProAlert from './utils/ProAlert';

const Twitter = (props) => {
    const builderContext = useBuilderContext();
    const [apiCredentialsModal,setApiCredentialsModal] = useState(false);
    const [platform, setPlatform] = useState('');
    const [selectedProfile, setSelectedProfile] = useState(props?.value);
    const [isErrorMessage, setIsErrorMessage] = useState(false)
    const [profileStatus, setProfileStatus] = useState(builderContext?.savedValues?.twitter_profile_status);

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
        const updatedData = selectedProfile.map(selectedItem => {
            if (!event.target.checked) {
                return {
                    ...selectedItem,
                    status: false,
                };
            }else{
                return {
                    ...selectedItem,
                    status: true,
                };
            }
        });
        setSelectedProfile(updatedData);
    };
    const handleSelectedProfileStatusChange = (item,event) => {
        const updatedData = selectedProfile.map(selectedItem => {
            if (selectedItem.id === item.id) {
            return {
                ...selectedItem,
                status: event.target.checked
            };
            }
            return selectedItem;
        });
        setSelectedProfile(updatedData);
    };

    const handleDeleteSelectedProfile = (item) => {
        const updatedData = selectedProfile.filter(selectedItem => selectedItem.id !== item.id);
        setSelectedProfile(updatedData);
    };

    // Save selected profile data
    useEffect( () => {
        builderContext.setFieldValue([props.name], selectedProfile);
    },[selectedProfile] )

    // Save profile status data
    let { onChange } = props;
    useEffect(() => {
		onChange({
			target: {
				type: "checkbox-select",
				name : 'twitter_profile_status',
				value: profileStatus,
			},
		});
	}, [profileStatus]);

    return (
        <div className={classNames('wprf-control', 'wprf-social-profile', `wprf-${props.name}-social-profile`, props?.classes)}>
            {isErrorMessage && (
                <ProAlert />
            )}
            <div className='social-profile-card'>
                <div className="main-profile">
                    <MainProfile props={props} handleProfileStatusChange={handleProfileStatusChange} profileStatus={profileStatus} openApiCredentialsModal={openApiCredentialsModal} />
                </div>
                <div className="selected-profile">
                    {selectedProfile && selectedProfile?.map((item,index) => (
                        <SelectedProfile platform={'twitter'} item={item} key={index} handleSelectedProfileStatusChange={handleSelectedProfileStatusChange} handleDeleteSelectedProfile={handleDeleteSelectedProfile} />
                    ))}
                </div>
            </div>
            {/* API Credentials Modal  */}
            <Modal
                isOpen={apiCredentialsModal}
                onRequestClose={closeApiCredentialsModal}
                ariaHideApp={false}
                className="modal_wrapper"
                >
                <button className="close-button" onClick={closeApiCredentialsModal}><i className='wpsp-icon wpsp-close'></i></button>
                <ApiCredentialsForm props={props}  platform={platform} requestHandler={socialProfileRequestHandler} />
            </Modal>

            {/* Profile Data Modal  */}
            {/* @ts-ignore */}
            <SocialModal
                selectedProfile={selectedProfile}
                setSelectedProfile={setSelectedProfile}
                setIsErrorMessage={setIsErrorMessage}
                props={props}
                type="twitter"
            />
        </div>
    )
}

export default Twitter;