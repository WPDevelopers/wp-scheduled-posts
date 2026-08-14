import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import { Badge, EmptyState } from '../../components/ui';
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
                <EmptyState
                    size="sm"
                    /* @ts-ignore — localised by Assets.php */
                    image={`${wpspSettingsGlobal?.image_path}EmptyCard.svg`}
                    title={__('No accounts connected', 'wp-scheduled-posts')}
                    description={sprintf(
                        /* translators: %s: platform name, e.g. "Facebook". */
                        __(
                            'Add a %s account to start sharing your scheduled posts to it.',
                            'wp-scheduled-posts'
                        ),
                        label || __('social', 'wp-scheduled-posts')
                    )}
                    /* Centres in whatever height the left-hand column sets. */
                    className="tw-m-auto"
                />
            )}
        </div>
    );
};

export default ConnectedProfiles;
