import { __ } from '@wordpress/i18n';
import React from 'react';
import { Badge, ButtonLink, Spinner } from '../components/ui';
import { IconBook, IconCheck, IconLifebuoy } from './icons';

export type SaveState = 'idle' | 'saving' | 'saved';

interface TopBarProps {
    imagePath: string;
    freeVersion?: string;
    proVersion?: string;
    saveState: SaveState;
}

const SaveIndicator: React.FC<{ state: SaveState }> = ({ state }) => {
    if (state === 'idle') {
        return null;
    }

    return (
        <span className="tw-inline-flex tw-items-center tw-gap-1.5 tw-text-xs tw-text-ink-muted tw-animate-fade-in">
            {state === 'saving' ? (
                <>
                    <Spinner size="xs" />
                    {__('Saving…', 'wp-scheduled-posts')}
                </>
            ) : (
                <>
                    <span className="tw-text-success-500">
                        <IconCheck />
                    </span>
                    {__('All changes saved', 'wp-scheduled-posts')}
                </>
            )}
        </span>
    );
};

const TopBar: React.FC<TopBarProps> = ({
    imagePath,
    freeVersion,
    proVersion,
    saveState,
}) => (
    <header className="tw-sticky tw-top-8 tw-z-30 tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-4 tw-border-0 tw-border-b tw-border-solid tw-border-line tw-bg-white/90 tw-px-5 tw-py-3 tw-backdrop-blur lg:tw-px-8">
        <div className="tw-flex tw-flex-wrap tw-items-center tw-gap-3">
            {/* The vector lockup: mainLogo.png is a 209x40 1x asset, so it
                softened on any HiDPI screen. Slightly taller than the PNG was
                because this file carries more padding in its viewBox. */}
            <img
                src={`${imagePath}wpsp-logo-full.svg`}
                alt={__('SchedulePress', 'wp-scheduled-posts')}
                className="tw-h-9 tw-w-auto"
            />

            <span className="tw-h-5 tw-w-px tw-bg-line-strong" />

            <div className="tw-flex tw-flex-wrap tw-items-center tw-gap-1.5">
                {freeVersion && (
                    <Badge tone="neutral" size="sm">
                        {__('Core', 'wp-scheduled-posts')}
                        <span className="tw-font-semibold tw-text-ink">
                            {freeVersion}
                        </span>
                    </Badge>
                )}

                {proVersion && (
                    <Badge tone="brand" size="sm">
                        {__('Pro', 'wp-scheduled-posts')}
                        <span className="tw-font-semibold">{proVersion}</span>
                    </Badge>
                )}
            </div>
        </div>

        <div className="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
            <SaveIndicator state={saveState} />

            <ButtonLink
                variant="ghost"
                size="sm"
                target="_blank"
                rel="noopener noreferrer"
                href="https://wpdeveloper.com/docs-category/wp-scheduled-posts/"
                leftIcon={<IconBook />}
            >
                {__('Docs', 'wp-scheduled-posts')}
            </ButtonLink>

            <ButtonLink
                variant="ghost"
                size="sm"
                target="_blank"
                rel="noopener noreferrer"
                href="https://wpdeveloper.com/support/"
                leftIcon={<IconLifebuoy />}
            >
                {__('Support', 'wp-scheduled-posts')}
            </ButtonLink>

            {!proVersion && (
                <ButtonLink
                    variant="primary"
                    size="sm"
                    target="_blank"
                    rel="noopener noreferrer"
                    href="https://schedulepress.com/#pricing"
                >
                    {__('Upgrade to Pro', 'wp-scheduled-posts')}
                </ButtonLink>
            )}
        </div>
    </header>
);

export default TopBar;
