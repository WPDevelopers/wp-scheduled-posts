import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import { Badge } from '../../components/ui';
import SelectedProfile from './SelectedProfile';
import ViewMore from './ViewMore';

/**
 * The right-hand column of a social platform card: the accounts connected to
 * that platform.
 *
 * Every one of the ten platform components rendered this same block inline,
 * down to the copy-pasted `selected-facebook-scrollbar` class on the Mastodon
 * and Bluesky cards. It is one component now, so the column gained a heading, a
 * count of what is actually switched on, and a real empty state in place of a
 * bare illustration — in every platform at once.
 */

/** How many accounts are collapsed to before "View More" is offered. */
const COLLAPSED_COUNT = 2;

/**
 * Stand-in accounts for the empty state. They are fed through the real
 * `SelectedProfile`, so the preview behind the message is the row the user will
 * actually get — same avatar, pill, name, toggle — rather than an illustration
 * of one. Blurred and inert, and one of the two is switched off so both states
 * are visible.
 *
 * The fields cover every platform's read of an account: `type`/`account_type`
 * decide the pill's wording, and Pinterest names its rows by board.
 */
const PLACEHOLDER_PROFILES = [
    {
        id: '__wpsp-placeholder-1',
        name: 'Marketing Team',
        added_by: 'admin',
        added_date: '2024-06-12',
        status: true,
        type: 'page',
        account_type: 'Page',
        default_board_name: { label: 'Design Inspiration', value: '__wpsp-placeholder-1' },
    },
    {
        id: '__wpsp-placeholder-2',
        name: 'Company News',
        added_by: 'editor',
        added_date: '2024-06-12',
        status: false,
        type: 'profile',
        account_type: 'Profile',
        default_board_name: { label: 'Product Launches', value: '__wpsp-placeholder-2' },
    },
];

const noop = () => {};

export interface ConnectedProfilesProps {
    /** Platform key, as `SelectedProfile` expects it (`google_business`, …). */
    platform: string;
    /** Platform name, for the empty state's copy. */
    label?: string;
    profiles: any[];
    /** Whether the list is expanded past `COLLAPSED_COUNT`. */
    viewMore: boolean;
    onViewMore: (expanded: boolean) => void;
    /** The platform's master switch — an account is only live if it is on. */
    profileStatus?: boolean;
    onStatusChange: (item: any, event: any) => void;
    onDelete: (item: any) => void;
    /** Pinterest is the only platform whose rows can be edited. */
    onEdit?: (item: any) => void;
}

/**
 * Rows are keyed by what identifies them: a Pinterest row is a board, so
 * several rows can share one account id.
 */
function rowKey(item: any, index: number) {
    return item?.default_board_name?.value || item?.id || index;
}

const ConnectedProfiles: React.FC<ConnectedProfilesProps> = ({
    platform,
    label,
    profiles,
    viewMore,
    onViewMore,
    profileStatus = false,
    onStatusChange,
    onDelete,
    onEdit,
}) => {
    const list = profiles || [];
    const visible = viewMore ? list : list.slice(0, COLLAPSED_COUNT);
    const activeCount = profileStatus
        ? list.filter((item) => item?.status).length
        : 0;

    return (
        <div className="selected-profile">
            {list.length > 0 ? (
                <>
                    <div className="tw-mb-1 tw-flex tw-items-center tw-justify-between tw-gap-3">
                        <h6 className="tw-m-0 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-ink-subtle">
                            {__('Connected accounts', 'wp-scheduled-posts')}
                        </h6>

                        <Badge
                            tone={activeCount > 0 ? 'success' : 'neutral'}
                            size="sm"
                            dot
                        >
                            {sprintf(
                                /* translators: 1: number of accounts sharing, 2: number connected. */
                                __('%1$d of %2$d sharing', 'wp-scheduled-posts'),
                                activeCount,
                                list.length
                            )}
                        </Badge>
                    </div>

                    <div className="selected-profiles-scrollbar">
                        {visible.map((item, index) => (
                            <SelectedProfile
                                key={rowKey(item, index)}
                                platform={platform}
                                item={item}
                                handleSelectedProfileStatusChange={onStatusChange}
                                handleDeleteSelectedProfile={onDelete}
                                handleEditSelectedProfile={onEdit || ''}
                                profileStatus={profileStatus}
                            />
                        ))}
                    </div>

                    {!viewMore && list.length > COLLAPSED_COUNT && (
                        <ViewMore setSelectedProfileViewMore={onViewMore} />
                    )}
                </>
            ) : (
                /* Centres in whatever height the left-hand column sets. */
                <div className="tw-relative tw-m-auto tw-w-full">
                    <div
                        aria-hidden="true"
                        /* The stand-in rows carry real toggles and buttons.
                           `inert` takes them out of the tab order too; the
                           pointer-events fallback covers browsers without it. */
                        {...({ inert: '' } as any)}
                        className="tw-pointer-events-none tw-flex tw-select-none tw-flex-col tw-gap-2 tw-blur-[3px]"
                    >
                        {PLACEHOLDER_PROFILES.map((item) => (
                            <SelectedProfile
                                key={item.id}
                                platform={platform}
                                item={item}
                                handleSelectedProfileStatusChange={noop}
                                handleDeleteSelectedProfile={noop}
                                handleEditSelectedProfile={noop}
                                profileStatus={true}
                            />
                        ))}
                    </div>

                    {/* Light enough to read the shape of a row through, opaque
                        enough to keep the message itself legible. */}
                    <div className="tw-absolute tw-inset-0 tw-flex tw-flex-col tw-items-center tw-justify-center tw-rounded tw-bg-[rgba(241,243,248,0.72)] tw-px-4 tw-text-center">
                        <span className="tw-mb-3 tw-inline-flex tw-h-10 tw-w-10 tw-items-center tw-justify-center tw-rounded-full tw-bg-white tw-text-brand-500 tw-shadow-card">
                            <svg
                                className="tw-h-5 tw-w-5"
                                viewBox="0 0 20 20"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 4.5v11M4.5 10h11"
                                    stroke="currentColor"
                                    strokeWidth="1.8"
                                    strokeLinecap="round"
                                />
                            </svg>
                        </span>

                        <h3 className="tw-m-0 tw-text-lg tw-font-medium tw-text-ink">
                            {__('No accounts connected', 'wp-scheduled-posts')}
                        </h3>

                        <p className="tw-m-0 tw-mt-1 tw-max-w-[260px] tw-text-sm tw-text-ink-muted">
                            {sprintf(
                                /* translators: %s: platform name, e.g. "Facebook". */
                                __(
                                    'Add a %s account to start sharing your scheduled posts to it.',
                                    'wp-scheduled-posts'
                                ),
                                label || __('social', 'wp-scheduled-posts')
                            )}
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ConnectedProfiles;
