import Modal from "react-modal";
import React, { useEffect, useState } from 'react';
import { __ } from "@wordpress/i18n";
import { activeSocialTab, getProfileData } from "../../helper/helper";
import Facebook from "./Facebook";
import Twitter from "./Twitter";
import Linkedin from "./Linkedin";
import Pinterest from "./Pinterest";

import {
    useBuilderContext,
} from "quickbuilder";

function SocialModal({ customStyles, selectedProfile, setSelectedProfile, setIsErrorMessage, type }) {
    const builderContext = useBuilderContext();

    const [requestSending, setRequestSending] = useState(false);
    const [profileDataModal, setProfileDataModal] = useState(false);
    const [error, setError] = useState("");
    const [borads,setBoards] = useState([]);
    const [twitterData,setTwitterData] = useState([]);
    const [fbPage, setFbPage] = useState([]);
    const [fbGroup, setFbGroup] = useState([]);
    const [pinterestBoards, setPinterestBoards] = useState([]);
    const [responseData, setResponseData] = useState([]);
    const [linkedInData, setLinkedInData] = useState({})
    const [socialPlatform, setSocialPlatform] = useState("");

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
                    getProfileData(params).then(response => {
                        console.log(response);
                        setRequestSending(false);
                        setFbPage(response.page);
                        setFbGroup(response.group);
                        setResponseData([response.data]);
                        setLinkedInData(response.linkedin);
                        setPinterestBoards(response.boards);
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
    useEffect(() => {
        console.log('pinterest board:',pinterestBoards);
    },[pinterestBoards]);
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
                    {
                        {
                            facebook: (
                                <Facebook
                                    page={fbPage}
                                    group={fbGroup}
                                    addProfileToggle={addProfileToggle}
                                />
                            ),
                            twitter: (
                                <Twitter
                                    platform={type}
                                    data={responseData}
                                    addProfileToggle={addProfileToggle}
                                />
                            ),
                            linkedin: (
                                <Linkedin
                                    platform={type}
                                    data={linkedInData}
                                    addProfileToggle={addProfileToggle}
                                />
                            ),
                            pinterest: (
                                <Pinterest
                                  platform={socialPlatform}
                                  data={pinterestBoards}
                                  addProfileToggle={pinterestBoards}
                                />
                              ),
                        }[type]
                    }
                </div>
            </>
        )}
    </Modal>
  )
}

export default SocialModal