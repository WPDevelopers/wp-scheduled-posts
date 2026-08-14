import React from 'react';
import cn from './cn';

export interface DividerProps extends React.HTMLAttributes<HTMLDivElement> {
    orientation?: 'horizontal' | 'vertical';
    /** Centred text on the rule — "or", "Advanced", … */
    label?: React.ReactNode;
    spacing?: 'sm' | 'md' | 'lg';
}

const spacings = {
    sm: 'tw-my-3',
    md: 'tw-my-5',
    lg: 'tw-my-8',
};

const Divider: React.FC<DividerProps> = ({
    orientation = 'horizontal',
    label,
    spacing = 'md',
    className,
    ...rest
}) => {
    if (orientation === 'vertical') {
        return (
            <div
                className={cn('tw-w-px tw-self-stretch tw-bg-line tw-mx-3', className)}
                {...rest}
            />
        );
    }

    if (label) {
        return (
            <div
                className={cn(
                    'tw-flex tw-items-center tw-gap-3',
                    spacings[spacing],
                    className
                )}
                {...rest}
            >
                <span className="tw-h-px tw-flex-1 tw-bg-line" />
                <span className="tw-text-xs tw-text-ink-subtle">{label}</span>
                <span className="tw-h-px tw-flex-1 tw-bg-line" />
            </div>
        );
    }

    return (
        <div
            className={cn('tw-h-px tw-w-full tw-bg-line', spacings[spacing], className)}
            {...rest}
        />
    );
};

export default Divider;
