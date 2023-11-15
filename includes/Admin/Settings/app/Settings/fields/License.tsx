import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import React, { useEffect, useState } from 'react';
import { SweetAlertToaster } from '../ToasterMsg';
import { activateLicense, deActivateLicense, getLicense, sendOpt } from '../helper/helper';
function License(props) {
    const [inputChanged, setInputChanged] = useState(false)
    const [tempKey, setTempKey] = useState(
        localStorage.getItem('wpsp_temp_key')
    )
    useEffect(() => {
        if (!localStorage.getItem('wpsp_is_valid')) {
            getLicense( {} ).then( (response) => {
                // @ts-ignore
                localStorage.setItem('wpsp_is_valid', response?.status)
                // @ts-ignore
                localStorage.setItem('wpsp_temp_key', response?.key)
                // @ts-ignore
                setValid(response?.status)
                // @ts-ignore
                setTempKey(response?.key)
            } )
        }
    }, [])

    const [valid, setValid] = useState(localStorage.getItem('wpsp_is_valid'))
    const [isRequestSend, setIsRequestSend] = useState(null)
    const [isRequiredOtp,setIsRequiredOtp] = useState('');
    const [opt,setOtp] = useState(null);
    const [validLicense,setValidLicense] = useState('');

    const handleLicenseActivation = () => {
        setIsRequestSend(true)
        let data = {
            license_key: tempKey,
        }
        setIsRequestSend(null)
        setInputChanged(false)
        activateLicense( data ).then( ( response ) => {
            setIsRequestSend(null)
            setInputChanged(false)
            // @ts-ignore
            if (response.success === true) {
                setValidLicense(tempKey);
                // @ts-ignore
                localStorage.setItem('wpsp_is_valid', response?.status)
                // @ts-ignore
                localStorage.setItem('wpsp_temp_key', response?.key)
                // @ts-ignore
                // setTempKey(response.key)
                setTempKey(response?.license_key)
                // @ts-ignore 
                setIsRequiredOtp(response?.license)
                // @ts-ignore
                setValid(response.status)
                SweetAlertToaster();
                SweetAlertToaster({
                    type : 'success',
                    title : __( 'Your License is Successfully Activated.', 'wp-scheduled-posts' ),
                }).fire();
            } else {
                // @ts-ignore
                let response_data = response.data;
                SweetAlertToaster({
                    type : 'error',
                    title : __( response_data, 'wp-scheduled-posts' ),
                }).fire();
            }
        } ).catch( (error) => {
            SweetAlertToaster({
                type : 'error',
                title : __( error, 'wp-scheduled-posts' ),
            }).fire();
        } );
    }

    const handleLicenseDeactivation = () => {
        setIsRequestSend(true)
        deActivateLicense().then( ( response ) => {
            setIsRequestSend(null)
            setInputChanged(false)
            // @ts-ignore
            if (response.success === true) {
                localStorage.removeItem('wpsp_is_valid')
                localStorage.removeItem('wpsp_temp_key')
                // @ts-ignore
                setValid(response.data.status)
                setTempKey('')
                SweetAlertToaster({
                    type : 'success',
                    title : __( 'Your License is Successfully Deactivated.', 'wp-scheduled-posts' ),
                }).fire();
            } else {
                // @ts-ignore
                let response_data = response.data;
                SweetAlertToaster({
                    type : 'error',
                    title : __( response_data, 'wp-scheduled-posts' ),
                }).fire();
            }
        } ).catch( (error) => {
            SweetAlertToaster({
                type : 'error',
                title : __( error, 'wp-scheduled-posts' ),
            }).fire();
        } );
    }

    // Handle opt verification 
    const handleOtpVerification = () => {
        let data = {
            otp: opt,
            license_key: validLicense,
        }
        console.log(data);
        
        sendOpt( data ).then( ( response ) => {
            console.log(response);
        } ).catch( (error) => {
            console.log(error);
        } );
    }

  return (
    <div className={classNames('wprf-control', 'wprf-license', `wprf-${props.name}-social-profile`, props?.classes)}>
        <div className='wpsp-license-container-2'>
            <h4>{props?.label}</h4>
            <div className='wpsp-license-key-wrapper'>
                <div className='wpsp-license-input'>
                    {tempKey && valid == 'valid' ? (
                        <input
                            id='wp-scheduled-posts-pro-license-key'
                            className='activated'
                            placeholder='Place Your License Key and Activate'
                            onChange={(e) => setTempKey(e.target.value)}
                            value={tempKey}
                            disabled={true}
                        />
                    ) : (
                        <input
                            id='wp-scheduled-posts-pro-license-key'
                            placeholder='Place Your License Key and Activate'
                            onChange={(e) => setTempKey(e.target.value)}
                            value={ tempKey ? tempKey : ''}
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
                            onClick={() => handleLicenseDeactivation()}
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
                            onClick={() => handleLicenseActivation()}
                            disabled={!tempKey}
                        >
                            {isRequestSend == true
                                ? 'Request Sending...'
                                : 'Activate License'}
                        </button>
                    )}
                </div>
            </div>
        </div>
        { isRequiredOtp === 'required_otp' && 
            <div className="wpsp-license-container-2">
                <input type="number" onChange={ (e) => setOtp(e.target.value) } />
                    <button
                        id='submit'
                        type='button'
                        className={
                            inputChanged
                                ? 'wpsp-license-buttons changed'
                                : 'wpsp-license-buttons'
                        }
                        onClick={() => handleOtpVerification()}
                    >
                        Verify
                    </button>
            </div>
        }
    </div>
  )
}

export default License