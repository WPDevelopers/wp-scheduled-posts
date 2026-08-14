import React from 'react';
import cn from './cn';

interface BaseProps {
    label?: React.ReactNode;
    description?: React.ReactNode;
    invalid?: boolean;
    wrapperClassName?: string;
}

export type CheckboxProps = BaseProps &
    Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'>;

/**
 * wp-admin styles `input[type="checkbox"]` heavily (its own tick glyph via
 * `::before`), so the native box is hidden and redrawn here.
 */
const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
    (
        { label, description, invalid, wrapperClassName, className, disabled, id, ...rest },
        ref
    ) => (
        <div
            className={cn(
                'tw-flex tw-items-start tw-gap-2.5',
                disabled && 'tw-opacity-60',
                wrapperClassName
            )}
        >
            <span className="tw-relative tw-flex tw-items-center tw-shrink-0 tw-mt-0.5">
                <input
                    ref={ref}
                    id={id}
                    type="checkbox"
                    disabled={disabled}
                    className={cn(
                        'wpsp-ui tw-peer tw-appearance-none tw-h-[18px] tw-w-[18px] tw-m-0',
                        'tw-rounded tw-border tw-border-solid tw-bg-white tw-cursor-pointer',
                        'tw-shadow-none tw-transition-colors tw-duration-150',
                        invalid ? 'tw-border-danger-500' : 'tw-border-line-strong',
                        'checked:tw-bg-brand-500 checked:tw-border-brand-500',
                        'focus:tw-shadow-focus focus:tw-outline-none',
                        'disabled:tw-cursor-not-allowed',
                        'before:tw-content-none',
                        className
                    )}
                    {...rest}
                />

                <svg
                    className="tw-pointer-events-none tw-absolute tw-left-0.5 tw-top-0.5 tw-h-3.5 tw-w-3.5 tw-text-white tw-opacity-0 peer-checked:tw-opacity-100"
                    viewBox="0 0 14 14"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="m3 7.2 2.6 2.6L11 4.4"
                        stroke="currentColor"
                        strokeWidth="2"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                </svg>
            </span>

            {(label || description) && (
                <span className="tw-flex tw-flex-col tw-gap-0.5">
                    {label && (
                        <label
                            htmlFor={id}
                            className="tw-text-base tw-text-ink tw-m-0 tw-cursor-pointer"
                        >
                            {label}
                        </label>
                    )}
                    {description && (
                        <span className="tw-text-xs tw-text-ink-muted">{description}</span>
                    )}
                </span>
            )}
        </div>
    )
);

Checkbox.displayName = 'Checkbox';

export default Checkbox;
