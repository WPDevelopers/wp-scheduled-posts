import React from 'react';
import cn from './cn';

export interface SkeletonProps extends React.HTMLAttributes<HTMLDivElement> {
    variant?: 'text' | 'rect' | 'circle';
    width?: number | string;
    height?: number | string;
    /** Number of stacked lines; only meaningful for `variant="text"`. */
    lines?: number;
}

const Bar: React.FC<{ variant: string; style: React.CSSProperties; className?: string }> = ({
    variant,
    style,
    className,
}) => (
    <div
        aria-hidden="true"
        style={style}
        className={cn(
            'wpsp-skeleton-shimmer tw-relative tw-overflow-hidden tw-bg-line',
            variant === 'circle' ? 'tw-rounded-full' : 'tw-rounded',
            className
        )}
    />
);

const Skeleton: React.FC<SkeletonProps> = ({
    variant = 'text',
    width,
    height,
    lines = 1,
    className,
    style,
    ...rest
}) => {
    const resolved: React.CSSProperties = {
        width: width,
        height: height || (variant === 'text' ? 12 : undefined),
        ...style,
    };

    if (variant === 'text' && lines > 1) {
        return (
            <div className={cn('tw-flex tw-flex-col tw-gap-2', className)} {...rest}>
                {Array.from({ length: lines }).map((_, index) => (
                    <Bar
                        key={index}
                        variant={variant}
                        style={{
                            ...resolved,
                            // Ragged last line reads as a paragraph, not a block.
                            width: index === lines - 1 ? '60%' : resolved.width || '100%',
                        }}
                    />
                ))}
            </div>
        );
    }

    return <Bar variant={variant} style={resolved} className={className} />;
};

export default Skeleton;
