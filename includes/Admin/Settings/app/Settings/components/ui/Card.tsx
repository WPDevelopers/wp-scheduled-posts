import React from 'react';
import cn from './cn';

export type CardPadding = 'none' | 'sm' | 'md' | 'lg';

export interface CardProps extends React.HTMLAttributes<HTMLDivElement> {
    padding?: CardPadding;
    /** `raised` lifts the card off the canvas; `flat` drops the border. */
    tone?: 'default' | 'raised' | 'flat';
    interactive?: boolean;
}

const paddings: Record<CardPadding, string> = {
    none: '',
    sm: 'tw-p-4',
    md: 'tw-p-6',
    lg: 'tw-p-8',
};

const Card: React.FC<CardProps> = ({
    padding = 'md',
    tone = 'default',
    interactive,
    className,
    children,
    ...rest
}) => (
    <div
        className={cn(
            'tw-bg-white tw-rounded-lg',
            tone === 'default' && 'tw-border tw-border-solid tw-border-line tw-shadow-card',
            tone === 'raised' && 'tw-border-0 tw-shadow-raised',
            tone === 'flat' && 'tw-border-0 tw-shadow-none',
            interactive &&
                'tw-transition-shadow tw-duration-150 hover:tw-shadow-raised tw-cursor-pointer',
            paddings[padding],
            className
        )}
        {...rest}
    >
        {children}
    </div>
);

export const CardHeader: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({
    className,
    children,
    ...rest
}) => (
    <div
        className={cn(
            'tw-flex tw-items-start tw-justify-between tw-gap-4 tw-mb-4',
            className
        )}
        {...rest}
    >
        {children}
    </div>
);

export const CardTitle: React.FC<React.HTMLAttributes<HTMLHeadingElement>> = ({
    className,
    children,
    ...rest
}) => (
    <h3
        className={cn('tw-text-xl tw-font-medium tw-text-ink tw-m-0', className)}
        {...rest}
    >
        {children}
    </h3>
);

export const CardDescription: React.FC<React.HTMLAttributes<HTMLParagraphElement>> = ({
    className,
    children,
    ...rest
}) => (
    <p className={cn('tw-text-base tw-text-ink-muted tw-m-0', className)} {...rest}>
        {children}
    </p>
);

export const CardBody: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({
    className,
    children,
    ...rest
}) => (
    <div className={cn('tw-text-base tw-text-ink-muted', className)} {...rest}>
        {children}
    </div>
);

export const CardFooter: React.FC<React.HTMLAttributes<HTMLDivElement>> = ({
    className,
    children,
    ...rest
}) => (
    <div
        className={cn(
            'tw-flex tw-items-center tw-gap-3 tw-mt-6 tw-pt-4 tw-border-0 tw-border-t ' +
                'tw-border-solid tw-border-line',
            className
        )}
        {...rest}
    >
        {children}
    </div>
);

export default Card;
