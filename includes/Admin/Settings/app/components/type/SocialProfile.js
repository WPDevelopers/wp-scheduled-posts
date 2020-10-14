import React, { useState, useEffect } from 'react'
import fetchWP from './../../utils/fetchWP'
import store from './../../redux/store'
import { connect } from 'react-redux'
import { close_redirect_popup } from './../../redux/actions/social.actions'
import { bindActionCreators } from 'redux'
import { useField } from 'formik'
import Modal from 'react-modal'

const customStyles = {
    content: {
        top: '50%',
        left: '50%',
        right: 'auto',
        bottom: 'auto',
        marginRight: '-50%',
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

    var subtitle

    function openModal() {
        setModalIsOpen(true)
    }

    function afterOpenModal() {
        // references are now sync'd and can be accessed.
        subtitle.style.color = '#f00'
    }
    const processOkResponse = (json, action) => {
        if (json.success) {
            console.log(json.value)
        } else {
            console.log(`Setting was not ${action}.`, json)
        }
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
                that.html(btnInnerDom)
                wpscpUpgradeAlert(response.data)
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
                <h2 ref={(_subtitle) => (subtitle = _subtitle)}>Hello</h2>
                <button onClick={closeModal}>close</button>
                <div>I am a modal</div>
                <form>
                    <input />
                    <button>tab navigation</button>
                    <button>stays</button>
                    <button>inside</button>
                    <button>the modal</button>
                </form>
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
