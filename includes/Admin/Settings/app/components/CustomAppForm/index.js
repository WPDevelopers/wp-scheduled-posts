import React, { useState } from 'react'
import { __ } from '@wordpress/i18n'
import { socialPopUpData } from './../../utils/helper'
const CustomAppForm = ({ platform, requestHandler }) => {
    const [redirectURI, SetRedirectURI] = useState(
        'https://api.schedulepress.com/callback.php'
    )
    const [appID, SetAppID] = useState('')
    const [appSecret, SetAppSecret] = useState('')
    const { title, subtitle } = socialPopUpData[platform]
    return (
        <React.Fragment>
            <div className='modalbody'>
                <div className='wpsp-social-account-insert-modal'>
                    <div className='wpsp-social-modal-header'>
                        <h3>{title}</h3>
                        <p
                            dangerouslySetInnerHTML={{
                                __html: subtitle,
                            }}
                        ></p>
                    </div>
                    <input type='hidden' name='tempmodaltype' value='twitter' />
                    <table className='form-table'>
                        <tbody>
                            <tr>
                                <td align='left'>
                                    <div className='form-group redirect-group'>
                                        <div className='form-label'>
                                            <label>
                                                {__(
                                                    'Redirect URI:',
                                                    'wp-scheduled-posts'
                                                )}{' '}
                                            </label>
                                        </div>
                                        <div className='form-input'>
                                            <input
                                                type='text'
                                                value={redirectURI}
                                                placeholder={__(
                                                    'Redirect URI',
                                                    'wp-scheduled-posts'
                                                )}
                                                onChange={(e) =>
                                                    SetRedirectURI(
                                                        e.target.value
                                                    )
                                                }
                                            />
                                            <div className='doc'>
                                                {__(
                                                    'Copy this and paste it in your',
                                                    'wp-scheduled-posts'
                                                )}
                                                {platform}{' '}
                                                {__(
                                                    'app Callback url field.',
                                                    'wp-scheduled-posts'
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td align='left'>
                                    <div className='form-group'>
                                        <div className='form-label'>
                                            <label>
                                                {__(
                                                    'App ID:',
                                                    'wp-scheduled-posts'
                                                )}{' '}
                                            </label>
                                        </div>
                                        <div className='form-input'>
                                            <input
                                                type='text'
                                                value={appID}
                                                placeholder={__(
                                                    'App ID',
                                                    'wp-scheduled-posts'
                                                )}
                                                onChange={(e) =>
                                                    SetAppID(e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td align='left'>
                                    <div className='form-group'>
                                        <div className='form-label'>
                                            <label>
                                                {__(
                                                    'App Secret:',
                                                    'wp-scheduled-posts'
                                                )}{' '}
                                            </label>
                                        </div>
                                        <div className='form-input'>
                                            <input
                                                type='text'
                                                value={appSecret}
                                                placeholder={__(
                                                    'App Secret',
                                                    'wp-scheduled-posts'
                                                )}
                                                onChange={(e) =>
                                                    SetAppSecret(e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td align='left'>
                                    <div className='form-group'>
                                        <a
                                            onClick={() =>
                                                requestHandler(
                                                    redirectURI,
                                                    appID,
                                                    appSecret
                                                )
                                            }
                                            className='wpsp-modal-generate-token-button'
                                        >
                                            {__(
                                                'Generate Access Token',
                                                'wp-scheduled-posts'
                                            )}
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </React.Fragment>
    )
}
export default CustomAppForm
