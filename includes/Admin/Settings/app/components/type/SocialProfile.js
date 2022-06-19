import React, { useState, useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import store from './../../redux/store'
import { connect } from 'react-redux'
import { close_redirect_popup } from './../../redux/actions/social.actions'
import { bindActionCreators } from 'redux'
import { useField, FieldArray } from 'formik'
import { wpspSettingsGlobal, wpspGetPluginRootURI } from './../../utils/helper'
import Modal from 'react-modal'
import SocialTabHeader from './../Social/SocialTabHeader'
import Facebook from './../Facebook'
import Profile from './../Social/Profile'
import ListItemProfile from './../Social/ListItemProfile'
import CustomAppForm from './../../components/CustomAppForm'
import Pinterest from '../Pinterest'

const customStyles = {
    overlay: {
        background: 'rgba(1, 17, 50, 0.7)',
    },
    content: {
        top: '50%',
        left: '50%',
        right: 'auto',
        bottom: 'auto',
        marginRight: '-50%',
        width: '450px',
        transform: 'translate(-50%, -50%)',
    },
}

const SocialProfile = ({ id, app, setFieldValue, close_redirect_popup }) => {
    const [modalIsOpen, setModalIsOpen] = useState(false)
    const [
        modalMultiProfileErrorIsOpen,
        setModalMultiProfileErrorIsOpen,
    ] = useState(false)
    const [multiProfileErrorMessage, setMultiProfileErrrorMessage] = useState(
        false
    )
    const [customAppModalIsOpen, setCustomAppModalIsOpen] = useState(false)
    const [localSocial, setLocalSocial] = useState()
    const [requestSending, setRequestSending] = useState(false)
    const [fbPage, setFbPage] = useState([])
    const [fbGroup, setFbGroup] = useState([])
    const [pinterestBoards, setPinterestBoards] = useState([])
    const [responseData, setResponseData] = useState([])
    const [socialPlatform, setSocialPlatform] = useState('')
    const [field] = useField(id)
    const [fieldStatus] = useField(field.name + '_status')
    const [fieldList] = useField(field.name + '_list')

    useEffect(() => {
        setLocalSocial(store.getState('social'))
        if (
            localSocial !== undefined &&
            localSocial.social.redirectFromOauth === true
        ) {
            setModalIsOpen(true)
            // remove unnecessary query string
            if (history.pushState) {
                history.pushState(
                    null,
                    null,
                    window.location.href.split('&')[0]
                )
            }
        }
    }, [localSocial])

    function afterOpenModal() {
        setRequestSending(true)
        setSocialPlatform(localSocial.social.queryString.get('type'))
        /**
         * send ajax requrest for generate access token and fetch user, page info
         */
        var data = {
            action: 'wpsp_social_profile_fetch_user_info_and_token',
            type: localSocial.social.queryString.get('type'),
            code: localSocial.social.queryString.get('code'),
            appId: localSocial.social.queryString.get('appId'),
            appSecret: localSocial.social.queryString.get('appSecret'),
            oauthVerifier: localSocial.social.queryString.get('oauth_verifier'),
            oauthToken: localSocial.social.queryString.get('oauth_token'),
        }
        jQuery.post(ajaxurl, data, function (response) {
            setRequestSending(false)
            if (response.success) {
                setFbPage(response.page)
                setFbGroup(response.group)
                setResponseData([response.data])
                setPinterestBoards(response.boards)
            } else {
            }
        })
    }

    const customAppProfileRequest = (redirectURI, appID, appSecret) => {
        var data = {
            action: 'wpsp_social_add_social_profile',
            redirectURI: redirectURI,
            appId: appID,
            appSecret: appSecret,
            type: app.platform,
        }

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        jQuery.post(ajaxurl, data, function (response) {
            if (response.success) {
                open(response.data, '_self')
            } else {
                let message;
                try {
                    let _message = JSON.parse(response.data);
                    if(_message?.errors?.[0]?.message){
                        message = _message.errors[0].message;
                    }
                    else{
                        message = response.data;
                    }
                } catch (e) {
                    message = response.data;
                }
                setMultiProfileErrrorMessage(message)
                setModalMultiProfileErrorIsOpen(true)
                setCustomAppModalIsOpen(false)
            }
        })
    }

    const openCustomAppModal = () => {
        setCustomAppModalIsOpen(true)
    }
    const closeCustomAppModalIsOpen = () => {
        setCustomAppModalIsOpen(false)
    }

    const closeModalMultiProfileErrorIsOpen = () => {
        setModalMultiProfileErrorIsOpen(false)
    }

    function closeModal() {
        setModalIsOpen(false)
        close_redirect_popup()
    }

    return (
        <div className='form-group'>
            <SocialTabHeader
                socialPlatform={app.platform}
                field={fieldStatus}
                setFieldValue={setFieldValue}
            />
            {fieldList.value !== undefined &&
                Array.isArray(fieldList.value) && (
                    <div className='wpscp-social-tab__item-list'>
                        {wpspSettingsGlobal.pro_version ? (
                            <FieldArray
                                name={fieldList.name}
                                render={(arrayHelpers) =>
                                    fieldList.value.map((item, index) => (
                                        <ListItemProfile
                                            groupFieldStatus={fieldStatus}
                                            fieldList={fieldList}
                                            arrayHelpers={arrayHelpers}
                                            item={item}
                                            key={index}
                                            index={index}
                                        />
                                    ))
                                }
                            />
                        ) : (
                            <FieldArray
                                name={fieldList.name}
                                render={(arrayHelpers) =>
                                    fieldList.value
                                        .slice(0, 1)
                                        .map((item, index) => (
                                            <ListItemProfile
                                                groupFieldStatus={fieldStatus}
                                                fieldList={fieldList}
                                                arrayHelpers={arrayHelpers}
                                                item={item}
                                                key={index}
                                                index={index}
                                            />
                                        ))
                                }
                            />
                        )}
                    </div>
                )}

            <button
                type='button'
                className={
                    'wpscp-social-tab__btn wpscp-social-tab__btn--' +
                    app.platform +
                    ' wpscp-social-tab__btn--addnew-profile'
                }
                onClick={() => openCustomAppModal(app.platform)}
            >
                <img
                    src={
                        wpspGetPluginRootURI +
                        'assets/images/icon-' +
                        app.platform +
                        '.png'
                    }
                    alt='icon'
                />
                {__('Add New Profile', 'wp-scheduled-posts')}
            </button>
            <Modal
                isOpen={customAppModalIsOpen}
                onRequestClose={closeCustomAppModalIsOpen}
                style={customStyles}
                ariaHideApp={false}
            >
                <CustomAppForm
                    platform={app.platform}
                    requestHandler={customAppProfileRequest}
                />
            </Modal>
            <Modal
                isOpen={modalMultiProfileErrorIsOpen}
                onRequestClose={closeModalMultiProfileErrorIsOpen}
                style={customStyles}
                ariaHideApp={false}
            >
                <div className='wpsp-mulit-profile-error-message'>
                    <div>
                        <img
                            src={
                                wpspGetPluginRootURI +
                                'assets/images/soft-warning.png'
                            }
                            alt='warning'
                        />
                    </div>
                    <h2
                        dangerouslySetInnerHTML={{
                            __html: multiProfileErrorMessage,
                        }}
                    ></h2>
                </div>
            </Modal>

            {/* after auth then it will fire */}
            <Modal
                isOpen={modalIsOpen}
                onAfterOpen={afterOpenModal}
                onRequestClose={closeModal}
                style={customStyles}
                ariaHideApp={false}
            >
                {requestSending ? (
                    <div className='wpsp-modal-info'>
                        {__(
                            'Generating Token & Fetching User Data',
                            'wp-scheduled-posts'
                        )}
                    </div>
                ) : (
                    <React.Fragment>
                        {
                            {
                                facebook: (
                                    <Facebook
                                        fieldName={fieldList.name}
                                        field={fieldList}
                                        page={fbPage}
                                        group={fbGroup}
                                    />
                                ),
                                twitter: (
                                    <Profile
                                        fieldName={fieldList.name}
                                        field={fieldList}
                                        platform={socialPlatform}
                                        data={responseData}
                                    />
                                ),
                                linkedin: (
                                    <Profile
                                        fieldName={fieldList.name}
                                        field={fieldList}
                                        platform={socialPlatform}
                                        data={responseData}
                                    />
                                ),
                                pinterest: (
                                    <Pinterest
                                        fieldName={fieldList.name}
                                        field={fieldList}
                                        platform={socialPlatform}
                                        data={responseData}
                                        boards={pinterestBoards}
                                    />
                                ),
                            }[socialPlatform]
                        }
                        <button
                            className='wpsp-modal-save-close-button'
                            type='submit'
                            onClick={closeModal}
                        >
                            {__('Close', 'wp-scheduled-posts')}
                        </button>
                    </React.Fragment>
                )}
            </Modal>
        </div>
    )
}

const mapDispatchToProps = (dispatch) => {
    return {
        close_redirect_popup: bindActionCreators(
            close_redirect_popup,
            dispatch
        ),
    }
}

export default connect(null, mapDispatchToProps)(SocialProfile)
