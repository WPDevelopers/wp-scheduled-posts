import React, { useState, useEffect } from 'react'
import store from './../../redux/store'
import { connect } from 'react-redux'
import { close_redirect_popup } from './../../redux/actions/social.actions'
import { bindActionCreators } from 'redux'
import { useField, FieldArray } from 'formik'
import { wpspGetPluginRootURI } from './../../utils/helper'
import Modal from 'react-modal'
import SocialTabHeader from './../Social/SocialTabHeader'
import Facebook from './../Facebook'
import Profile from './../Social/Profile'
import ListItemProfile from './../Social/ListItemProfile'
import CustomAppForm from './../../components/CustomAppForm'

const customStyles = {
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
    const [customAppModalIsOpen, setCustomAppModalIsOpen] = useState(false)
    const [localSocial, setLocalSocial] = useState()
    const [requestSending, setRequestSending] = useState(false)
    const [fbPage, setFbPage] = useState([])
    const [fbGroup, setFbGroup] = useState([])
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

    function openModal() {
        setModalIsOpen(true)
    }

    function afterOpenModal() {
        setRequestSending(true)
        setSocialPlatform(localSocial.social.queryString.get('type'))
        /**
         * send ajax requrest for generate access token and fetch user, page info
         */
        var data = {
            action: 'wpscp_social_profile_fetch_user_info_and_token',
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
            } else {
                console.log('error, response: ', response)
            }
        })
    }

    const sendAddProfileRequest = () => {
        var data = {
            action: 'wpscp_social_add_profile',
            type: 'facebook',
        }

        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        jQuery.post(ajaxurl, data, function (response) {
            if (response.success) {
                open(response.data, '_self')
            } else {
                // error message
                console.log(response)
            }
        })
    }

    const customAppProfileRequest = (redirectURI, appID, appSecret) => {
        var data = {
            action: 'wpscp_social_temp_add_profile',
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
                console.log(response)
            }
        })
    }

    const openCustomAppModal = () => {
        setCustomAppModalIsOpen(true)
    }
    const closeCustomAppModalIsOpen = () => {
        setCustomAppModalIsOpen(false)
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
            {fieldList.value !== undefined && Array.isArray(fieldList.value) && (
                <div className='wpscp-social-tab__item-list'>
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
                </div>
            )}

            <button
                type='button'
                className={
                    'wpscp-social-tab__btn wpscp-social-tab__btn--' +
                    app.platform +
                    ' wpscp-social-tab__btn--addnew-profile'
                }
                onClick={() =>
                    app.type == 'custom'
                        ? openCustomAppModal(app.platform)
                        : sendAddProfileRequest(app.platform)
                }
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
                Add New Profile
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

            {/* default app facebook and pinterest */}
            <Modal
                isOpen={modalIsOpen}
                onAfterOpen={afterOpenModal}
                onRequestClose={closeModal}
                style={customStyles}
                contentLabel='Example Modal'
                ariaHideApp={false}
            >
                {requestSending ? (
                    <div className='wpsp-modal-info'>
                        Generating Token & Fetching User Data
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
                                    <Profile
                                        fieldName={fieldList.name}
                                        field={fieldList}
                                        platform={socialPlatform}
                                        data={responseData}
                                    />
                                ),
                            }[socialPlatform]
                        }
                        <button
                            className='wpsp-modal-save-close-button'
                            type='submit'
                            onClick={closeModal}
                        >
                            Close
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
