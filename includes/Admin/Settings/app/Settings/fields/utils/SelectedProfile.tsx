import { __ } from '@wordpress/i18n';
import React from 'react';
import { getFormatDateTime, handleImageError } from '../../helper/helper';
import { Avatar, Badge, Toggle } from '../../components/ui';

/**
 * Label shown on the pill next to a connected account. Each platform names its
 * account kinds differently, and only some expose one at all.
 */
function accountTypeLabel(platform, item): string {
    switch (platform) {
        case 'linkedin':
            return item?.type == 'organization'
                ? __('Page', 'wp-scheduled-posts')
                : __('Profile', 'wp-scheduled-posts');
        case 'pinterest':
            return item?.account_type
                ? __('Board', 'wp-scheduled-posts')
                : item?.type;
        case 'instagram':
            return item?.account_type
                ? __('Profile', 'wp-scheduled-posts')
                : item?.type;
        case 'facebook':
        case 'twitter':
            return item?.type ? item.type : __('Profile', 'wp-scheduled-posts');
        default:
            return __('Profile', 'wp-scheduled-posts');
    }
}

/**
 * Modifier class the platform palettes in `_socialProfile.scss` hang off —
 * LinkedIn pages, Facebook groups and Pinterest boards each get their own
 * brand colour rather than the default purple.
 */
function accountTypeModifier(platform, item): string {
    switch (platform) {
        case 'pinterest':
        case 'instagram':
            return `${platform}-${item?.account_type?.toLowerCase()}`;
        case 'facebook':
        case 'twitter':
        case 'linkedin':
            return `${platform}-${item?.type}`;
        default:
            return `${platform}-profile`;
    }
}

const actionButtonClass =
    'wpsp-ui tw-inline-flex tw-items-center tw-gap-1 tw-bg-transparent tw-border-0 tw-p-0 ' +
    'tw-text-sm tw-text-ink-muted tw-cursor-pointer hover:tw-text-brand-600';

export default function SelectedProfile({ platform, item, handleSelectedProfileStatusChange, handleDeleteSelectedProfile, handleEditSelectedProfile, profileStatus = false }) {
    const isPinterest = platform == 'pinterest';
    // Pinterest rows are keyed by board rather than by account.
    const toggleId = isPinterest ? item?.default_board_name?.value : item?.id;
    const isOn = !!(profileStatus && item?.status);

    return (
        <div className="profile-item tw-relative tw-flex tw-items-start tw-gap-4 tw-rounded tw-bg-white tw-p-5">
            <div className="profile-image">
                <Avatar
                    size="lg"
                    src={item?.thumbnail_url}
                    name={item?.name}
                    alt={item?.name}
                    onImageError={handleImageError}
                />
            </div>

            <div className="profile-data tw-min-w-0 tw-flex-1">
                {/* Pinned top-right of the row, as in the previous design. */}
                <Badge
                    tone="brand"
                    size="sm"
                    className={`badge ${accountTypeModifier(
                        platform,
                        item
                    )} tw-absolute tw-right-3 tw-top-3 tw-uppercase`}
                >
                    {accountTypeLabel(platform, item)}
                </Badge>

                <h4 className="tw-text-base tw-font-medium tw-text-ink tw-m-0 tw-truncate tw-pr-16">
                    {isPinterest ? item?.default_board_name?.label : item?.name}
                </h4>

                <span className="tw-block tw-text-xs tw-text-ink-subtle tw-mt-0.5">
                    {item?.added_by?.replace(/^\w/, (c) => c.toUpperCase())}{' '}
                    {__('on', 'wp-scheduled-posts')}{' '}
                    {getFormatDateTime(item?.added_date)}
                </span>

                <div className="action tw-flex tw-items-center tw-gap-3 tw-mt-2">
                    <Toggle
                        id={toggleId}
                        size="sm"
                        tone="success"
                        checked={isOn}
                        /* The handler only reads `target.checked`. */
                        onChange={(checked) =>
                            handleSelectedProfileStatusChange(item, {
                                target: { checked },
                            })
                        }
                    />

                    {isPinterest && (
                        <button
                            type="button"
                            onClick={() => handleEditSelectedProfile(item)}
                            className={actionButtonClass}
                        >
                            <i className="wpsp-icon wpsp-edit tw-text-[0.9em]" />
                            {__('Edit', 'wp-scheduled-posts')}
                        </button>
                    )}

                    <button
                        type="button"
                        onClick={() => handleDeleteSelectedProfile(item)}
                        className={`${actionButtonClass} hover:tw-text-danger-500`}
                    >
                        <i className="wpsp-icon wpsp-delete tw-text-[0.9em]" />
                        {__('Delete', 'wp-scheduled-posts')}
                    </button>
                </div>
            </div>
        </div>
    )
}
