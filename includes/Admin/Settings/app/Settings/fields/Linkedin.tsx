import classNames from 'classnames';
import React, { useState,useEffect,useCallback } from 'react'
import { __ } from "@wordpress/i18n";
import {
    useBuilderContext,
    executeChange,
} from "quickbuilder";

import ApiCredentialsForm from './SocialProfile/ApiCredentialsForm';
import Modal from "react-modal";
import { fetchDataFromAPI,activeSocialTab,socialProfileRequestHandler, getProfileData } from '../helper/helper';
import SocialModal from './SocialProfile/SocialModal';

const Linkedin = (props) => {
    const builderContext = useBuilderContext();
    console.log(builderContext.values);
    

    const handleChange = useCallback((event, index) => {
        const { field, val: value } = executeChange(event);
        console.log(builderContext);
        console.log(selectedProfile);
        builderContext.setFieldValue([props.name, field], value);
    }, [props.value]);

    const customStyles = {
        overlay: {
          background: "rgba(1, 17, 50, 0.7)",
          padding: "50px 20px",
          display: "flex",
          overflow: "auto",
        },
        content: {
          margin: "auto",
          maxWidth: "100%",
          width: "450px",
          position: "static",
          overflow: "hidden",
        },
    };

    const [apiCredentialsModal,setApiCredentialsModal] = useState(false);
    
    const [profileData, setProfileData] = useState(false);
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

   

    // @ts-ignore
    let { profiles = [], pages = [], ...appData} = profileData ?? {};
    profiles = profiles ? profiles : [];
    profiles = profiles.map((val, i) => {
        return {...appData, ...val}
    });
    pages = pages ? pages : [];
    pages = pages.map((val, i) => {
        return {...appData, ...val}
    });
    
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
                <div className="card-header">
                    <div className="heading">
                        <h5>Linkedin</h5>
                    </div>
                    <div className="status">
                        <input
                            name='enabled'
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
                        onClick={() => openApiCredentialsModal('linkedin')}
                        >
                        {__("Add New", "wp-scheduled-posts")}
                    </button>
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
                style={customStyles}
                >
                
                <ApiCredentialsForm platform={platform} requestHandler={socialProfileRequestHandler} />
            </Modal>

            {/* Profile Data Modal  */}

            <SocialModal 
                customStyles={customStyles}
                pages={pages}
                profiles={profiles}
                setProfileData={setProfileData}
                selectedProfile={selectedProfile}
                setSelectedProfile={setSelectedProfile}
                setIsErrorMessage={setIsErrorMessage}
                type="linkedin"
            />
            
        </div>
    )
}

export default Linkedin;