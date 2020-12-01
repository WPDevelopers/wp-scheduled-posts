import React, { useState, useEffect } from 'react'
import { __ } from '@wordpress/i18n'
import { toast } from 'react-toastify'
import { wpspSettingsGlobal, wpspGetPluginRootURI } from './../../utils/helper'
import Upgrade from './../Upgrade'
const License = () => {
    const [inputChanged, setInputChanged] = useState(false)
    const [errorMessage, setErrorMessage] = useState('')
    const [tempKey, setTempKey] = useState(
        localStorage.getItem('wpsp_temp_key')
    )
    const [valid, setValid] = useState(localStorage.getItem('wpsp_is_valid'))
    const [isRequestSend, setIsRequestSend] = useState(null)
    useEffect(() => {
        if (!localStorage.getItem('wpsp_is_valid')) {
            var data = {
                action: 'get_license',
                _wpnonce: wpscp_pro_ajax_object.license_nonce,
            }
            jQuery.post(ajaxurl, data, function (response) {
                if (response.success === true) {
                    localStorage.setItem('wpsp_is_valid', response.data.status)
                    localStorage.setItem('wpsp_temp_key', response.data.key)
                    setValid(response.data.status)
                    setTempKey(response.data.key)
                }
            })
        }
    }, [])

    const activeLicense = () => {
        setIsRequestSend(true)
        var data = {
            action: 'activate_license',
            key: tempKey,
            _wpnonce: wpscp_pro_ajax_object.license_nonce,
        }
        jQuery.post(ajaxurl, data, function (response) {
            setIsRequestSend(null)
            setInputChanged(false)
            if (response.success === true) {
                localStorage.setItem('wpsp_is_valid', response.data.status)
                localStorage.setItem('wpsp_temp_key', response.data.key)
                setTempKey(response.data.key)
                setValid(response.data.status)
                toast.success(
                    <div>
                        <span className='dashicons dashicons-yes-alt'></span>
                        {__(
                            'Your License successfully activated!',
                            'wp-scheduled-posts'
                        )}
                    </div>,
                    {
                        position: 'top-right',
                        autoClose: 5000,
                        hideProgressBar: true,
                        closeOnClick: true,
                        pauseOnHover: true,
                        draggable: true,
                        progress: undefined,
                    }
                )
            } else {
                setErrorMessage(response.data)
            }
        })
    }
    const deactiveLicense = () => {
        setIsRequestSend(true)
        var data = {
            action: 'deactivate_license',
            _wpnonce: wpscp_pro_ajax_object.license_nonce,
        }
        jQuery.post(ajaxurl, data, function (response) {
            setIsRequestSend(null)
            setInputChanged(false)
            if (response.success === true) {
                localStorage.removeItem('wpsp_is_valid')
                localStorage.removeItem('wpsp_temp_key')
                setValid(response.data.status)
                setTempKey('')
                toast.success(
                    <div>
                        <span className='dashicons dashicons-yes-alt'></span>
                        {__(
                            'Your License successfully deactivated!',
                            'wp-scheduled-posts'
                        )}
                    </div>,
                    {
                        position: 'top-right',
                        autoClose: 5000,
                        hideProgressBar: true,
                        closeOnClick: true,
                        pauseOnHover: true,
                        draggable: true,
                        progress: undefined,
                    }
                )
            }
        })
    }
    const changed = (value) => {
        setInputChanged(true)
        setTempKey(value)
    }
    return (
        <React.Fragment>
            <Upgrade
                icon={wpspGetPluginRootURI + 'assets/images/wpsp.png'}
                proVersion={wpspSettingsGlobal.pro_version}
            />
            <div className='wpsp-license-wrapper'>
                {valid == 'valid' ? (
                    <div className='validated-feature-list'>
                        <div className='validated-feature-list-item'>
                            <div className='validated-feature-list-icon'>
                                <img
                                    src={
                                        wpspGetPluginRootURI +
                                        'assets/images/auto_update.png'
                                    }
                                    alt='Auto Update'
                                />
                            </div>
                            <div className='validated-feature-list-content'>
                                <h4>
                                    {__('Auto Update', 'wp-scheduled-posts')}
                                </h4>
                                <p>
                                    {__(
                                        'Update the plugin right from your WordPress Dashboard.',
                                        'wp-scheduled-posts'
                                    )}
                                </p>
                            </div>
                        </div>

                        <div className='validated-feature-list-item'>
                            <div className='validated-feature-list-icon'>
                                <img
                                    src={
                                        wpspGetPluginRootURI +
                                        'assets/images/premium_support.png'
                                    }
                                    alt='Premium Support'
                                />
                            </div>
                            <div className='validated-feature-list-content'>
                                <h4>
                                    {__(
                                        'Premium Support',
                                        'wp-scheduled-posts'
                                    )}
                                </h4>
                                <p>
                                    {__(
                                        'Supported by professional and courteous staff.',
                                        'wp-scheduled-posts'
                                    )}
                                </p>
                            </div>
                        </div>
                    </div>
                ) : (
                    <React.Fragment>
                        <div className='wpsp-lockscreen'>
                            <div className='wpsp-lockscreen-icons'>
                                <img
                                    src={
                                        wpspGetPluginRootURI +
                                        'assets/images/lock_close.png'
                                    }
                                    alt='Lock Close'
                                />
                                <img
                                    src={
                                        wpspGetPluginRootURI +
                                        'assets/images/forward.png'
                                    }
                                    alt='Forwards'
                                />
                                <img
                                    src={
                                        wpspGetPluginRootURI +
                                        'assets/images/lock_key.png'
                                    }
                                    alt='Lock Key'
                                />
                                <img
                                    src={
                                        wpspGetPluginRootURI +
                                        'assets/images/forward.png'
                                    }
                                    alt='Forwards'
                                />
                                <img
                                    src={
                                        wpspGetPluginRootURI +
                                        'assets/images/lock_open.png'
                                    }
                                    alt='Lock Open'
                                />
                            </div>
                            <h1 className='wpsp-validation-title'>
                                <strong>
                                    {__(
                                        'Just one more step to go!',
                                        'wp-scheduled-posts'
                                    )}
                                </strong>
                            </h1>
                        </div>
                        <div className='wpsp-license-instruction'>
                            <p>
                                {__(
                                    'Enter your license key here, to activate',
                                    'wp-scheduled-posts'
                                )}
                                <strong>
                                    {__('SchedulePress', 'wp-scheduled-posts')}
                                </strong>
                                {__(
                                    ', and get automatic updates and premium support.',
                                    'wp-scheduled-posts'
                                )}
                            </p>
                            <p>
                                {__('Visit the', 'wp-scheduled-posts')}
                                <a
                                    href='https://wpdeveloper.net/docs/wp-scheduled-posts/'
                                    target='_blank'
                                >
                                    {__(
                                        'Validation Guide',
                                        'wp-scheduled-posts'
                                    )}
                                </a>
                                {__('for help.', 'wp-scheduled-posts')}
                            </p>

                            <ol>
                                <li>
                                    {__('Log in to', 'wp-scheduled-posts')}
                                    <a
                                        href='https://wpdeveloper.net/account/'
                                        target='_blank'
                                    >
                                        {__(
                                            'your account',
                                            'wp-scheduled-posts'
                                        )}
                                    </a>
                                    {__(
                                        'to get your license key.',
                                        'wp-scheduled-posts'
                                    )}
                                </li>
                                <li>
                                    {__(
                                        "If you don't yet have a license key, get",
                                        'wp-scheduled-posts'
                                    )}
                                    <a
                                        href='https://wpdeveloper.net/in/wpsp'
                                        target='_blank'
                                    >
                                        {__(
                                            'SchedulePress now.',
                                            'wp-scheduled-posts'
                                        )}
                                    </a>
                                </li>
                                <li>
                                    {__(
                                        'Copy the license key from your account and paste it below.',
                                        'wp-scheduled-posts'
                                    )}
                                </li>
                                <li>
                                    {__('Click on', 'wp-scheduled-posts')}
                                    <strong>
                                        {__(
                                            ' "Activate License" ',
                                            'wp-scheduled-posts'
                                        )}
                                    </strong>
                                    {__('button.', 'wp-scheduled-posts')}
                                </li>
                            </ol>
                        </div>
                    </React.Fragment>
                )}

                <div className='wpsp-license-container'>
                    <div className='wpsp-license-icon'>
                        <img
                            src={
                                wpspGetPluginRootURI +
                                'assets/images/activate.png'
                            }
                            alt='Active'
                        />
                    </div>
                    <div className='wpsp-license-input'>
                        {tempKey && valid == 'valid' ? (
                            <input
                                id='wp-scheduled-posts-pro-license-key'
                                placeholder='Place Your License Key and Activate'
                                onChange={(e) => changed(e.target.value)}
                                value={tempKey}
                                disabled='disabled'
                            />
                        ) : (
                            <input
                                id='wp-scheduled-posts-pro-license-key'
                                placeholder='Place Your License Key and Activate'
                                onChange={(e) => changed(e.target.value)}
                                value={tempKey !== false ? tempKey : ''}
                            />
                        )}
                    </div>
                    <div className='wpsp-license-buttons'>
                        {valid == 'valid' ? (
                            <button
                                id='submit'
                                type='button'
                                className={
                                    inputChanged
                                        ? 'wpsp-license-deactivation-btn changed'
                                        : 'wpsp-license-deactivation-btn'
                                }
                                onClick={() => deactiveLicense()}
                            >
                                {isRequestSend == true
                                    ? 'Request Sending...'
                                    : 'Deactivate License'}
                            </button>
                        ) : (
                            <button
                                id='submit'
                                type='button'
                                className={
                                    inputChanged
                                        ? 'wpsp-license-buttons changed'
                                        : 'wpsp-license-buttons'
                                }
                                onClick={() => activeLicense()}
                                disabled={!tempKey}
                            >
                                {isRequestSend == true
                                    ? 'Request Sending...'
                                    : 'Activate License'}
                            </button>
                        )}
                    </div>
                </div>
                <p className='error-message'>{errorMessage}</p>
            </div>
        </React.Fragment>
    )
}

export default License
