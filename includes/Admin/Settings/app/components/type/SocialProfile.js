import React, { useState, useEffect } from 'react'
import fetchWP from './../../utils/fetchWP'
import store from './../../redux/store'
import { connect } from 'react-redux'
import { close_redirect_popup } from './../../redux/actions/social.actions'
import { bindActionCreators } from 'redux'
import { useField, Formik, Form, Field, FieldArray } from 'formik'
import Modal from 'react-modal'
import SocialTabHeader from './../Social/SocialTabHeader'
import Facebook from './../Facebook'
import Profile from './../Social/Profile'
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

const SocialProfile = ({
    id,
    title,
    subtitle,
    desc,
    app,
    setFieldValue,
    redirectFromOauth,
    close_redirect_popup,
}) => {
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
            // if (history.pushState) {
            //     history.pushState(
            //         null,
            //         null,
            //         window.location.href.split('&')[0]
            //     )
            // }
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
            console.log(response)
            if (response.success) {
                open(response.data, '_self')
            } else {
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
            />

            <div className='wpscp-social-tab__item-list'>
                {fieldList.value !== undefined &&
                    Array.isArray(fieldList.value) &&
                    fieldList.value.map((item, index) => (
                        <div
                            className='wpscp-social-tab__item-list__single_item'
                            key={index}
                        >
                            <div className='entry-thumbnail'>
                                <img src={item.thumbnail_url} alt='icon' />
                            </div>
                            <div className='entry-content'>
                                <h4 className='entry-content__title'>
                                    {item.name}
                                </h4>
                                <p className='entry-content__doc'>
                                    <strong>{item.added_by}</strong>
                                    on
                                    {item.added_date}
                                </p>
                            </div>
                            <div className='entry-control'>
                                <div className='checkbox-toggle'>
                                    <input
                                        type='checkbox'
                                        className='wpsp_field_activate'
                                        checked={item.status}
                                    />
                                    <svg
                                        className='is_checked'
                                        xmlns='http://www.w3.org/2000/svg'
                                        viewBox='0 0 426.67 426.67'
                                    >
                                        <path d='M153.504 366.84c-8.657 0-17.323-3.303-23.927-9.912L9.914 237.265c-13.218-13.218-13.218-34.645 0-47.863 13.218-13.218 34.645-13.218 47.863 0l95.727 95.727 215.39-215.387c13.218-13.214 34.65-13.218 47.86 0 13.22 13.218 13.22 34.65 0 47.863L177.435 356.928c-6.61 6.605-15.27 9.91-23.932 9.91z' />
                                    </svg>
                                    <svg
                                        className='is_unchecked'
                                        xmlns='http://www.w3.org/2000/svg'
                                        viewBox='0 0 212.982 212.982'
                                    >
                                        <path
                                            d='M131.804 106.49l75.936-75.935c6.99-6.99 6.99-18.323 0-25.312-6.99-6.99-18.322-6.99-25.312 0L106.49 81.18 30.555 5.242c-6.99-6.99-18.322-6.99-25.312 0-6.99 6.99-6.99 18.323 0 25.312L81.18 106.49 5.24 182.427c-6.99 6.99-6.99 18.323 0 25.312 6.99 6.99 18.322 6.99 25.312 0L106.49 131.8l75.938 75.937c6.99 6.99 18.322 6.99 25.312 0 6.99-6.99 6.99-18.323 0-25.313l-75.936-75.936z'
                                            fill-rule='evenodd'
                                            clip-rule='evenodd'
                                        />
                                    </svg>
                                </div>
                                <div className='entry-control__more-link'>
                                    <button className='btn-more-link'>
                                        <img
                                            src='images/icon-more.png'
                                            alt='more item'
                                        />
                                    </button>
                                    <ul className='entry-control__more-link__group_absolute'>
                                        <li>
                                            <button className='btn btn-refresh'>
                                                Refresh
                                            </button>
                                            <button className='btn btn-remove'>
                                                Remove
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    ))}
            </div>

            <button
                type='button'
                className='wpscp-social-tab__btn wpscp-social-tab__btn--facebook wpscp-social-tab__btn--addnew-profile'
                onClick={() =>
                    app.type == 'custom'
                        ? openCustomAppModal(app.platform)
                        : sendAddProfileRequest(app.platform)
                }
            >
                <img src='#' alt='icon' />
                Add New Profile
            </button>

            <Modal
                isOpen={customAppModalIsOpen}
                onRequestClose={closeCustomAppModalIsOpen}
                style={customStyles}
                ariaHideApp={false}
            >
                <CustomAppForm requestHandler={customAppProfileRequest} />
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
                                pinterest: (
                                    <CustomAppForm
                                        fieldName={fieldList.name}
                                        field={fieldList}
                                        page={fbPage}
                                        group={fbGroup}
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
