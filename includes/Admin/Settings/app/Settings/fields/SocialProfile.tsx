import classNames from 'classnames';
import React, { useState,useEffect } from 'react'
import { __ } from "@wordpress/i18n";
import ApiCredentialsForm from './SocialProfile/ApiCredentialsForm';
import ProfileData from './SocialProfile/ProfileData';
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


    const [modalIsOpen, setModalIsOpen] = useState(false);
    const [requestSending, setRequestSending] = useState(false);
    const [profileDataModal, setProfileDataModal] = useState(false);
    const [error, setError] = useState("");
   
    useEffect(() => {
        const getQueryParams = (query) => {
            const params = new URLSearchParams(query);
            const getProfileData = async () => {
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
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
                    }).toString(),
                });
                const responseData = await response.json();
                setProfileDataModal(responseData);
            }
            getProfileData();
        };
        getQueryParams(window.location.search);
    },[window.location]);

    const openApiCredentialsModal = () => {
        setApiCredentialsModal(true);
    };
    const closeApiCredentialsModal = () => {
        setApiCredentialsModal(false);
    };

    const openProfileDataModal = () => {
        setProfileDataModal(true);
    };

    const closeProfileDataModal = () => {
        setProfileDataModal(false);
    };

    const socialProfileRequestHandler = (redirectURI, appID, appSecret) => {
        const sendRequest = async () => {
            const response = await fetch('/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'wpsp_social_add_social_profile',      
                    redirectURI: redirectURI,
                    appId: appID,
                    appSecret: appSecret,
                    type: 'facebook',
                }).toString(),
            });
        
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
                        onClick={() => openApiCredentialsModal()}
                        >
                        {__("Add New", "wp-scheduled-posts")}
                    </button>
                </div>
                <div className='social-profile-card'>
                    <h5>Twitter</h5>
                    <button>Add New</button>
                </div>
                <div className='social-profile-card'>
                    <h5>Linkedin</h5>
                    <button>Add New</button>
                </div>
                <div className='social-profile-card'>
                    <h5>Pinterest</h5>
                    <button>Add New</button>
                </div>
           </div>
        {/* API Credentials Modal  */}
        <Modal
            isOpen={apiCredentialsModal}
            onRequestClose={closeApiCredentialsModal}
            ariaHideApp={false}
            style={customStyles}
            >
            <ApiCredentialsForm platform={'facebook'} requestHandler={socialProfileRequestHandler} />
        </Modal>

        {/* Profile Data Modal  */}
        <Modal
            isOpen={profileDataModal ? true : ''}
            onRequestClose={closeProfileDataModal}
            ariaHideApp={false}
            style={customStyles}
            >
            <ProfileData platform={'facebook'} profileData={profileDataModal} />
        </Modal>
        </div>
    )
}

export default SocialProfile;