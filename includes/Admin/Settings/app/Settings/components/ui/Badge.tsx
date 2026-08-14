import React from 'react';
import cn from './cn';

export type BadgeTone =
    | 'neutral'
    | 'brand'
    | 'success'
    | 'warning'
    | 'danger'
    | 'info';

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
    tone?: BadgeTone;
    size?: 'sm' | 'md';
    /** Leading status dot — useful for connection / schedule states. */
    dot?: boolean;
    icon?: React.ReactNode;
}

const tones: Record<BadgeTone, string> = {
    neutral: 'tw-bg-canvas tw-text-ink-muted',
    brand: 'tw-bg-brand-50 tw-text-brand-600',
    success: 'tw-bg-success-50 tw-text-success-600',
    warning: 'tw-bg-warning-50 tw-text-warning-600',
    danger: 'tw-bg-danger-50 tw-text-danger-600',
    info: 'tw-bg-[#f1f8fe] tw-text-[#2271b1]',
};

const dots: Record<BadgeTone, string> = {
    neutral: 'tw-bg-ink-subtle',
    brand: 'tw-bg-brand-500',
    success: 'tw-bg-success-500',
    warning: 'tw-bg-warning-500',
    danger: 'tw-bg-danger-500',
    info: 'tw-bg-[#2271b1]',
};

const Badge: React.FC<BadgeProps> = ({
    tone = 'neutral',
    size = 'md',
    dot,
    icon,
    className,
    children,
    ...rest
}) => (
    <span
        className={cn(
            'tw-inline-flex tw-items-center tw-gap-1.5 tw-rounded-full tw-font-medium',
            'tw-whitespace-nowrap tw-align-middle',
            size === 'sm' ? 'tw-text-xxs tw-px-2 tw-py-0.5' : 'tw-text-xs tw-px-2.5 tw-py-1',
            tones[tone],
            className
        )}
        {...rest}
    >
        {dot && (
            <span className={cn('tw-h-1.5 tw-w-1.5 tw-rounded-full', dots[tone])} />
        )}
        {icon}
        {children}
    </span>
);

/** The "Pro" pill that gates paid features across the settings screens. */
export const ProBadge: React.FC<React.HTMLAttributes<HTMLSpanElement>> = ({
    className,
    children,
    ...rest
}) => (
    <Badge
        tone="warning"
        size="sm"
        className={cn('tw-uppercase tw-tracking-wide', className)}
        {...rest}
    >
        {children || 'Pro'}
    </Badge>
);

export default Badge;
