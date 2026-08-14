import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import React, { useEffect, useState } from 'react';
import { SweetAlertToaster } from '../ToasterMsg';
import { activateLicense, deActivateLicense, getLicense } from '../helper/helper';
import { Badge, Button, Card, Input, SectionHeader } from '../components/ui';
function License(props) {
    const [inputChanged, setInputChanged] = useState(false)
    const [tempKey, setTempKey] = useState(
        localStorage.getItem('wpsp_temp_key')
    )
    useEffect(() => {
        if (!localStorage.getItem('wpsp_is_valid')) {
            getLicense( {} ).then( (response) => {
                // @ts-ignore
                localStorage.setItem('wpsp_is_valid', response?.data.status)
                // @ts-ignore
                localStorage.setItem('wpsp_temp_key', response?.data.key)
                // @ts-ignore
                setValid(response?.data.status)
                // @ts-ignore
                setTempKey(response?.data.key)
            } )
        }
    }, [])

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

    const isActivated = valid == 'valid';

    return (
        <div
            className={classNames(
                'wprf-control',
                'wprf-license',
                `wprf-${props.name}-social-profile`,
                props?.classes
            )}
        >
            <Card padding="lg">
                <SectionHeader
                    title={props?.label}
                    description={__(
                        'Activate your license to unlock automatic updates and premium support.',
                        'wp-scheduled-posts'
                    )}
                    actions={
                        <Badge tone={isActivated ? 'success' : 'neutral'} dot>
                            {isActivated
                                ? __('Active', 'wp-scheduled-posts')
                                : __('Not activated', 'wp-scheduled-posts')}
                        </Badge>
                    }
                    className="tw-mb-6"
                />

                <div className="tw-flex tw-flex-wrap tw-items-start tw-gap-3">
                    <Input
                        id="wp-scheduled-posts-pro-license-key"
                        inputSize="lg"
                        wrapperClassName="tw-flex-1 tw-min-w-[260px]"
                        placeholder={__(
                            'Place Your License Key and Activate',
                            'wp-scheduled-posts'
                        )}
                        /* An activated key is read-only until it is deactivated. */
                        disabled={isActivated}
                        value={tempKey ? tempKey : ''}
                        onChange={(e) => setTempKey(e.target.value)}
                    />

                    {isActivated ? (
                        <Button
                            id="submit"
                            variant="outline"
                            size="lg"
                            loading={isRequestSend == true}
                            onClick={() => handleLicenseDeactivation()}
                        >
                            {isRequestSend == true
                                ? __('Request Sending...', 'wp-scheduled-posts')
                                : __('Deactivate License', 'wp-scheduled-posts')}
                        </Button>
                    ) : (
                        <Button
                            id="submit"
                            size="lg"
                            disabled={!tempKey}
                            loading={isRequestSend == true}
                            onClick={() => handleLicenseActivation()}
                        >
                            {isRequestSend == true
                                ? __('Request Sending...', 'wp-scheduled-posts')
                                : __('Activate License', 'wp-scheduled-posts')}
                        </Button>
                    )}
                </div>

                <p className="tw-text-sm tw-text-ink-muted tw-m-0 tw-mt-4">
                    {__(
                        'You can find your license key in your WPDeveloper account under Downloads.',
                        'wp-scheduled-posts'
                    )}{' '}
                    <a
                        href="https://store.wpdeveloper.com/account/"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="tw-text-brand-500 hover:tw-text-brand-700"
                    >
                        {__('Open my account', 'wp-scheduled-posts')}
                    </a>
                </p>
            </Card>
        </div>
    )
}

export default License
