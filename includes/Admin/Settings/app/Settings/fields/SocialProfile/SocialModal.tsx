import Modal from "react-modal";
import React, { useEffect, useState } from 'react';
import { __ } from "@wordpress/i18n";
import { activeSocialTab, getProfileData } from "../../helper/helper";
import {
    useBuilderContext,
} from "quickbuilder";

function SocialModal({ customStyles, pages, profiles, selectedProfile, setSelectedProfile, setIsErrorMessage,setFbProfileData,setLinkedInProfileData,setPinterestBoards, type }) {
    const builderContext = useBuilderContext();

    const [requestSending, setRequestSending] = useState(false);
    const [profileDataModal, setProfileDataModal] = useState(false);
    const [error, setError] = useState("");

    useEffect(() => {
        // Send API request fo fetching data
        const getQueryParams = (query) => {
            const params = new URLSearchParams(query);
            const error = params.get('error_message');
            if( error ) {
                setError(error);
            }else{
                if( params.get('action') === 'wpsp_social_add_social_profile' && params.get("type") == type ) {
                    setProfileDataModal(true);
                    setRequestSending(true);
                    // remove unnecessary query string and active social profile tab
                    if (history.pushState) {
                        activeSocialTab();                
                    }
                    getProfileData(params).then(res => {
                        setRequestSending(false);
                        if( type === 'linkedin' ) {
                            setLinkedInProfileData(res.linkedin);
                        }else if( type == 'pinterest' ){
                            setPinterestBoards(res);
                        }else {
                            setFbProfileData(res);
                        }
                    })
                }
            }
        };
        getQueryParams(window.location.search);        
    },[window.location]);

    const closeProfileDataModal = () => {
        setProfileDataModal(false);
    };
    // Add linkedin prifle 
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
                    <h3>This is modal for {type} header</h3>
                </div>
                <div className="modalbody">
                    {/* @ts-ignore */}
                    { (pages ?? []).length && (
                        <div className="profile-list-page">
                            <h3>Pages</h3>
                            <ul className="prfile-list">
                                {/* @ts-ignore */}
                                {pages?.map((item,index) => (
                                <li id={'linkedin_page_' + index} key={index}>
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
                    { (profiles ?? []).length > 0 && (
                        <div className="profile-list-group">
                            <h3>Profile</h3>
                            <ul className="prfile-list">
                                {/* @ts-ignore */}
                                {profiles.map((item,index) => (
                                <li id={'linkedin_page_' + index} key={index}>
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
                    )}
                </div>
            </>
        )}
    </Modal>
  )
}

export default SocialModal