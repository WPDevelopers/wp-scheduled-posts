import classNames from 'classnames';
import React, { useState,useEffect,useCallback } from 'react'
import { __ } from "@wordpress/i18n";
import {
    useBuilderContext,
    executeChange,
} from "quickbuilder";

import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import Modal from "react-modal";
import { socialProfileRequestHandler } from '../helper/helper';
import SocialModal from './Modals/SocialModal';

const Facebook = (props) => {
    const builderContext = useBuilderContext();

    const handleChange = useCallback((event, index) => {
        const { field, val: value } = executeChange(event);
        builderContext.setFieldValue([props.name, field], value);
    }, [props.value]);

    const [apiCredentialsModal,setApiCredentialsModal] = useState(false);
    const [fbProfileData, setFbProfileData] = useState({
        page: [],
        group: []
    });
    const [platform, setPlatform] = useState('');
    const [selectedProfile, setSelectedProfile] = useState([]);
    const [isErrorMessage, setIsErrorMessage] = useState(false)

    const openApiCredentialsModal = (platform) => {
        setPlatform(platform);
        setApiCredentialsModal(true);
    };
    const closeApiCredentialsModal = () => {
        setPlatform('');
        setApiCredentialsModal(false);
    };

    useEffect(() => {
      console.log(fbProfileData);
    }, [fbProfileData])
    
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
                            <img width={'30px'} src={`${props?.logo}`} alt="" />
                            <h5>Facebook</h5>
                        </div>
                        <div className="status">
                            <input
                                type='checkbox'
                                onChange={(e) =>
                                    handleChange(e,1)
                                }
                            />
                        </div>
                    </div>
                    <div className="card-content">
                        <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Eum dolorem velit nisi vel perspiciatis rerum reprehenderit. Quisquam nisi maiores, voluptatem dignissimos accusamus ipsum recusandae earum. Sed dolorem sint ducimus excepturi.</p>
                    </div>
                    <div className="card-footer">
                        <select name="" id="">
                            <option value="">Page</option>
                            <option value="">Group</option>
                        </select>
                        <button
                            type="button"
                            className={
                            "wpscp-social-tab__btn--addnew-profile"
                            }
                            onClick={() => openApiCredentialsModal('facebook')}
                            >
                            {__("Add New", "wp-scheduled-posts")}
                        </button>
                    </div>
                </div>
                <div className="selected-profile">
                    {selectedProfile.map((item,index) => (
                        <div className="profile-item" key={Math.random()}>
                            <div className="profile-image">
                                <img src="" alt="" />
                            </div>
                            <div className="profile-data">
                                <span>Profile</span>
                                <h4>{item.name}</h4>
                                <span>Admin on 12 June, 2023</span>
                                <div className="action">
                                    <div className="change-status">
                                        <input type="checkbox" name="" id="" />
                                    </div>
                                    <div className="remove-profile">
                                        <button>Delete</button>
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
                >
                
                <ApiCredentialsForm  platform={platform} requestHandler={socialProfileRequestHandler} />
            </Modal>

            {/* Profile Data Modal  */}
            {/* @ts-ignore */}
            <SocialModal
                selectedProfile={selectedProfile}
                setSelectedProfile={setSelectedProfile}
                setIsErrorMessage={setIsErrorMessage}
                type="facebook"
            />
        </div>
    )
}

export default Facebook;