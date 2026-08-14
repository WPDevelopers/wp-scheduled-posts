import { __ } from '@wordpress/i18n';
import React from 'react'
import { Button, Toggle } from '../../components/ui';

/**
 * Google Business renders its own profile header rather than sharing
 * `utils/MainProfile`, because it has no account-type select. The layout is
 * kept in step with MainProfile so both cards read the same.
 */
const GoogleBusinessProfile = ({props,
    handleProfileStatusChange,
    profileStatus,
    openApiCredentialsModal}) => {
    return (
        <div>
            <div className="card-header tw-flex tw-items-center tw-justify-between tw-gap-3 tw-mb-5">
                <div className="heading tw-flex tw-items-center tw-gap-3">
                    <img
                        src={`${props?.logo}`}
                        alt={`${props?.label}`}
                        className="tw-w-8 tw-h-8 tw-object-contain"
                    />
                    <h5 className="tw-text-xl tw-font-medium tw-text-ink tw-m-0">
                        {props?.label}
                    </h5>
                </div>

                <Toggle
                    id={props?.id}
                    tone="success"
                    checked={!!profileStatus}
                    /* The handler only reads `target.checked`. */
                    onChange={(checked) =>
                        handleProfileStatusChange({ target: { checked } })
                    }
                />
            </div>

            <div
                className="card-content tw-text-base tw-text-ink-muted tw-mb-5 [&_p]:tw-m-0 [&_a]:tw-text-ink"
                dangerouslySetInnerHTML={{ __html: props?.desc }}
            />

            <div className="card-footer tw-flex">
                <Button
                    type="button"
                    variant="outline"
                    size="lg"
                    className="tw-ml-auto tw-shrink-0"
                    onClick={() => {
                        openApiCredentialsModal()
                    }}>
                    {__('Add New', 'wp-scheduled-posts')}
                </Button>
            </div>
        </div>
    )
}

export default GoogleBusinessProfile
