import React from 'react';
import cn from './cn';

export type InputSize = 'sm' | 'md' | 'lg';

interface BaseProps {
    inputSize?: InputSize;
    invalid?: boolean;
    /** Rendered inside the field, before the text. */
    prefix?: React.ReactNode;
    /** Rendered inside the field, after the text — buttons, units, badges. */
    suffix?: React.ReactNode;
    wrapperClassName?: string;
}

export type InputProps = BaseProps &
    Omit<React.InputHTMLAttributes<HTMLInputElement>, 'size' | 'prefix'>;

const heights: Record<InputSize, string> = {
    sm: 'tw-min-h-[32px]',
    md: 'tw-min-h-[40px]',
    lg: 'tw-min-h-[48px]',
};

export const inputShellClass = (invalid?: boolean, disabled?: boolean) =>
    cn(
        'tw-flex tw-items-center tw-gap-2 tw-w-full tw-bg-white tw-rounded-md',
        'tw-border tw-border-solid tw-transition-colors tw-duration-150 tw-px-3',
        invalid ? 'tw-border-danger-500' : 'tw-border-line-strong',
        !invalid && 'focus-within:tw-border-brand-500 focus-within:tw-shadow-focus',
        invalid && 'focus-within:tw-shadow-[0_0_0_3px_rgba(220,53,69,0.15)]',
        disabled && 'tw-bg-canvas tw-opacity-60 tw-cursor-not-allowed'
    );

/** Strips wp-admin's own input chrome so the shell above is the only border. */
export const bareInputClass =
    'wpsp-ui tw-flex-1 tw-w-full tw-min-w-0 tw-bg-transparent tw-border-0 tw-outline-none ' +
    'tw-shadow-none tw-p-0 tw-m-0 tw-text-base tw-text-ink placeholder:tw-text-ink-placeholder ' +
    'focus:tw-outline-none focus:tw-shadow-none disabled:tw-cursor-not-allowed';

const Input = React.forwardRef<HTMLInputElement, InputProps>(
    (
        {
            inputSize = 'md',
            invalid,
            prefix,
            suffix,
            wrapperClassName,
            className,
            disabled,
            ...rest
        },
        ref
    ) => (
        <div
            className={cn(
                inputShellClass(invalid, disabled),
                heights[inputSize],
                wrapperClassName
            )}
        >
            {prefix && (
                <span className="tw-flex tw-items-center tw-text-ink-subtle tw-shrink-0">
                    {prefix}
                </span>
            )}

            <input
                ref={ref}
                disabled={disabled}
                className={cn(bareInputClass, className)}
                {...rest}
            />

            {suffix && (
                <span className="tw-flex tw-items-center tw-text-ink-subtle tw-shrink-0">
                    {suffix}
                </span>
            )}
        </div>
    )
);

Input.displayName = 'Input';

export default Input;
