import React from 'react';
import cn from './cn';

export type AvatarSize = 'xs' | 'sm' | 'md' | 'lg';

export interface AvatarProps extends React.HTMLAttributes<HTMLSpanElement> {
    src?: string;
    alt?: string;
    /** Falls back to initials derived from this when `src` is missing. */
    name?: string;
    size?: AvatarSize;
    /** Small platform icon pinned to the bottom-right — social profiles. */
    badge?: React.ReactNode;
    shape?: 'circle' | 'rounded';
}

const sizes: Record<AvatarSize, string> = {
    xs: 'tw-h-6 tw-w-6 tw-text-xxs',
    sm: 'tw-h-8 tw-w-8 tw-text-xs',
    md: 'tw-h-10 tw-w-10 tw-text-sm',
    lg: 'tw-h-[50px] tw-w-[50px] tw-text-base',
};

function initials(name?: string): string {
    if (!name) {
        return '';
    }

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

const Avatar: React.FC<AvatarProps> = ({
    src,
    alt,
    name,
    size = 'md',
    badge,
    shape = 'circle',
    className,
    ...rest
}) => (
    <span className={cn('tw-relative tw-inline-flex tw-shrink-0', className)} {...rest}>
        <span
            className={cn(
                'tw-inline-flex tw-items-center tw-justify-center tw-overflow-hidden',
                'tw-bg-brand-50 tw-text-brand-600 tw-font-medium',
                shape === 'circle' ? 'tw-rounded-full' : 'tw-rounded-lg',
                sizes[size]
            )}
        >
            {src ? (
                <img
                    src={src}
                    alt={alt || name || ''}
                    className="tw-h-full tw-w-full tw-object-cover"
                />
            ) : (
                initials(name)
            )}
        </span>

        {badge && (
            <span className="tw-absolute tw--bottom-0.5 tw--right-0.5 tw-flex tw-items-center tw-justify-center tw-rounded-full tw-bg-white tw-p-0.5 tw-shadow-card">
                {badge}
            </span>
        )}
    </span>
);

export default Avatar;
