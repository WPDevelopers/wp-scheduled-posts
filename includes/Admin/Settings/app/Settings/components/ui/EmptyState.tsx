import React from 'react';
import cn from './cn';

export interface EmptyStateProps
    extends Omit<React.HTMLAttributes<HTMLDivElement>, 'title'> {
    icon?: React.ReactNode;
    image?: string;
    title: React.ReactNode;
    description?: React.ReactNode;
    action?: React.ReactNode;
    size?: 'sm' | 'md';
}

const EmptyState: React.FC<EmptyStateProps> = ({
    icon,
    image,
    title,
    description,
    action,
    size = 'md',
    className,
    ...rest
}) => (
    <div
        className={cn(
            'tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-center',
            size === 'sm' ? 'tw-py-8 tw-px-4' : 'tw-py-14 tw-px-6',
            className
        )}
        {...rest}
    >
        {image ? (
            <img src={image} alt="" className="tw-mb-5 tw-max-w-[180px]" />
        ) : (
            icon && (
                <span className="tw-mb-4 tw-inline-flex tw-h-12 tw-w-12 tw-items-center tw-justify-center tw-rounded-full tw-bg-brand-50 tw-text-brand-500">
                    {icon}
                </span>
            )
        )}

        <h3 className="tw-text-xl tw-font-medium tw-text-ink tw-m-0">{title}</h3>

        {description && (
            <p className="tw-text-base tw-text-ink-muted tw-m-0 tw-mt-2 tw-max-w-md">
                {description}
            </p>
        )}

        {action && <div className="tw-mt-6">{action}</div>}
    </div>
);

export default EmptyState;
