import { __ } from '@wordpress/i18n';
import React from 'react';
import cn from '../components/ui/cn';
import { ButtonLink } from '../components/ui';
import { tabIcon } from './icons';

export interface NavTab {
    id: string;
    label: string;
    is_pro?: boolean;
}

interface NavRailProps {
    tabs: NavTab[];
    activeId: string;
    onSelect: (id: string) => void;
    isPro: boolean;
    imagePath: string;
}

const NavRail: React.FC<NavRailProps> = ({
    tabs,
    activeId,
    onSelect,
    isPro,
    imagePath,
}) => (
    <nav className="tw-flex tw-shrink-0 tw-flex-col tw-gap-6 tw-p-4 lg:tw-w-[236px] lg:tw-p-5">
        <ul className="tw-m-0 tw-flex tw-list-none tw-flex-row tw-gap-1 tw-overflow-x-auto tw-p-0 lg:tw-flex-col lg:tw-overflow-visible">
            {tabs.map((tab) => {
                const Icon = tabIcon(tab.id);
                const isActive = tab.id === activeId;

                return (
                    <li key={tab.id} className="tw-m-0">
                        <button
                            type="button"
                            onClick={() => onSelect(tab.id)}
                            aria-current={isActive ? 'page' : undefined}
                            className={cn(
                                'wpsp-ui tw-flex tw-w-full tw-items-center tw-gap-2.5 tw-whitespace-nowrap',
                                'tw-rounded-md tw-border-0 tw-px-3 tw-py-2.5 tw-text-left tw-text-base',
                                'tw-cursor-pointer tw-transition-colors tw-duration-150',
                                isActive
                                    ? 'tw-bg-brand-500 tw-font-medium tw-text-white'
                                    : 'tw-bg-transparent tw-text-ink-muted hover:tw-bg-canvas-sunken hover:tw-text-ink'
                            )}
                        >
                            <span
                                className={cn(
                                    'tw-shrink-0',
                                    isActive ? 'tw-text-white' : 'tw-text-ink-subtle'
                                )}
                            >
                                <Icon />
                            </span>
                            {tab.label}
                        </button>
                    </li>
                );
            })}
        </ul>

        {/* Promo sits under the nav so it never competes with it for attention. */}
        <div className="tw-hidden lg:tw-block">
            <div
                className={cn(
                    'tw-rounded-lg tw-p-4 tw-text-center',
                    isPro ? 'tw-bg-brand-50' : 'tw-bg-warning-50'
                )}
            >
                <img
                    src={`${imagePath}upgrade-pro-new.png`}
                    alt=""
                    className="tw-mx-auto tw-mb-3 tw-w-24"
                />

                <p className="tw-m-0 tw-mb-1 tw-text-base tw-font-medium tw-text-ink">
                    {isPro
                        ? __('Manage License', 'wp-scheduled-posts')
                        : __('Unlock every feature', 'wp-scheduled-posts')}
                </p>

                <p className="tw-m-0 tw-mb-3 tw-text-xs tw-text-ink-muted">
                    {isPro
                        ? __(
                              'Review your license and billing at any time.',
                              'wp-scheduled-posts'
                          )
                        : __(
                              'Auto scheduling, missed schedule handling and more.',
                              'wp-scheduled-posts'
                          )}
                </p>

                <ButtonLink
                    variant={isPro ? 'primary' : 'warning'}
                    size="sm"
                    fullWidth
                    target="_blank"
                    rel="noopener noreferrer"
                    href={
                        isPro
                            ? 'https://store.wpdeveloper.com/'
                            : 'https://schedulepress.com/#pricing'
                    }
                >
                    {isPro
                        ? __('Manage License', 'wp-scheduled-posts')
                        : __('Upgrade to Pro', 'wp-scheduled-posts')}
                </ButtonLink>
            </div>
        </div>
    </nav>
);

export default NavRail;
