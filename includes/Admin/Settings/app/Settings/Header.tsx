import { __ } from '@wordpress/i18n';
import React from 'react';
import { Badge, ButtonLink } from './components/ui';

const Header = ({ image_path }) => {
    // @ts-ignore
    const free_version = wpspSettingsGlobal?.free_version;
    // @ts-ignore
    const pro_version = wpspSettingsGlobal?.pro_version;

    return (
        <div className="wpsp-admin-header tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-4 tw-bg-white tw-px-6 tw-py-4 lg:tw-px-10 lg:tw-py-5">
            <div className="tw-flex tw-flex-wrap tw-items-center tw-gap-4">
                <img
                    src={`${image_path}mainLogo.png`}
                    alt={__('SchedulePress', 'wp-scheduled-posts')}
                    className="tw-h-9 tw-w-auto"
                />

                <div className="wpsp-admin-version tw-flex tw-flex-wrap tw-items-center tw-gap-2">
                    {free_version && (
                        <Badge tone="neutral" size="sm">
                            <span className="tw-text-ink-subtle">
                                {__('Core', 'wp-scheduled-posts')}
                            </span>
                            <span className="tw-font-semibold tw-text-ink">
                                {free_version}
                            </span>
                        </Badge>
                    )}

                    {pro_version && (
                        <Badge tone="brand" size="sm">
                            <span className="tw-opacity-70">
                                {__('Pro', 'wp-scheduled-posts')}
                            </span>
                            <span className="tw-font-semibold">{pro_version}</span>
                        </Badge>
                    )}
                </div>
            </div>

            <div className="tw-flex tw-items-center tw-gap-2">
                <ButtonLink
                    variant="ghost"
                    size="sm"
                    target="_blank"
                    rel="noopener noreferrer"
                    href="https://wpdeveloper.com/docs-category/wp-scheduled-posts/"
                >
                    {__('Documentation', 'wp-scheduled-posts')}
                </ButtonLink>

                <ButtonLink
                    variant="ghost"
                    size="sm"
                    target="_blank"
                    rel="noopener noreferrer"
                    href="https://wpdeveloper.com/support/"
                >
                    {__('Support', 'wp-scheduled-posts')}
                </ButtonLink>

                {!pro_version && (
                    <ButtonLink
                        variant="warning"
                        size="sm"
                        target="_blank"
                        rel="noopener noreferrer"
                        href="https://schedulepress.com/#pricing"
                    >
                        {__('Upgrade To Pro', 'wp-scheduled-posts')}
                    </ButtonLink>
                )}
            </div>
        </div>
    );
};

export default Header;
