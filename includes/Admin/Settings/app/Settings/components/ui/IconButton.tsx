import React from 'react';
import cn from './cn';
import Spinner from './Spinner';

export type IconButtonVariant = 'ghost' | 'outline' | 'solid' | 'danger';
export type IconButtonSize = 'sm' | 'md' | 'lg';

interface BaseProps {
    variant?: IconButtonVariant;
    size?: IconButtonSize;
    loading?: boolean;
    /** Required — the icon carries no text, so this is the only label. */
    label: string;
}

export type IconButtonProps = BaseProps &
    Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, keyof BaseProps>;

const variants: Record<IconButtonVariant, string> = {
    ghost: 'tw-bg-transparent tw-border-transparent tw-text-ink-muted hover:tw-bg-brand-50 hover:tw-text-brand-600',
    outline: 'tw-bg-white tw-border-line-strong tw-text-ink-muted hover:tw-border-brand-500 hover:tw-text-brand-600',
    solid: 'tw-bg-brand-500 tw-border-brand-500 tw-text-white hover:tw-bg-brand-600',
    danger: 'tw-bg-transparent tw-border-transparent tw-text-ink-muted hover:tw-bg-danger-50 hover:tw-text-danger-500',
};

const sizes: Record<IconButtonSize, string> = {
    sm: 'tw-h-8 tw-w-8',
    md: 'tw-h-10 tw-w-10',
    lg: 'tw-h-12 tw-w-12',
};

const IconButton = React.forwardRef<HTMLButtonElement, IconButtonProps>(
    (
        {
            variant = 'ghost',
            size = 'md',
            loading,
            label,
            className,
            disabled,
            children,
            type = 'button',
            ...rest
        },
        ref
    ) => (
        <button
            ref={ref}
            type={type}
            title={label}
            aria-label={label}
            disabled={disabled || loading}
            className={cn(
                'wpsp-ui tw-inline-flex tw-items-center tw-justify-center tw-rounded-md tw-p-0',
                'tw-border tw-border-solid tw-cursor-pointer tw-transition-colors tw-duration-150',
                'disabled:tw-cursor-not-allowed disabled:tw-opacity-50',
                variants[variant],
                sizes[size],
                className
            )}
            {...rest}
        >
            {loading ? <Spinner size="sm" /> : children}
        </button>
    )
);

IconButton.displayName = 'IconButton';

export default IconButton;
