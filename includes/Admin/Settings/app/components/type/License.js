import React, { useState } from 'react'
import { wpspSettingsGlobal, wpspGetPluginRootURI } from './../../utils/helper'
import Upgrade from './../Upgrade'
const License = ({ id, setFieldValue, value }) => {
    const [tempKey, setTempKey] = useState('')
    const [isRequestSend, setIsRequestSend] = useState(false)
    const activeLicense = () => {
        setIsRequestSend(true)
        var data = {
            action: 'activate_license',
            key: tempKey,
            _wpnonce: wpscp_pro_ajax_object.license_nonce,
        }
        jQuery.post(ajaxurl, data, function (response) {
            setIsRequestSend(null)
            console.log(response)
        })
    }
    const saveKeyValue = (key) => {
        setTempKey(key)
        setFieldValue(id, key)
    }
    return (
        <React.Fragment>
            <Upgrade
                icon={wpspGetPluginRootURI + 'assets/images/wpsp.png'}
                proVersion={wpspSettingsGlobal.pro_version}
            />
            <div className='wpsp-license-wrapper'>
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
                                'assets/images/lock_close.png'
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
                        Just one more step to go!
                    </h1>
                </div>
                <div className='wpsp-license-instruction'>
                    <p>
                        Enter your license key here, to activate{' '}
                        <strong>WP Scheduled Posts</strong>, and get automatic
                        updates and premium support.
                    </p>
                    <p>
                        Visit the{' '}
                        <a href='%s' target='_blank'>
                            Validation Guide
                        </a>{' '}
                        for help.
                    </p>

                    <ol>
                        <li>
                            Log in to{' '}
                            <a href='%s' target='_blank'>
                                your account
                            </a>{' '}
                            to get your license key.
                        </li>
                        <li>
                            If you don\'t yet have a license key, get{' '}
                            <a href='%s' target='_blank'>
                                WP Scheduled Posts now
                            </a>
                            .
                        </li>
                        <li>
                            Copy the license key from your account and paste it
                            below.
                        </li>
                        <li>
                            Click on <strong>"Activate License"</strong> button.
                        </li>
                    </ol>
                </div>

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
                            <h4>Auto Update</h4>
                            <p>
                                Update the plugin right from your WordPress
                                Dashboard.
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
                            <h4>Premium Support</h4>
                            <p>
                                Supported by professional and courteous staff.
                            </p>
                        </div>
                    </div>
                </div>
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
                        <input
                            id='wp-scheduled-posts-pro-license-key'
                            placeholder='Place Your License Key and Activate'
                        />
                    </div>
                    <div className='wpsp-license-buttons'>
                        <button
                            id='submit'
                            type='button'
                            className='wpsp-license-buttons'
                        >
                            Activate License
                        </button>
                    </div>
                </div>
            </div>
        </React.Fragment>
    )
}

export default License
