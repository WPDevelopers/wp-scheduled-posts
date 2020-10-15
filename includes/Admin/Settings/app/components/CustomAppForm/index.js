import React, { useState } from 'react'
const CustomAppForm = ({ requestHandler }) => {
    const [redirectURI, SetRedirectURI] = useState(
        'https://api.schedulepress.com/callback.php'
    )
    const [appID, SetAppID] = useState('')
    const [appSecret, SetAppSecret] = useState('')
    return (
        <React.Fragment>
            <div className='modalbody'>
                <div className='wpsp-social-account-insert-modal'>
                    <div className='wpsp-social-modal-header'>
                        <h3>Twitter</h3>
                        <p>
                            For details on Twitter configuration, check out this{' '}
                            <a
                                className='docs'
                                href='https://wpdeveloper.net/docs/automatically-tweet-wordpress-posts/'
                                target='_blank'
                            >
                                Doc
                            </a>{' '}
                            <br />
                            <a
                                href='https://developer.twitter.com/'
                                target='_blank'
                            >
                                <strong>Click here</strong>
                            </a>{' '}
                            here to Retrieve Your API Keys from your Twitter
                            account
                        </p>
                    </div>
                    <input type='hidden' name='tempmodaltype' value='twitter' />
                    <table className='form-table'>
                        <tbody>
                            <tr>
                                <td colspan='2' align='left'>
                                    <div className='form-group redirect-group'>
                                        <div className='form-label'>
                                            <label>Redirect URI: </label>
                                        </div>
                                        <div className='form-input'>
                                            <input
                                                type='text'
                                                value={redirectURI}
                                                placeholder='Redirect URI'
                                                onChange={(e) =>
                                                    SetRedirectURI(
                                                        e.target.value
                                                    )
                                                }
                                            />
                                            <div className='doc'>
                                                Copy this and paste it in your
                                                Twitter app Callback uri field.
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan='2' align='left'>
                                    <div className='form-group'>
                                        <div className='form-label'>
                                            <label>App ID: </label>
                                        </div>
                                        <div className='form-input'>
                                            <input
                                                type='text'
                                                value={appID}
                                                placeholder='App ID'
                                                onChange={(e) =>
                                                    SetAppID(e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan='2' align='left'>
                                    <div className='form-group'>
                                        <div className='form-label'>
                                            <label>App Secret: </label>
                                        </div>
                                        <div className='form-input'>
                                            <input
                                                type='text'
                                                value={appSecret}
                                                placeholder='App Secret'
                                                onChange={(e) =>
                                                    SetAppSecret(e.target.value)
                                                }
                                            />
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan='2' align='left'>
                                    <div className='form-group'>
                                        <a
                                            onClick={() =>
                                                requestHandler(
                                                    redirectURI,
                                                    appID,
                                                    appSecret
                                                )
                                            }
                                            className='submit'
                                        >
                                            Generate Access Token
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
