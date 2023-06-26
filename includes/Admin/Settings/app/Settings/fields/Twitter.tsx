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

const Twitter = (props) => {
    const builderContext = useBuilderContext();
    const [apiCredentialsModal,setApiCredentialsModal] = useState(false);
    const [platform, setPlatform] = useState('');
    const [selectedProfile, setSelectedProfile] = useState(props?.value);
    const [isErrorMessage, setIsErrorMessage] = useState(false)
    const [profileStatus, setProfileStatus] = useState(builderContext?.savedValues?.twitter_profile_status);

    const openApiCredentialsModal = (platform) => {
        setPlatform(platform);
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
           {/* <h2>Social Profile</h2> */}
           {isErrorMessage && (
                <div className='error-message'>
                    {__(
                        'Multi Profile is a Premium Feature. To use this feature,',
                        'wp-scheduled-posts'
                    )}
                    {" "}
                    <a target="_blank" href='https://wpdeveloper.com/in/schedulepress-pro'>
                        {__(
                            'Upgrade to PRO.',
                            'wp-scheduled-posts'
                        )}
                    </a>
                </div>
            )}
            <div className='social-profile-card'>
                <div className="main-profile">
                    <div className="card-header">
                        <div className="heading">
                            <img width={'30px'} src={`${props?.logo}`} alt={`${props?.label}`} />
                            <h5>{props?.label}</h5>                        
                        </div>
                        <div className="status">
                            <div className="switcher">
                                <input
                                    id={props?.id}
                                    type='checkbox'
                                    checked={profileStatus}
                                    className="wprf-switcher-checkbox"
                                    onChange={(event) =>
                                        handleProfileStatusChange(event)
                                    }
                                />
                                <label
                                    className="wprf-switcher-label"
                                    htmlFor={props?.id}
                                    style={{ background: profileStatus && '#02AC6E' }}
                                >
                                    <span className={`wprf-switcher-button`} />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div className="card-content">
                        <p dangerouslySetInnerHTML={{ __html: props?.desc }}></p>
                    </div>
                    <div className="card-footer">
                        <button
                            type="button"
                            className={
                            "wpscp-social-tab__btn--addnew-profile"
                            }
                            onClick={() => openApiCredentialsModal('twitter')}
                            >
                            {__("Add New", "wp-scheduled-posts")}
                        </button>
                    </div>
                </div>
                <div className="selected-profile">
                    {selectedProfile && selectedProfile?.map((item,index) => (
                        <div className="profile-item" key={Math.random()}>
                            <div className="profile-image">
                                {/* @ts-ignore */}
                                <img src={`${item?.thumbnail_url}`} alt={ __( item?.name,'wp-scheduled-posts' ) } />
                            </div>
                            <div className="profile-data">
                                <span className='badge'>{ item.type ? item.type : __('Profile','wp-scheduled-posts') }</span>
                                <h4>{ item?.name }</h4>
                                <span>{ item?.added_by.replace(/^\w/, (c) => c.toUpperCase()) } { __('on','wp-scheduled-posts') } {getFormatDateTime(item?.added_date)}</span>
                                <div className="action">
                                <div className="status">
                                        <div className="switcher">
                                            <input
                                                id={item?.id}
                                                type='checkbox'
                                                className="wprf-switcher-checkbox"
                                                checked={item?.status}
                                                onChange={(event) =>
                                                    handleSelectedProfileStatusChange(item,event)
                                                }
                                            />
                                            <label
                                                className="wprf-switcher-label"
                                                htmlFor={item?.id}
                                                style={{ background: item?.status && '#02AC6E' }}
                                            >
                                                <span className={`wprf-switcher-button`} />
                                            </label>
                                        </div>
                                    </div>
                                    <div className="remove-profile">
                                        <button onClick={ () => handleDeleteSelectedProfile( item ) }>{ __('Delete','wp-scheduled-posts') }</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                <button className="close-button" onClick={closeApiCredentialsModal}>X</button>
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