import React from 'react';
import cn from './cn';

export interface SectionHeaderProps
    extends Omit<React.HTMLAttributes<HTMLDivElement>, 'title'> {
    title: React.ReactNode;
    description?: React.ReactNode;
    /** Right-aligned controls — buttons, toggles, filters. */
    actions?: React.ReactNode;
    icon?: React.ReactNode;
    size?: 'sm' | 'md' | 'lg';
    divider?: boolean;
}

const titleSizes = {
    sm: 'tw-text-lg',
    md: 'tw-text-xl',
    lg: 'tw-text-2xl',
};

const SectionHeader: React.FC<SectionHeaderProps> = ({
    title,
    description,
    actions,
    icon,
    size = 'md',
    divider,
    className,
    ...rest
}) => (
    <div
        className={cn(
            'tw-flex tw-flex-wrap tw-items-start tw-justify-between tw-gap-4',
            divider && 'tw-pb-4 tw-border-0 tw-border-b tw-border-solid tw-border-line',
            className
        )}
        {...rest}
    >
        <div className="tw-flex tw-items-start tw-gap-3 tw-min-w-0">
            {icon && (
                <span className="tw-inline-flex tw-h-10 tw-w-10 tw-shrink-0 tw-items-center tw-justify-center tw-rounded-full tw-bg-brand-50 tw-text-brand-500">
                    {icon}
                </span>
            )}

            <div className="tw-min-w-0">
                <h2
                    className={cn(
                        'tw-font-medium tw-text-ink tw-m-0',
                        titleSizes[size]
                    )}
                >
                    {title}
                </h2>
                {description && (
                    <p className="tw-text-base tw-text-ink-muted tw-m-0 tw-mt-1">
                        {description}
                    </p>
                )}
            </div>
        </div>

        {actions && (
            <div className="tw-flex tw-items-center tw-gap-2 tw-shrink-0">{actions}</div>
        )}
    </div>
);

export default SectionHeader;
