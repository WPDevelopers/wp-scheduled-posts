import { __ } from '@wordpress/i18n';
import { useBuilderContext } from 'quickbuilder';
import React from 'react';
import { ButtonLink, Card } from './components/ui';

interface ResourceCard {
    icon: string;
    title: string;
    description: string;
    linkLabel: string;
    href: string;
}

const RESOURCES: ResourceCard[] = [
    {
        icon: 'wpsp-file',
        title: __('Documentation', 'wp-scheduled-posts'),
        description: __(
            'Get started spending some time with the documentation to get familiar with SchedulePress.',
            'wp-scheduled-posts'
        ),
        linkLabel: __('Documentation', 'wp-scheduled-posts'),
        href: 'https://wpdeveloper.com/docs-category/wp-scheduled-posts/',
    },
    {
        icon: 'wpsp-puzzle',
        title: __('Contribute to SchedulePress', 'wp-scheduled-posts'),
        description: __(
            'You can contribute to making SchedulePress better by reporting bugs',
            'wp-scheduled-posts'
        ),
        linkLabel: __('Report A Bug', 'wp-scheduled-posts'),
        href: 'https://wordpress.org/support/plugin/wp-scheduled-posts/',
    },
    {
        icon: 'wpsp-comment',
        title: __('Need Help?', 'wp-scheduled-posts'),
        description: __(
            'Stuck with something? Get help from the community WPDeveloper Forum or Facebook Community.',
            'wp-scheduled-posts'
        ),
        linkLabel: __('Get Support', 'wp-scheduled-posts'),
        href: 'https://wpdeveloper.com/support/',
    },
    {
        icon: 'wpsp-chat-2',
        title: __('Show your Love', 'wp-scheduled-posts'),
        description: __(
            'We love to have you in the SchedulePress family. We are making it more awesome every day.',
            'wp-scheduled-posts'
        ),
        linkLabel: __('Show your Love', 'wp-scheduled-posts'),
        href: 'https://wordpress.org/support/plugin/wp-scheduled-posts/reviews/',
    },
];

/**
 * Promo card at the top of the sidebar. The same shell serves both the
 * "upgrade" and "manage license" states — only the palette and copy differ.
 */
const PromoCard = ({ image, tone, title, description, linkLabel, href }) => {
    const isPro = tone === 'pro';

    return (
        <div
            className={[
                'tw-relative tw-z-[1] tw-overflow-hidden tw-rounded-lg tw-p-4 tw-pb-6',
                'tw-text-center',
                isPro ? 'tw-bg-brand-50' : 'tw-bg-warning-50',
            ].join(' ')}
        >
            {/* Soft elliptical wash behind the artwork, matching the legacy design. */}
            <span
                aria-hidden="true"
                className={[
                    'tw-absolute tw-inset-x-0 tw-bottom-0 tw-z-[-1] tw-h-3/4',
                    'tw-[clip-path:ellipse(95%_100%_at_center_bottom)]',
                    isPro
                        ? 'tw-bg-gradient-to-b tw-from-brand-200 tw-to-brand-50'
                        : 'tw-bg-gradient-to-b tw-from-warning-200 tw-to-warning-100',
                ].join(' ')}
            />

            <img
                src={image}
                alt={__('upgrade-pro-img', 'wp-scheduled-posts')}
                className="tw-mx-auto tw-mb-4 tw-max-w-full"
            />

            <h3
                className={[
                    'tw-text-xl tw-font-medium tw-m-0 tw-mb-3',
                    isPro ? 'tw-text-ink' : 'tw-text-warning-500',
                ].join(' ')}
            >
                {title}
            </h3>

            <p className="tw-text-xs tw-text-ink tw-m-0 tw-mb-4">{description}</p>

            <ButtonLink
                variant={isPro ? 'primary' : 'warning'}
                size="lg"
                target="_blank"
                rel="noopener noreferrer"
                href={href}
                fullWidth
            >
                {linkLabel}
            </ButtonLink>
        </div>
    );
};

const Sidebar = ({ props }) => {
    const builderContext = useBuilderContext();

    if (
        props.id !== 'tab-sidebar-layout' ||
        builderContext.config.active === 'layout_calendar'
    ) {
        return null;
    }

    // @ts-ignore
    const is_pro = wpspSettingsGlobal.pro_version;
    // @ts-ignore
    const promoImage = `${wpspSettingsGlobal?.image_path}upgrade-pro-new.png`;

    return (
        <div className="wpsp-admin-sidebar tw-flex tw-w-full tw-flex-col tw-gap-3 tw-bg-white tw-p-6 lg:tw-w-[300px] lg:tw-shrink-0 lg:tw-p-8">
            <div className="tw-mb-4">
                {is_pro ? (
                    <PromoCard
                        image={promoImage}
                        tone="pro"
                        title={__('Manage License', 'wp-scheduled-posts')}
                        description={__(
                            'Supercharge your content schedule and have peace of mind',
                            'wp-scheduled-posts'
                        )}
                        linkLabel={__('Manage License', 'wp-scheduled-posts')}
                        href="https://store.wpdeveloper.com/"
                    />
                ) : (
                    <PromoCard
                        image={promoImage}
                        tone="free"
                        title={__('Get Unlimited Features', 'wp-scheduled-posts')}
                        description={__(
                            'Supercharge your content schedule and have peace of mind',
                            'wp-scheduled-posts'
                        )}
                        linkLabel={__('Upgrade To Pro', 'wp-scheduled-posts')}
                        href="https://schedulepress.com/#pricing"
                    />
                )}
            </div>

            {RESOURCES.map((resource) => (
                <Card key={resource.icon} padding="sm">
                    <i
                        className={`wpsp-icon ${resource.icon} tw-mb-5 tw-inline-block tw-rounded-full tw-bg-brand-50 tw-p-3 tw-text-lg tw-text-brand-500`}
                    />

                    <h3 className="tw-text-xl tw-font-medium tw-text-ink tw-m-0 tw-mb-3">
                        {resource.title}
                    </h3>

                    <p className="tw-text-base tw-text-ink-muted tw-m-0 tw-mb-5">
                        {resource.description}
                    </p>

                    <ButtonLink
                        variant="link"
                        target="_blank"
                        rel="noopener noreferrer"
                        href={resource.href}
                        rightIcon={
                            <svg
                                className="tw-h-3.5 tw-w-3.5"
                                viewBox="0 0 16 16"
                                fill="none"
                                aria-hidden="true"
                            >
                                <path
                                    d="M6 3.5 10.5 8 6 12.5"
                                    stroke="currentColor"
                                    strokeWidth="1.8"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                />
                            </svg>
                        }
                    >
                        {resource.linkLabel}
                    </ButtonLink>
                </Card>
            ))}
        </div>
    );
};

export default Sidebar;
