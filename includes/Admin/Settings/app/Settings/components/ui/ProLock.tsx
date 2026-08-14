import { __ } from '@wordpress/i18n';
import React from 'react';
import cn from './cn';
import { ButtonLink } from './Button';

export interface ProLockProps {
    title: React.ReactNode;
    description?: React.ReactNode;
    /** Bullet list of what the feature does, shown under the description. */
    points?: string[];
    href?: string;
    className?: string;
    children: React.ReactNode;
}

const LockIcon = () => (
    <svg
        className="tw-h-5 tw-w-5"
        viewBox="0 0 20 20"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.6"
        aria-hidden="true"
    >
        <rect x="4" y="8.5" width="12" height="8.5" rx="2.5" />
        <path d="M7 8.5V6a3 3 0 0 1 6 0v2.5" strokeLinecap="round" />
    </svg>
);

/**
 * A paid feature the current licence does not include.
 *
 * The controls stay on the page — seeing what you would get is the point of an
 * upsell — but they are veiled and taken out of the tab order, with one clear
 * call to action over them. That reads as locked; a form of individually
 * greyed-out inputs reads as broken.
 */
const ProLock: React.FC<ProLockProps> = ({
    title,
    description,
    points,
    href = 'https://schedulepress.com/#pricing',
    className,
    children,
}) => (
    <div className={cn('tw-relative', className)}>
        <div
            aria-hidden="true"
            /* `inert` would be the right tool here, but it is not in the
               React 17 typings — hidden from AT and from the tab order. */
            className="tw-pointer-events-none tw-select-none tw-opacity-40 tw-blur-[1.5px]"
        >
            {children}
        </div>

        <div className="tw-absolute tw-inset-0 tw-flex tw-items-start tw-justify-center tw-p-4 sm:tw-p-8">
            <div className="tw-w-full tw-max-w-md tw-rounded-lg tw-border tw-border-solid tw-border-warning-200 tw-bg-white tw-p-6 tw-text-center tw-shadow-raised">
                <span className="tw-mx-auto tw-mb-4 tw-flex tw-h-11 tw-w-11 tw-items-center tw-justify-center tw-rounded-full tw-bg-warning-50 tw-text-warning-600">
                    <LockIcon />
                </span>

                <h3 className="tw-m-0 tw-text-lg tw-font-semibold tw-text-ink">
                    {title}
                </h3>

                {description && (
                    <p className="tw-m-0 tw-mt-2 tw-text-base tw-text-ink-muted">
                        {description}
                    </p>
                )}

                {points && points.length > 0 && (
                    <ul className="tw-m-0 tw-mt-4 tw-flex tw-list-none tw-flex-col tw-gap-2 tw-p-0 tw-text-left">
                        {points.map((point) => (
                            <li
                                key={point}
                                className="tw-flex tw-items-start tw-gap-2 tw-text-sm tw-text-ink-muted"
                            >
                                <svg
                                    className="tw-mt-0.5 tw-h-4 tw-w-4 tw-shrink-0 tw-text-success-500"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m4.5 10.5 3.5 3.5 7.5-8"
                                        stroke="currentColor"
                                        strokeWidth="1.8"
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                    />
                                </svg>
                                {point}
                            </li>
                        ))}
                    </ul>
                )}

                <ButtonLink
                    variant="warning"
                    size="lg"
                    fullWidth
                    target="_blank"
                    rel="noopener noreferrer"
                    href={href}
                    className="tw-mt-5"
                >
                    {__('Upgrade to Pro', 'wp-scheduled-posts')}
                </ButtonLink>
            </div>
        </div>
    </div>
);

export default ProLock;
