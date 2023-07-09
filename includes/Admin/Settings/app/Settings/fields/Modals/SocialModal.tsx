import Modal from "react-modal";
import React, { useEffect, useState } from 'react';
import { __ } from "@wordpress/i18n";
import { generateTabURL, getProfileData,getPinterestBoardSection } from "../../helper/helper";
import Facebook from "./Facebook";
import Twitter from "./Twitter";
import Linkedin from "./Linkedin";
import Pinterest from "./Pinterest";

import {
    useBuilderContext,
} from "quickbuilder";

function SocialModal({selectedProfile, setSelectedProfile, setIsErrorMessage,props, type, accountType = '' }) {
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
    const [savedProfile,setSavedProfile] = useState([]);
    const [cashedSectionData, setCashedSectionData] = useState({});


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
                        generateTabURL();  
                        builderContext.setActiveTab('layout_social_profile');
                    }
                    getProfileData(params).then(response => {
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
                if (!savedProfile || (savedProfile && savedProfile.length == 0)) {
                    setIsErrorMessage(false)
                    if (!savedProfile.some((profile) => profile.id === item.id)) {
                        setSavedProfile((prevItems) => [...prevItems, item]);
                    }
                } else {
                    e.target.checked = false;
                    setIsErrorMessage(true)
                }
            } else {
                if ( savedProfile && !savedProfile.some((profile) => profile.id === item.id)) {
                    setSavedProfile((prevItems) => [...prevItems, item]);
                    setIsErrorMessage(false)
                }
            }
        }else{
            setIsErrorMessage(false)
            setSavedProfile((prevItems) => prevItems.filter((prevItem) => prevItem.id !== item.id));
        }
    }
    const addSavedProfile = () => {
        setSelectedProfile(savedProfile);
        closeProfileDataModal();
    }
    const noSection = { label: "No Section", value: "" };

    const fetchSectionData = (defaultBoard, profile, updateOptions) => {
        let options = [noSection];
        if (!cashedSectionData?.[defaultBoard]) {
          if (defaultBoard) {
            getPinterestBoardSection(defaultBoard,profile).then( (response:any) => {
                if (response.success === true) {
                    const sections = response.data?.map((section) => {
                      return {
                        label: section.name,
                        value: section.id,
                      };
                    });
                    options = [...options, ...sections];
                    updateOptions(options);
                    setCashedSectionData({
                      ...cashedSectionData,
                      [defaultBoard]: options,
                    });
                } else {
                    updateOptions(options);
                }
            })
            .catch(function () {
                console.log(error);
                updateOptions(options);
            });
          }
        } else {
          updateOptions(cashedSectionData?.[defaultBoard]);
        }
    };

    const addPinterestProfileToggle = (item,defaultBoard,defaultSection,event) => {
        const pinterestItem = { ...item, borads : pinterestBoards, defaultSection: defaultSection, default_board_name : defaultBoard };
        if( event.target.checked ) {
            // free
            // @ts-ignore
            if (!builderContext.is_pro_active) {
                // @ts-ignore
                if (!savedProfile || (savedProfile && savedProfile.length == 0)) {
                    setIsErrorMessage(false)
                    if (!savedProfile.some((profile) => profile.id === pinterestItem.id)) {
                        setSavedProfile((prevItems) => [...prevItems, pinterestItem]);
                    }
                } else {
                    event.target.checked = false;
                    setIsErrorMessage(true)
                }
            } else {
                if ( savedProfile && !savedProfile.some((profile) => profile.id === pinterestItem.id)) {
                    setSavedProfile((prevItems) => [...prevItems, pinterestItem]);
                    setIsErrorMessage(false)
                }
            }
        }else{
            setIsErrorMessage(false)
            setSavedProfile((prevItems) => prevItems.filter((prevItem) => prevItem.id !== pinterestItem.id));
        }
    }
    console.log(accountType);
    
  return (
    <Modal
        isOpen={profileDataModal}
        onRequestClose={closeProfileDataModal}
        ariaHideApp={false}
        className="modal_wrapper"
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
                    <button className="close-button" onClick={closeProfileDataModal}>X</button>
                    <div className="platform-info">
                        <img width={'30px'} src={`${props?.modal?.logo}`} alt={`${props?.label}`} />
                        <h4>{props?.label}</h4>
                    </div>
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
                                    savedProfile={addSavedProfile}
                                />
                            ),
                            twitter: (
                                <Twitter
                                    platform={type}
                                    data={responseData}
                                    addProfileToggle={addProfileToggle}
                                    savedProfile={addSavedProfile}
                                />
                            ),
                            linkedin: (
                                <Linkedin
                                    platform={type}
                                    data={linkedInData}
                                    addProfileToggle={addProfileToggle}
                                    savedProfile={addSavedProfile}
                                />
                            ),
                            pinterest: (
                                <Pinterest
                                    platform={socialPlatform}
                                    data={responseData}
                                    boards={pinterestBoards}
                                    fetchSectionData={fetchSectionData}
                                    noSection={noSection}
                                    addProfileToggle={addPinterestProfileToggle}
                                    savedProfile={addSavedProfile}
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