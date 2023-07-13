import React, { useState } from 'react'
import classNames from 'classnames';
import { __ } from '@wordpress/i18n';
import { activateLicense } from '../helper/helper';
import { SweetAlertToaster } from '../ToasterMsg';
function License(props) {
    const [inputChanged, setInputChanged] = useState(false)
    const [errorMessage, setErrorMessage] = useState('')
    const [tempKey, setTempKey] = useState(
        localStorage.getItem('wpsp_temp_key')
    )
    const [valid, setValid] = useState(localStorage.getItem('wpsp_is_valid'))
    const [isRequestSend, setIsRequestSend] = useState(null)
    const handleLicenseActivation = () => {
        setIsRequestSend(true)
        let data = {
            key: tempKey,
        }
        setIsRequestSend(null)
        setInputChanged(false)
        activateLicense( data ).then( ( response ) => {
            setIsRequestSend(null)
            setInputChanged(false)
            // @ts-ignore 
            if (response.success === true) {
                // @ts-ignore 
                localStorage.setItem('wpsp_is_valid', response?.data.status)
                // @ts-ignore 
                localStorage.setItem('wpsp_temp_key', response?.data.key)
                // @ts-ignore 
                setTempKey(response.data.key)
                // @ts-ignore 
                setValid(response.data.status)
                SweetAlertToaster();
                SweetAlertToaster({
                    icon : 'success',
                    title : __( 'Your License is Successfully Activated.', 'wp-scheduled-posts' ),
                }).fire();
            } else {
                // @ts-ignore 
                let response_data = response.data;
                setErrorMessage(response_data)
                SweetAlertToaster({
                    icon : 'error',
                    title : __( response_data, 'wp-scheduled-posts' ),
                }).fire();
            }
        } ).catch( (error) => {
            setErrorMessage(error)
            SweetAlertToaster();
        } );
    }
  return (
    <div className={classNames('wprf-control', 'wprf-license', `wprf-${props.name}-social-profile`, props?.classes)}>
        <input type="text" value={tempKey} onChange={ (e) => setTempKey(e.target.value) }  />
        <button onClick={handleLicenseActivation}>Activate</button>
    </div>
  )
}

export default License