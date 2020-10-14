import React, { useState, useEffect } from 'react'
import fetchWP from './../../utils/fetchWP'
import store from './../../redux/store'
import { connect } from 'react-redux'
import { close_redirect_popup } from './../../redux/actions/social.actions'
import { bindActionCreators } from 'redux'
import { useField, Formik, Form, Field, FieldArray } from 'formik'
import Modal from 'react-modal'
import Facebook from './../Facebook'

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
    const [localSocial, setLocalSocial] = useState()
    const [field] = useField(id)
    console.log(field)
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

    const [fbPage, setFbPage] = useState([])
    const [fbGroup, setFbGroup] = useState([])
    const [socialPlatform, setSocialPlatform] = useState('')

    function openModal() {
        setModalIsOpen(true)
    }

    function afterOpenModal() {
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
            if (response.success) {
                setFbPage(response.page)
                setFbGroup(response.group)
                setSocialPlatform(response.type)
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
    function closeModal() {
        setModalIsOpen(false)
        close_redirect_popup()
    }
    return (
        <div className='form-group'>
            <div className='wpscp-social-tab__item-header wpscp-social-tab__item-header--facebook'>
                <div className='entry-icon'>
                    {/* <img src="#" alt="icon" /> */}
                </div>
                <div className='entry-content'>
                    <h3>Facebook</h3>
                    <p>
                        You can enable/disable facebook social share. For
                        details on facebook configuration, check out this Doc
                    </p>
                </div>
                <div className='entry-control'>
                    <div className='checkbox_wrap'>
                        <div className='wpsp_switch'>
                            <input
                                type='checkbox'
                                checked={field.value}
                                name={field.name}
                                onChange={() =>
                                    setFieldValue(field.name, !field.value)
                                }
                            />
                            <span className='wpsp_switch_slider'></span>
                        </div>
                    </div>
                </div>
            </div>
            <div className='wpscp-social-tab__item-list'></div>

            <button
                type='button'
                className='wpscp-social-tab__btn wpscp-social-tab__btn--facebook wpscp-social-tab__btn--addnew-profile'
                onClick={() =>
                    app.type == 'custom'
                        ? openModal(app.platform)
                        : sendAddProfileRequest(app.platform)
                }
            >
                <img src='#' alt='icon' />
                Add New Profile
            </button>

            <Modal
                isOpen={modalIsOpen}
                onAfterOpen={afterOpenModal}
                onRequestClose={closeModal}
                style={customStyles}
                contentLabel='Example Modal'
                ariaHideApp={false}
            >
                {/* <Facebook page={fbPage} group={fbGroup} /> */}

                <FieldArray
                    name='facebook_profile'
                    render={(arrayHelpers) => (
                        <div>
                            <div className='wpsp-modal-social-platform'>
                                <div className='entry-head facebook'>
                                    <img
                                        src='https://itushar.me/dev/wp-content/plugins/wp-scheduled-posts/admin/assets/images/icon-facebook-small-white.png'
                                        alt='logo'
                                    />
                                    <h2 className='entry-head-title'>
                                        Facebook
                                    </h2>
                                </div>
                                <ul>
                                    <li>Pages: </li>
                                    {fbPage.map((item, index) => (
                                        <li
                                            id={'facebook_page_' + index}
                                            key={index}
                                        >
                                            <div className='item-content'>
                                                <div className='entry-thumbnail'>
                                                    <img
                                                        src='https://scontent-lax3-1.xx.fbcdn.net/v/t1.0-1/cp0/p50x50/104447021_103269271446191_8892114688067945178_o.png?_nc_cat=104&amp;_nc_sid=dbb9e7&amp;_nc_ohc=X_6m8nD-nooAX8Duvu3&amp;_nc_ht=scontent-lax3-1.xx&amp;oh=61b337157a9eca69e54506b10d5d42ac&amp;oe=5FAB5877'
                                                        alt='logo'
                                                    />
                                                </div>
                                                <h4 className='entry-title'>
                                                    {item.name}
                                                </h4>
                                                <div className='control'>
                                                    {console.log(field)}
                                                    <input
                                                        type='checkbox'
                                                        name={`${field.name}.${index}`}
                                                        onChange={(e) => {
                                                            if (
                                                                e.target.checked
                                                            ) {
                                                                return arrayHelpers.insert(
                                                                    index,
                                                                    item
                                                                )
                                                            } else {
                                                                return arrayHelpers.remove(
                                                                    index
                                                                )
                                                            }
                                                        }}
                                                    />
                                                    <div></div>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}
                />

                <button onClick={closeModal}>close</button>
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
