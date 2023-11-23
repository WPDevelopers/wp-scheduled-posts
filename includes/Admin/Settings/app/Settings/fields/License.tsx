import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import React, { useEffect, useState } from 'react';
import { SweetAlertToaster } from '../ToasterMsg';
import { activateLicense, deActivateLicense, getLicense, resendOtp, sendOpt } from '../helper/helper';
import Verification from './utils/Verification';
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
    const [isSendingVerificationRequest, setIsSendingVerificationRequest] = useState(false);
    const [isSendingResendRequest, setIsSendingResendRequest] = useState(false);
    const [isRequiredOtp,setIsRequiredOtp] = useState('');
    const [validLicense,setValidLicense] = useState('');
    const [email,setEmail] = useState('');
    const [isSedingActivationRequest,setIsSedingActivationRequest] = useState(false);

    const handleLicenseActivation = () => {
        setIsRequestSend(true)
        setIsSedingActivationRequest(true);
        let data = {
            license_key: tempKey,
        }
        setIsRequestSend(null)
        setInputChanged(false)
        activateLicense( data ).then( ( response ) => {
            setIsSedingActivationRequest(false);
            setIsRequestSend(null)
            setInputChanged(false)
            // @ts-ignore
            if (response.success === true) {
                // @ts-ignore 
                setIsRequiredOtp(response?.license)
                // @ts-ignore 
                setEmail( response?.customer_email );
                // @ts-ignore
                setValidLicense(tempKey);
                SweetAlertToaster({
                    type : 'info',
                    title : __( 'Please validate OTP.', 'wp-scheduled-posts' ),
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
            setIsSedingActivationRequest(false);
            SweetAlertToaster({
                type : 'error',
                title : __( error.message, 'wp-scheduled-posts' ),
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
                setValid(response?.license)
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
                    title : __( `${response_data}`, 'wp-scheduled-posts' ),
                }).fire();
            }
        } ).catch( (error) => {
            setIsRequestSend(null)
            SweetAlertToaster({
                type : 'error',
                title : __( error?.message, 'wp-scheduled-posts' ),
            }).fire();
        } );
    }

    // Handle opt verification 
    const handleOtpVerification = (getOtp) => {
        let data = {
            otp: getOtp,
            license: validLicense,
            license_key: validLicense,
        }
        setIsSendingVerificationRequest(true);
        sendOpt( data ).then( ( response ) => {
            setIsSendingVerificationRequest(false);
             // @ts-ignore
             localStorage.setItem('wpsp_is_valid', response?.license)
             // @ts-ignore
             localStorage.setItem('wpsp_temp_key', response?.license_key)
             // @ts-ignore
             // setTempKey(response.key)
             setTempKey(response?.license_key)
             // @ts-ignore 
             setIsRequiredOtp(response?.license)
             // @ts-ignore
             setValid(response?.license)
             SweetAlertToaster({
                 type : 'success',
                 title : __( 'Your License is Successfully Activated.', 'wp-scheduled-posts' ),
             }).fire();
        } ).catch( (error) => {
            setIsSendingVerificationRequest(false);
            SweetAlertToaster({
                type : 'error',
                title : __( error.message, 'wp-scheduled-posts' ),
            }).fire();
        } );
    }

    const handleResendOtp = () => {
        let data = {
            license_key: validLicense,
            license: validLicense,
        }
        setIsSendingResendRequest(true);
        resendOtp( data ).then( ( response ) => {
            setIsSendingResendRequest(false);
             // @ts-ignore 
             setIsRequiredOtp(response?.license)
             // @ts-ignore
             setValidLicense(tempKey);
             SweetAlertToaster({
                 type : 'success',
                 title : __( 'Your OTP has been sended again Successfully!!.', 'wp-scheduled-posts' ),
             }).fire();
        } ).catch( (error) => {
            setIsSendingResendRequest(true);
            SweetAlertToaster({
                type : 'error',
                title : __( error.message, 'wp-scheduled-posts' ),
            }).fire();
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
                        {isSedingActivationRequest == true
                            ? 'Request Sending...'
                            : 'Activate License'}
                        </button>
                    )}
                </div>
            </div>
        </div>
        { isRequiredOtp === 'required_otp' && 
            <Verification email={email} submitOTP={ handleOtpVerification } resendOTP={ handleResendOtp } isRequestSending={ isSendingVerificationRequest } isSendingResendRequest={isSendingResendRequest}  />
        }
    </div>
  )
}

export default License