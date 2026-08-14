import React from 'react';
import cn from './cn';
import Spinner from './Spinner';

export type ButtonVariant =
    | 'primary'
    | 'secondary'
    | 'outline'
    | 'ghost'
    | 'danger'
    | 'warning'
    | 'link';

export type ButtonSize = 'sm' | 'md' | 'lg';

interface BaseProps {
    variant?: ButtonVariant;
    size?: ButtonSize;
    /** Swaps the leading icon for a spinner and blocks interaction. */
    loading?: boolean;
    disabled?: boolean;
    fullWidth?: boolean;
    leftIcon?: React.ReactNode;
    rightIcon?: React.ReactNode;
    className?: string;
    children?: React.ReactNode;
}

export type ButtonProps = BaseProps &
    Omit<React.ButtonHTMLAttributes<HTMLButtonElement>, keyof BaseProps>;

export type ButtonLinkProps = BaseProps &
    Omit<React.AnchorHTMLAttributes<HTMLAnchorElement>, keyof BaseProps>;

const base =
    'wpsp-ui tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-font-medium ' +
    'tw-rounded-md tw-border tw-border-solid tw-transition-colors tw-duration-150 ' +
    'tw-cursor-pointer tw-no-underline tw-text-center tw-align-middle ' +
    'disabled:tw-cursor-not-allowed disabled:tw-opacity-50';

const variants: Record<ButtonVariant, string> = {
    primary:
        'tw-bg-brand-500 tw-border-brand-500 tw-text-white hover:tw-bg-brand-600 ' +
        'hover:tw-border-brand-600 hover:tw-text-white focus:tw-text-white active:tw-bg-brand-700',
    secondary:
        'tw-bg-brand-50 tw-border-brand-50 tw-text-brand-600 hover:tw-bg-brand-100 ' +
        'hover:tw-border-brand-100 hover:tw-text-brand-700 focus:tw-text-brand-600',
    outline:
        'tw-bg-white tw-border-line-strong tw-text-ink hover:tw-border-brand-500 ' +
        'hover:tw-text-brand-600 focus:tw-text-ink',
    ghost:
        'tw-bg-transparent tw-border-transparent tw-text-ink-muted hover:tw-bg-brand-50 ' +
        'hover:tw-text-brand-600 focus:tw-text-ink-muted',
    danger:
        'tw-bg-danger-500 tw-border-danger-500 tw-text-white hover:tw-bg-danger-600 ' +
        'hover:tw-border-danger-600 hover:tw-text-white focus:tw-text-white',
    warning:
        'tw-bg-warning-500 tw-border-warning-500 tw-text-white hover:tw-bg-warning-600 ' +
        'hover:tw-border-warning-600 hover:tw-text-white focus:tw-text-white',
    link:
        'tw-bg-transparent tw-border-transparent tw-text-brand-500 tw-p-0 tw-h-auto ' +
        'hover:tw-text-brand-700 hover:tw-underline focus:tw-text-brand-500',
};

const sizes: Record<ButtonSize, string> = {
    sm: 'tw-text-sm tw-px-3 tw-min-h-[32px]',
    md: 'tw-text-base tw-px-5 tw-min-h-[40px]',
    lg: 'tw-text-base tw-px-6 tw-min-h-[48px]',
};

function buildClassName(props: BaseProps): string {
    const { variant = 'primary', size = 'md', fullWidth, className } = props;

    return cn(
        base,
        variants[variant],
        // `link` carries its own spacing, so the size scale only sets type here.
        variant === 'link' ? 'tw-text-base tw-min-h-0' : sizes[size],
        fullWidth && 'tw-w-full',
        className
    );
}

function renderContent(props: BaseProps) {
    const { loading, leftIcon, rightIcon, children } = props;

    return (
        <>
            {loading ? <Spinner size="sm" /> : leftIcon}
            {children}
            {!loading && rightIcon}
        </>
    );
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>((props, ref) => {
    const {
        variant,
        size,
        loading,
        disabled,
        fullWidth,
        leftIcon,
        rightIcon,
        className,
        children,
        type = 'button',
        ...rest
    } = props;

    return (
        <button
            ref={ref}
            type={type}
            disabled={disabled || loading}
            className={buildClassName(props)}
            {...rest}
        >
            {renderContent(props)}
        </button>
    );
});

Button.displayName = 'Button';

/**
 * Anchor twin of `Button` — wp-admin pages link out constantly, and a real
 * `<a>` keeps middle-click and "open in new tab" working.
 */
export const ButtonLink = React.forwardRef<HTMLAnchorElement, ButtonLinkProps>(
    (props, ref) => {
        const {
            variant,
            size,
            loading,
            disabled,
            fullWidth,
            leftIcon,
            rightIcon,
            className,
            children,
            ...rest
        } = props;

        return (
            <a
                ref={ref}
                className={cn(
                    buildClassName(props),
                    disabled && 'tw-pointer-events-none tw-opacity-50'
                )}
                {...rest}
            >
                {renderContent(props)}
            </a>
        );
    }
);

ButtonLink.displayName = 'ButtonLink';

export default Button;
