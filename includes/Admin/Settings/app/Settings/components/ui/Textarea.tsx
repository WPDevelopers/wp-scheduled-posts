import React from 'react';
import cn from './cn';
import { bareInputClass, inputShellClass } from './Input';

export interface TextareaProps
    extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
    invalid?: boolean;
    wrapperClassName?: string;
}

const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
    ({ invalid, wrapperClassName, className, disabled, rows = 4, ...rest }, ref) => (
        <div
            className={cn(
                inputShellClass(invalid, disabled),
                'tw-py-2 tw-items-stretch',
                wrapperClassName
            )}
        >
            <textarea
                ref={ref}
                rows={rows}
                disabled={disabled}
                className={cn(bareInputClass, 'tw-resize-y tw-leading-6', className)}
                {...rest}
            />
        </div>
    )
);

Textarea.displayName = 'Textarea';

export default Textarea;
