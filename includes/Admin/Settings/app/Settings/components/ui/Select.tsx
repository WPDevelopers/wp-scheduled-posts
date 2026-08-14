import React from 'react';
import cn from './cn';

export interface SelectOption {
    label: string;
    value: string | number;
    disabled?: boolean;
}

interface BaseProps {
    options?: SelectOption[];
    invalid?: boolean;
    selectSize?: 'sm' | 'md' | 'lg';
    placeholder?: string;
    wrapperClassName?: string;
}

export type SelectProps = BaseProps &
    Omit<React.SelectHTMLAttributes<HTMLSelectElement>, 'size'>;

const heights = {
    sm: 'tw-min-h-[32px]',
    md: 'tw-min-h-[40px]',
    lg: 'tw-min-h-[48px]',
};

/**
 * Native `<select>` with the platform arrow swapped for our own, so the control
 * matches `Input` on every OS. Complex cases still use `react-select`.
 */
const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
    (
        {
            options,
            invalid,
            selectSize = 'md',
            placeholder,
            wrapperClassName,
            className,
            disabled,
            children,
            ...rest
        },
        ref
    ) => (
        <div className={cn('tw-relative tw-w-full', wrapperClassName)}>
            <select
                ref={ref}
                disabled={disabled}
                className={cn(
                    'wpsp-ui tw-w-full tw-appearance-none tw-bg-white tw-rounded-md tw-m-0',
                    'tw-border tw-border-solid tw-pl-3 tw-pr-9 tw-text-base tw-text-ink',
                    'tw-shadow-none tw-transition-colors tw-duration-150 tw-cursor-pointer',
                    invalid ? 'tw-border-danger-500' : 'tw-border-line-strong',
                    'focus:tw-border-brand-500 focus:tw-shadow-focus focus:tw-outline-none',
                    disabled && 'tw-bg-canvas tw-opacity-60 tw-cursor-not-allowed',
                    heights[selectSize],
                    className
                )}
                {...rest}
            >
                {placeholder && (
                    <option value="" disabled>
                        {placeholder}
                    </option>
                )}

                {options
                    ? options.map((option) => (
                          <option
                              key={option.value}
                              value={option.value}
                              disabled={option.disabled}
                          >
                              {option.label}
                          </option>
                      ))
                    : children}
            </select>

            <svg
                className="tw-pointer-events-none tw-absolute tw-right-3 tw-top-1/2 tw--translate-y-1/2 tw-h-4 tw-w-4 tw-text-ink-subtle"
                viewBox="0 0 20 20"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="m5 7.5 5 5 5-5"
                    stroke="currentColor"
                    strokeWidth="1.6"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                />
            </svg>
        </div>
    )
);

Select.displayName = 'Select';

export default Select;
