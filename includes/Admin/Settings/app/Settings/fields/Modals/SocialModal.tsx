import { __ } from "@wordpress/i18n";
import React, { useEffect, useState } from 'react';
import Modal from "react-modal";
import { generateTabURL, getPinterestBoardSection, getProfileData } from "../../helper/helper";
import ProAlert from "../utils/ProAlert";
import Facebook from "./Facebook";
import Linkedin from "./Linkedin";
import Pinterest from "./Pinterest";
import Twitter from "./Twitter";

import {
    useBuilderContext,
} from "quickbuilder";

function SocialModal({setSelectedProfile,props, type, profileItem = '', isProfileEditModal = false, setProfileEditModal = null , profileStatus }) {
    const builderContext = useBuilderContext();

    const [requestSending, setRequestSending] = useState(false);
    const [profileDataModal, setProfileDataModal] = useState(false);
    const [error, setError] = useState("");
    const [fbPage, setFbPage] = useState([]);
    const [fbGroup, setFbGroup] = useState([]);
    const [pinterestBoards, setPinterestBoards] = useState([]);
    const [responseData, setResponseData] = useState([]);
    const [linkedInData, setLinkedInData] = useState({})
    const [savedProfile,setSavedProfile] = useState(props?.value ?? []);
    const [cashedSectionData, setCashedSectionData] = useState({});
    const [singlePinterestBoard,setSinglePinterestBoard] = useState('');
    const [isErrorMessage, setIsErrorMessage] = useState(false)
    let account_type = localStorage.getItem('account_type');

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
                        if( account_type == 'page' ) {
                            setFbPage(response.page);
                        }else if( account_type == 'group' ) {
                            setFbGroup(response.group);
                        }else{
                            setFbPage(response.page);
                            setFbGroup(response.group);
                        }
                        setResponseData([response.data]);
                        setLinkedInData(response.linkedin);
                        setPinterestBoards(response.boards);
                    })
                }
            }
        };
        getQueryParams(window.location.search);
    },[window.location]);

    useEffect( () => {
        if( profileItem ) {
            // @ts-ignore
            setSinglePinterestBoard(profileItem);
            setProfileDataModal(true);
        }
    },[profileItem] )

    const closeProfileDataModal = () => {
        if( type == 'pinterest' ) {
            setProfileEditModal(false);
        }
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
                        item.status = profileStatus;
                        setSavedProfile((prevItems) => [...prevItems, item]);
                    }
                } else {
                    e.target.checked = false;
                    setIsErrorMessage(true)
                }
            } else {
                if ( savedProfile && !savedProfile.some((profile) => profile.id === item.id)) {
                    item.status = profileStatus;
                    setSavedProfile((prevItems) => [...prevItems, item]);
                    setIsErrorMessage(false)
                }
            }
        }else{
            setIsErrorMessage(false)
            setSavedProfile((prevItems) => prevItems.filter((prevItem) => prevItem.id !== item.id));
        }
    }

    const noSection = { label: __("No Section",'wp-scheduled-posts'), value: "" };

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
                updateOptions(options);
            });
          }
        } else {
          updateOptions(cashedSectionData?.[defaultBoard]);
        }
    };

    const addPinterestProfileToggle = (item,defaultSection,event, board) => {
        const pinterestItem = { ...item, borads : pinterestBoards, defaultSection: defaultSection, default_board_name : board };
        if( event?.target?.checked ) {
            // free
            // @ts-ignore
            if (!builderContext.is_pro_active) {
                // @ts-ignore
                if (!savedProfile || savedProfile === 'undefined' || (savedProfile && savedProfile.length == 0)) {
                    setIsErrorMessage(false)
                    pinterestItem.status = profileStatus;
                    setSavedProfile((prevItems) => [...prevItems, pinterestItem]);
                } else {
                    event.target.checked = false;
                    setIsErrorMessage(true)
                }
            } else {
                if ( savedProfile && !savedProfile.some((profile) => profile.default_board_name.value === pinterestItem.default_board_name.value)) {
                    pinterestItem.status = profileStatus;
                    setSavedProfile((prevItems) => [...prevItems, pinterestItem]);
                    setIsErrorMessage(false)
                }else{
                    pinterestItem.status = profileStatus;
                    setSavedProfile((prevItems) => [...prevItems, pinterestItem]);
                }
            }
        }else if( event === 'save-edit' ) {
            const pinterestEditedItem = savedProfile.map(profile => {
                if (profile?.default_board_name?.label === pinterestItem?.default_board_name?.label) {
                  return pinterestItem;
                } else {
                  return profile;
                }
            });
            setSavedProfile( pinterestEditedItem );
            setSelectedProfile( pinterestEditedItem );
        }else{
            setIsErrorMessage(false)
            setSavedProfile((prevItems) => prevItems?.filter((prevItem) => prevItem.default_board_name.value !== pinterestItem.default_board_name.value));
        }
    }

    // useEffect( () => {

    //     setProfileDataModal(isProfileEditModal)
    // },[isProfileEditModal])

    const addSavedProfile = () => {
        setSelectedProfile(savedProfile);
        closeProfileDataModal();
    }
  return (
    <Modal
        isOpen={profileDataModal}
        ariaHideApp={false}
        shouldCloseOnOverlayClick={false}
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
                    <button className="close-button" onClick={closeProfileDataModal}><i className="wpsp-icon wpsp-close"></i></button>
                    <div className="platform-info">
                        <img width={'30px'} src={`${props?.modal?.logo}`} alt={`${props?.label}`} />
                        <h4>{props?.label}</h4>
                    </div>
                </div>
                <div className="modalbody">
                    {isErrorMessage && (
                        <ProAlert />
                    )}
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
                                    platform={type}
                                    data={responseData}
                                    boards={pinterestBoards}
                                    fetchSectionData={fetchSectionData}
                                    noSection={noSection}
                                    addProfileToggle={addPinterestProfileToggle}
                                    savedProfile={addSavedProfile}
                                    singlePinterestBoard={singlePinterestBoard}
                                    setProfileEditModal={setProfileEditModal}
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