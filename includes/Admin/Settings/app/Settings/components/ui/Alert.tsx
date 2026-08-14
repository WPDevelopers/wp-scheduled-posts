import React from 'react';
import cn from './cn';

export type AlertTone = 'info' | 'success' | 'warning' | 'danger' | 'brand';

export interface AlertProps
    extends Omit<React.HTMLAttributes<HTMLDivElement>, 'title'> {
    tone?: AlertTone;
    title?: React.ReactNode;
    icon?: React.ReactNode;
    /** Renders a close affordance; the parent still owns the visibility state. */
    onDismiss?: () => void;
    actions?: React.ReactNode;
}

const tones: Record<AlertTone, string> = {
    info: 'tw-bg-[#f1f8fe] tw-border-[#cfe4f6] tw-text-ink',
    success: 'tw-bg-success-50 tw-border-[#b6e8d4] tw-text-ink',
    warning: 'tw-bg-warning-50 tw-border-warning-200 tw-text-ink',
    danger: 'tw-bg-danger-50 tw-border-[#f3c6cb] tw-text-ink',
    brand: 'tw-bg-brand-50 tw-border-brand-200 tw-text-ink',
};

const iconTones: Record<AlertTone, string> = {
    info: 'tw-text-[#2271b1]',
    success: 'tw-text-success-500',
    warning: 'tw-text-warning-500',
    danger: 'tw-text-danger-500',
    brand: 'tw-text-brand-500',
};

const defaultIcons: Record<AlertTone, React.ReactNode> = {
    info: <path d="M10 9v5m0-8.5v.6" strokeWidth="2" strokeLinecap="round" />,
    success: <path d="m6 10.5 2.6 2.6L14.5 7" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />,
    warning: <path d="M10 6v5m0 3v.6" strokeWidth="2" strokeLinecap="round" />,
    danger: <path d="m6.5 6.5 7 7m0-7-7 7" strokeWidth="2" strokeLinecap="round" />,
    brand: <path d="M10 9v5m0-8.5v.6" strokeWidth="2" strokeLinecap="round" />,
};

const Alert: React.FC<AlertProps> = ({
    tone = 'info',
    title,
    icon,
    onDismiss,
    actions,
    className,
    children,
    ...rest
}) => (
    <div
        role="alert"
        className={cn(
            'tw-flex tw-items-start tw-gap-3 tw-rounded-lg tw-border tw-border-solid tw-p-4',
            tones[tone],
            className
        )}
        {...rest}
    >
        <span className={cn('tw-shrink-0 tw-mt-0.5', iconTones[tone])}>
            {icon || (
                <svg
                    className="tw-h-5 tw-w-5"
                    viewBox="0 0 20 20"
                    fill="none"
                    stroke="currentColor"
                    aria-hidden="true"
                >
                    <circle cx="10" cy="10" r="8.25" strokeWidth="1.5" />
                    {defaultIcons[tone]}
                </svg>
            )}
        </span>

        <div className="tw-flex-1 tw-min-w-0">
            {title && (
                <p className="tw-text-base tw-font-medium tw-text-ink tw-m-0 tw-mb-1">
                    {title}
                </p>
            )}
            {children && (
                <div className="tw-text-sm tw-text-ink-muted [&_p]:tw-m-0">{children}</div>
            )}
            {actions && <div className="tw-flex tw-gap-2 tw-mt-3">{actions}</div>}
        </div>

        {onDismiss && (
            <button
                type="button"
                aria-label="Dismiss"
                onClick={onDismiss}
                className="wpsp-ui tw-shrink-0 tw-bg-transparent tw-border-0 tw-p-1 tw-cursor-pointer tw-text-ink-subtle hover:tw-text-ink"
            >
                <svg className="tw-h-4 tw-w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                    <path
                        d="m4 4 8 8m0-8-8 8"
                        stroke="currentColor"
                        strokeWidth="1.8"
                        strokeLinecap="round"
                    />
                </svg>
            </button>
        )}
    </div>
);

export default Alert;
