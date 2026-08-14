import React from 'react';
import cn from './cn';

interface BaseProps {
    label?: React.ReactNode;
    description?: React.ReactNode;
    wrapperClassName?: string;
}

export type RadioProps = BaseProps &
    Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'>;

const Radio = React.forwardRef<HTMLInputElement, RadioProps>(
    ({ label, description, wrapperClassName, className, disabled, id, ...rest }, ref) => (
        <div
            className={cn(
                'tw-flex tw-items-start tw-gap-2.5',
                disabled && 'tw-opacity-60',
                wrapperClassName
            )}
        >
            <input
                ref={ref}
                id={id}
                type="radio"
                disabled={disabled}
                className={cn(
                    'wpsp-ui tw-appearance-none tw-h-[18px] tw-w-[18px] tw-m-0 tw-mt-0.5 tw-shrink-0',
                    'tw-rounded-full tw-border tw-border-solid tw-border-line-strong tw-bg-white',
                    'tw-cursor-pointer tw-shadow-none tw-transition-colors tw-duration-150',
                    'checked:tw-border-[5px] checked:tw-border-brand-500',
                    'focus:tw-shadow-focus focus:tw-outline-none',
                    'disabled:tw-cursor-not-allowed before:tw-content-none',
                    className
                )}
                {...rest}
            />

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

Radio.displayName = 'Radio';

export default Radio;
