import React from 'react';
import cn from './cn';

export interface FormFieldProps extends React.HTMLAttributes<HTMLDivElement> {
    /** Rendered as a `<label for>` when `htmlFor` is supplied. */
    label?: React.ReactNode;
    htmlFor?: string;
    hint?: React.ReactNode;
    error?: React.ReactNode;
    required?: boolean;
    children?: React.ReactNode;
}

/**
 * Label + control + hint/error stack. Every input in this library is usable on
 * its own, so this stays a separate wrapper rather than props on each control.
 */
const FormField: React.FC<FormFieldProps> = ({
    label,
    htmlFor,
    hint,
    error,
    required,
    className,
    children,
    ...rest
}) => (
    <div className={cn('tw-flex tw-flex-col tw-gap-1.5', className)} {...rest}>
        {label && (
            <label
                htmlFor={htmlFor}
                className="tw-text-base tw-font-medium tw-text-ink tw-m-0"
            >
                {label}
                {required && <span className="tw-text-danger-500 tw-ml-1">*</span>}
            </label>
        )}

        {children}

        {/* An error replaces the hint rather than stacking under it. */}
        {error ? (
            <span className="tw-text-xs tw-text-danger-500">{error}</span>
        ) : (
            hint && <span className="tw-text-xs tw-text-ink-muted">{hint}</span>
        )}
    </div>
);

export default FormField;
