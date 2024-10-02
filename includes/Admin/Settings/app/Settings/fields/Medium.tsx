import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import { useBuilderContext } from 'quickbuilder';
import React, { useEffect, useState } from 'react';
import Modal from 'react-modal';
import Swal from 'sweetalert2';
import { SweetAlertDeleteMsg, SweetAlertToaster } from '../ToasterMsg';
import { fetchDataFromAPI, socialProfileRequestHandler } from '../helper/helper';
import ApiCredentialsForm from './Modals/ApiCredentialsForm';
import SocialModal from './Modals/SocialModal';
import MainProfile from './utils/MainProfile';
import SelectedProfile from './utils/SelectedProfile';
import ViewMore from './utils/ViewMore';

const Medium = (props) => {
    const propsValue = props?.value || [];
    const sortedSelectedValue  = [...propsValue].sort((a, b) => {
        return b.status - a.status; // Sort in descending order by status
    });
    const cachedLocalData = JSON.parse(localStorage.getItem('instagram'));
    const builderContext = useBuilderContext();
    const [apiCredentialsModal, setApiCredentialsModal] = useState(false);
    const [showProfileInfo, setShowProfileInfo] = useState(false);
    const [profileInfo, setProfileInfo] = useState({});
    const [selectedProfileViewMore, setSelectedProfileViewMore] = useState(false);
    const [platform, setPlatform] = useState('');
    const [savedProfile,setSavedProfile] = useState(props?.value ?? []);
    const [selectedProfile, setSelectedProfile] = useState( sortedSelectedValue ?? [] );
    const [cachedStatus, setCashedStatus] = useState(cachedLocalData ?? {});
    const [activeStatusCount,setActiveStatusCount] = useState(0);
    const [profileStatus, setProfileStatus] = useState(
        builderContext?.savedValues?.medium_profile_status
    );

    useEffect(() => {
        onChange({
            target: {
                type: 'checkbox-select',
                name: 'medium_profile_status',
                value: profileStatus,
            },
            });    
        }, [profileStatus]);
        const [isErrorMessage, setIsErrorMessage] = useState(false)
        localStorage.setItem('medium',JSON.stringify(cachedStatus));

        // prepare appId and appSecret
        let appInfo = [];
        if( props?.value ) {
        props?.value?.map( ( profile ) => {
            if( profile['app_id'] && profile['app_secret'] ) {
            appInfo['app_id'] = profile['app_id'];
            appInfo['app_secret'] = profile['app_secret'];
            }
        } );
    }

    const handleDeleteSelectedProfile = (item) => {
        SweetAlertDeleteMsg({ item }, deleteSelectedProfile);
    };

    const deleteSelectedProfile = (item) => {
        const updateSelectedProfile = selectedProfile.filter(
        (selectedItem) => selectedItem.id !== item.id
        );
        setSelectedProfile(updateSelectedProfile);
    };

    // Handle profile & selected profile status onChange event
    const handleProfileStatusChange = (event) => {
        setProfileStatus(event.target.checked);
        const changeProfileStatus = selectedProfile.map((selectedItem) => {
        if (!event.target.checked) {
            setCashedStatus((prevStatus) => {
            return { ...prevStatus, [selectedItem.id]: selectedItem?.status };
            });
            return {
            ...selectedItem,
            status: false,
            };
        } else {
            return {
            ...selectedItem,
            status : (cachedStatus?.[selectedItem.id] == undefined) ?  false : cachedStatus?.[selectedItem.id], 
            };
        }
        });
        setSelectedProfile(changeProfileStatus);
    };
    
    // @ts-ignore
    const is_pro = wpspSettingsGlobal?.pro_version ? true : false;
    // Open and Close API credentials modal
    const openApiCredentialsModal = (accountType) => {
        localStorage.setItem('account_type', accountType);
        setPlatform('medium');
        setApiCredentialsModal(true);
    };
    const closeApiCredentialsModal = () => {
        setApiCredentialsModal(false);
    };
    
    const handleSelectedProfileStatusChange = (item, event) => {
        if (event.target.checked) {
        setProfileStatus(true);
        }
        setCashedStatus((prevStatus) => {
        if( is_pro ) {
            return { ...prevStatus, [item.id]: event.target.checked };
        }else{
            return { [item.id]: event.target.checked };
        }
        });
        if ( is_pro ) {
        const updatedData = selectedProfile.map((selectedItem) => {
            if (selectedItem.id === item.id) {
            return {
                ...selectedItem,
                status: event.target.checked,
            };
            }
            return selectedItem;
        });
        setSelectedProfile(updatedData);
        }else{
        if( activeStatusCount <= 1 ) {
            let currentStatus = event.target.checked;
            if( activeStatusCount === 1 && currentStatus ) {
            Swal.fire({
                title: __('Are you sure?','wp-scheduled-posts'),
                text: __('Enabling this profile will deactivate other profile automatically.','wp-scheduled-posts'),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                cancelButtonText: '<i class="wpsp-icon wpsp-close"></i>',
                confirmButtonText: __('Yes, Enable it!', 'wp-scheduled-posts'),
            }).then((result) => {
                if (result.isConfirmed) {
                    const updatedData = selectedProfile.map((selectedItem) => {
                    return {
                        ...selectedItem,
                        status: selectedItem.id === item.id ? currentStatus : false,
                    };
                });
                setSelectedProfile(updatedData);
                }
            })
            }else{
            const updatedData = selectedProfile.map((selectedItem) => {
                return {
                    ...selectedItem,
                    status: selectedItem.id === item.id ? currentStatus : false,
                };
            });
            setSelectedProfile(updatedData);
            }
        }
        }
    };
    
    // Save selected profile data
    useEffect(() => {
        builderContext.setFieldValue([props.name], selectedProfile);
        let count = 0;
        if( selectedProfile ) {
            selectedProfile.forEach(element => {
                if( element.status ) {
                    count++;
                }
                setActiveStatusCount( count );
            });
        }
    }, [selectedProfile]);

    // Save profile status data
    let { onChange } = props;
    useEffect(() => {
        onChange({
        target: {
            type: 'checkbox-select',
            name: 'medium_profile_status',
            value: profileStatus,
        },
        });    
    }, [profileStatus]);

    let selectedProfileData = [];
    if (selectedProfile && selectedProfileViewMore) {
        selectedProfileData = selectedProfile;
    } else if (selectedProfile && !selectedProfileViewMore) {
        selectedProfileData = selectedProfile.slice(0, 2);
    }
    const handleMediumFetchProfile = async (redirectURI, appID, appSecret, platform, openIDConnect = false) => {
        const account_type = localStorage.getItem('account_type');
        // @ts-ignore 
        const nonce = wpspSettingsGlobal?.api_nonce;
        const data = {
            action       : 'wpsp_social_add_social_profile',
            nonce        : nonce,
            redirectURI  : redirectURI,
            appId        : appID,
            appSecret    : appSecret,
            type         : platform,
            openIDConnect: openIDConnect,
            accountType  : account_type,
        };
        const response = await fetchDataFromAPI(data);
        const responseData = await response.json();
        if( responseData?.data?.name && responseData.success )  {
            setShowProfileInfo(true);
            setApiCredentialsModal(false);
            setProfileInfo(responseData?.data);   
        } else {
            SweetAlertToaster({
                type : 'error',
                title : responseData?.data.message ?  responseData?.data.message : __( 'Please check your access token and try again.', 'wp-scheduled-posts' ),
            }).fire();
        }
    };

    const addMediumProfile = (event, profileInfo) => {
        if( event.target.checked ) {
            // free
            // @ts-ignore
            if (!builderContext.is_pro_active) {
                // @ts-ignore
                if (!savedProfile || (savedProfile && savedProfile.length == 0)) {
                    setIsErrorMessage(false)
                    if (!savedProfile.some((profile) => profile.id === profileInfo.id)) {
                        profileInfo.status = profileStatus;
                        setSavedProfile((prevItems) => [...prevItems, profileInfo]);
                    }
                } else {
                    event.target.checked = false;
                    setIsErrorMessage(true)
                }
            } else {
                if ( savedProfile && !savedProfile.some((profile) => profile.id === profileInfo.id)) {
                    profileInfo.status = profileStatus;
                    let updatedSavedProfile = savedProfile.map(savedItem => {
                        if (savedItem.id === profileInfo.id) {
                            return { ...savedItem, access_token: 'token' };
                        }
                        return savedItem;
                        
                    });
                    updatedSavedProfile.push(profileInfo);
                    setSavedProfile(updatedSavedProfile);
                    setIsErrorMessage(false)
                }
            }
        }else{
            setIsErrorMessage(false)
            setSavedProfile((prevItems) => prevItems.filter((prevItem) => prevItem.id !== profileInfo.id));
        }
    }
    
    const addSavedProfile = () => {
        setSelectedProfile(savedProfile);
        setShowProfileInfo(false);
    }

    return (
        <div
            className={classNames(
                'wprf-control',
                'wprf-social-profile',
                `wprf-${props.name}-social-profile`,
                props?.classes
            )}>
            <div className="social-profile-card">
                <div className="main-profile">
                <MainProfile
                    props={props}
                    handleProfileStatusChange={handleProfileStatusChange}
                    profileStatus={profileStatus}
                    openApiCredentialsModal={openApiCredentialsModal}
                />
                </div>
                <div className="selected-profile">
                    {(!selectedProfile || selectedProfile.length == 0) && (
                        <img
                        className="empty-image"
                        /* @ts-ignore */
                        src={`${wpspSettingsGlobal?.image_path}EmptyCard.svg`}
                        alt="mainLogo"
                        />
                    )}
                    <div className="selected-facebook-scrollbar">
                        {selectedProfile &&
                            selectedProfileData.map((item, index) => (
                                <div
                                className="selected-facebook-wrapper"
                                key={index}>
                                <SelectedProfile
                                    platform={'medium'}
                                    item={item}
                                    handleSelectedProfileStatusChange={
                                        handleSelectedProfileStatusChange
                                    }
                                    handleDeleteSelectedProfile={handleDeleteSelectedProfile}
                                    handleEditSelectedProfile={''}
                                    profileStatus={profileStatus}
                                />
                                </div>
                        ))}
                    </div>
                    { ( !selectedProfileViewMore && selectedProfile && selectedProfile.length >= 3) && (
                        <ViewMore setSelectedProfileViewMore={setSelectedProfileViewMore} />
                    ) }
                </div>
            </div>
            {/* API Credentials Modal  */}
            <Modal
                isOpen={apiCredentialsModal}
                onRequestClose={closeApiCredentialsModal}
                ariaHideApp={false}
                shouldCloseOnOverlayClick={false}
                className="modal_wrapper">
                <button
                    className="close-button"
                    onClick={closeApiCredentialsModal}>
                    <i className="wpsp-icon wpsp-close"></i>
                </button>
                    <ApiCredentialsForm
                        props={props}
                        platform={platform}
                        requestHandler={ handleMediumFetchProfile }
                        appInfo={appInfo}
                    />
            </Modal>
            {/* @ts-ignore */}
            <Modal
                isOpen={showProfileInfo}
                onRequestClose={showProfileInfo}
                ariaHideApp={false}
                shouldCloseOnOverlayClick={false}
                className="modal_wrapper">
                <div className="modalhead">
                    <button
                        className="close-button"
                        onClick={() => setShowProfileInfo(false)}>
                        <i className="wpsp-icon wpsp-close"></i>
                    </button>
                    <div className="platform-info">
                        <img width={'30px'} src={`${props?.modal?.logo}`} alt={`${props?.label}`} />
                        <h4>{props?.label}</h4>
                    </div>
                </div>
                <div className={`wpsp-modal-social-platform wpsp-modal-social-${platform}`}>
                { profileInfo && 
                    <ul>
                            <li key='1'>
                                <div className='item-content'>
                                    <div className='entry-thumbnail'>
                                        <img
                                            // @ts-ignore 
                                            src={profileInfo?.thumbnail_url}
                                            alt='logo'
                                        />
                                        <h4 className='entry-title'>
                                            {/* @ts-ignore  */}
                                            { profileInfo?.name }
                                        </h4>
                                    </div>
                                    <div className='control'>
                                        <input
                                            type='checkbox'
                                            onChange={(e) =>
                                                addMediumProfile(
                                                    e,
                                                    profileInfo
                                                )
                                            }
                                        />
                                        <div></div>
                                    </div>
                                </div>
                            </li>
                    </ul>
                }
                <button
                    type="submit"
                    className="wpsp-modal-save-account"
                    onClick={(event) => {
                        event.preventDefault();
                        addSavedProfile()
                    }}
                >{ __( 'Save','wp-scheduled-posts' ) }</button>
                </div>
            </Modal>
        </div>
    )
}

export default Medium