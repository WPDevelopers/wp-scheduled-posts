import classNames from 'classnames';
import React, { useState,useEffect } from 'react'
import { __ } from "@wordpress/i18n";
import ApiCredentialsForm from './SocialProfile/ApiCredentialsForm';
import Facebook from './SocialProfile/Facebook';
import Linkedin from './SocialProfile/LInkedin';
import Modal from "react-modal";

const SocialProfile = (props) => {

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

    // Send API request
    const fetchDataFromAPI = async (body) => {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(body).toString(),
        });
        return response;
    };
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
        console.log(appID,appSecret);
        
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
    return (
        <div className={classNames('wprf-control', 'wprf-social-profile', `wprf-${props.name}-social-profile`, props?.classes)}>
           <h2>Social Profile</h2>
           <div className="social-card">
                <div className='social-profile-card'>
                    <h5>Facebook</h5>
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
                       <Facebook profileData={profileData} />
                    </>
                )}
            </Modal>
        </div>
    )
}

export default SocialProfile;