import classNames from 'classnames';
import React, { useState,useEffect,useCallback } from 'react'
import { __ } from "@wordpress/i18n";
import {
    useBuilderContext,
    executeChange,
} from "quickbuilder";

import ApiCredentialsForm from './SocialProfile/ApiCredentialsForm';
import Modal from "react-modal";
import { fetchDataFromAPI,activeSocialTab } from '../helper/helper';

const Facebook = (props) => {
    const builderContext = useBuilderContext();

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
    const [requestSending, setRequestSending] = useState(false);
    const [profileDataModal, setProfileDataModal] = useState(false);
    const [profileData, setProfileData] = useState(false);
    const [platform, setPlatform] = useState('');
    const [error, setError] = useState("");
    const [selectedProfile, setSelectedProfile] = useState([]);
    const [isErrorMessage, setIsErrorMessage] = useState(false)

    useEffect(() => {
        // Send API request fo fetching data
        const getQueryParams = (query) => {
            const params = new URLSearchParams(query);
            const error = params.get('error_message');
            if( error ) {
                setError(error);
            }else{
                if( params.get('action') === 'wpsp_social_add_social_profile' ) {
                    setProfileDataModal(true);
                    setRequestSending(true);
                    // remove unnecessary query string and active social profile tab
                    if (history.pushState) {
                        activeSocialTab();                
                    }
                    const getProfileData = async () => {
                        const data = {
                            action: "wpsp_social_profile_fetch_user_info_and_token",
                            type: params.get("type"),
                            appId: params.get("appId"),
                            appSecret: params.get("appSecret"),
                            code: params.get("code"),
                            redirectURI: params.get("redirectURI"),
                            access_token: params.get("access_token"),
                            refresh_token: params.get("refresh_token"),
                            expires_in: params.get("expires_in"),
                            rt_expires_in: params.get("rt_expires_in"),
                            oauthVerifier: params.get("oauth_verifier"),
                            oauthToken: params.get("oauth_token"),
                        };
                        const response = await fetchDataFromAPI(data);
                        const responseData = await response.json();
                        setRequestSending(false);
                        setProfileData(responseData);
                    }
                    getProfileData();
                }
            }
            
        };
        getQueryParams(window.location.search);        
    },[window.location]);

    const openApiCredentialsModal = (platform) => {
        setPlatform(platform);
        setApiCredentialsModal(true);
    };
    const closeApiCredentialsModal = () => {
        setPlatform('');
        setApiCredentialsModal(false);
    };

    const closeProfileDataModal = () => {
        setProfileDataModal(false);
    };
  
    const socialProfileRequestHandler = (redirectURI, appID, appSecret, platform) => {
        
        // Send API request for fetch url
        const sendRequest = async () => {
            const data = {
                action: 'wpsp_social_add_social_profile',      
                redirectURI: redirectURI,
                appId: appID,
                appSecret: appSecret,
                type: platform,
            };

            const response = await fetchDataFromAPI(data);
            
            const responseData = await response.json();
            if (responseData.success) {
                open(responseData.data, '_self');
              } else {
                let message;
                try {
                  const parsedData = JSON.parse(responseData.data);
                  if (parsedData?.errors?.[0]?.message) {
                    message = parsedData.errors[0].message;
                  } else {
                    message = responseData.data;
                  }
                } catch (e) {
                  message = responseData.data;
                }
                console.log(message);
            }
        };
        sendRequest();
    };

    // Add facebook prifle 
    const addProfileToggle = (item, index, e) => {
        if( e.target.checked ) {
            // free
            // @ts-ignore
            if (!builderContext.is_pro_active) {
                // @ts-ignore
                if (!selectedProfile || (selectedProfile && selectedProfile.length == 0)) {
                    setIsErrorMessage(false)
                    if (!selectedProfile.some((profile) => profile.id === item.id)) {
                        setSelectedProfile((prevItems) => [...prevItems, item]);
                    }
                } else {
                    e.target.checked = false;
                    setIsErrorMessage(true)
                }
            } else {
                if (!selectedProfile.some((profile) => profile.id === item.id)) {
                    setSelectedProfile((prevItems) => [...prevItems, item]);
                    setIsErrorMessage(false)
                }
            }
        }else{
            setIsErrorMessage(false)
            setSelectedProfile((prevItems) => prevItems.filter((prevItem) => prevItem.id !== item.id));
        }
    }
    return (
        <div className={classNames('wprf-control', 'wprf-social-profile', `wprf-${props.name}-social-profile`, props?.classes)}>
           <h2>Social Profile</h2>
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
                <div className="selected-profile">
                    <div className="profile-item">
                        <div className="profile-image">
                            <img src="" alt="" />
                        </div>
                        <div className="profile-data">
                            <span>Profile</span>
                            <h4>David Warner</h4>
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
                </div>
                <h4>Selected Profile</h4>
                <ul>
                    {selectedProfile.map((item,index) => (
                        <li>{item.name}</li>
                    ))}
                </ul>
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
            <Modal
                isOpen={profileDataModal}
                onRequestClose={closeProfileDataModal}
                ariaHideApp={false}
                style={customStyles}
                >
                {requestSending ? (
                    <div className="wpsp-modal-info">
                        {error
                        ? error
                        : __(
                            "Generating Token & Fetching User Data",
                            "wp-scheduled-posts"
                            )}
                    </div>
                ) : (
                    <>
                        <div className="modalhead">
                            <h3>This is modal for facebook header</h3>
                        </div>
                        <div className="modalbody">
                            {/* @ts-ignore */}
                            { (profileData?.page ?? []).length && (
                                <div className="profile-list-page">
                                    <h3>Pages</h3>
                                    <ul className="prfile-list">
                                        {/* @ts-ignore */}
                                        {profileData?.page?.map((item,index) => (
                                        <li id={'facebook_page_' + index} key={index}>
                                            <div className='item-content'>
                                                <div className='entry-thumbnail'>
                                                    <img
                                                        src={item.thumbnail_url}
                                                        alt='logo'
                                                    />
                                                </div>
                                                <h4 className='entry-title'>
                                                    {item.name}
                                                </h4>
                                                <div className='control'>
                                                    <input
                                                        type='checkbox'
                                                        onChange={(e) =>
                                                            addProfileToggle(
                                                                item,
                                                                index,
                                                                e
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </li>
                                        ))}
                                    </ul>
                                </div>
                            ) }
                            {/* @ts-ignore */}
                            { (profileData?.group ?? []).length > 0 && (
                                <div className="profile-list-group">
                                    <h3>Group</h3>
                                    <ul className="prfile-list">
                                        {/* @ts-ignore */}
                                        {profileData?.group?.map((item,index) => (
                                        <li id={'facebook_page_' + index} key={index}>
                                            <div className='item-content'>
                                                <div className='entry-thumbnail'>
                                                    <img
                                                        src={item.thumbnail_url}
                                                        alt='logo'
                                                    />
                                                </div>
                                                <h4 className='entry-title'>
                                                    {item.name}
                                                </h4>
                                                <div className='control'>
                                                    <input
                                                        type='checkbox'
                                                        onChange={(e) =>
                                                            addProfileToggle(
                                                                item,
                                                                index,
                                                                e.target.checked
                                                            )
                                                        }
                                                    />
                                                </div>
                                            </div>
                                        </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    </>
                )}
            </Modal>
        </div>
    )
}

export default Facebook;