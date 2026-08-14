import classNames from 'classnames';
import React from 'react';
import { __ } from '@wordpress/i18n';
import { ButtonLink, Card } from '../components/ui';

const FEATURES = [
    { icon: 'wpsp-advance-pro', title: __('Advanced Schedule', 'wp-scheduled-posts') },
    { icon: 'wpsp-manage-pro', title: __('Manage Schedule', 'wp-scheduled-posts') },
    { icon: 'wpsp-missed-pro', title: __('Missed Schedule', 'wp-scheduled-posts') },
];

const ScheduleHubFeature = (props) => {
    // @ts-ignore
    const is_pro = wpspSettingsGlobal?.pro_version ? true : false;

    if (is_pro) {
        return null;
    }

    return (
        <div
            className={classNames(
                'wprf-control',
                'wprf-schedule-hub-features',
                `wprf-${props.name}-schedule-hub-features`,
                props?.classes
            )}
        >
            <Card padding="lg" className="tw-bg-brand-50 tw-border-brand-100">
                <div className="tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-4 tw-mb-6">
                    <h2 className="tw-text-2xl tw-font-medium tw-text-ink tw-m-0">
                        {__(
                            'Upgrade to use these Pro Features',
                            'wp-scheduled-posts'
                        )}
                    </h2>

                    <ButtonLink
                        variant="primary"
                        target="_blank"
                        rel="noopener noreferrer"
                        href="https://schedulepress.com/#pricing"
                    >
                        {__('Upgrade To Pro', 'wp-scheduled-posts')}
                    </ButtonLink>
                </div>

                <div className="tw-grid tw-gap-4 tw-grid-cols-1 sm:tw-grid-cols-3">
                    {FEATURES.map((feature) => (
                        <div
                            key={feature.icon}
                            className="tw-flex tw-items-center tw-gap-3 tw-rounded-lg tw-bg-white tw-p-4"
                        >
                            <i
                                className={`wpsp-icon ${feature.icon} tw-inline-flex tw-h-10 tw-w-10 tw-shrink-0 tw-items-center tw-justify-center tw-rounded-full tw-bg-brand-50 tw-text-lg tw-text-brand-500`}
                            />
                            <h4 className="tw-text-base tw-font-medium tw-text-ink tw-m-0">
                                {feature.title}
                            </h4>
                        </div>
                    ))}
                </div>
            </Card>
        </div>
    );
};

export default ScheduleHubFeature;
