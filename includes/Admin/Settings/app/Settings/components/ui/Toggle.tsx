import React from 'react';
import cn from './cn';

export type ToggleSize = 'sm' | 'md';

/** Colour of the "on" track — green reads as connected/live, brand as enabled. */
export type ToggleTone = 'brand' | 'success';

export interface ToggleProps {
    checked: boolean;
    onChange?: (checked: boolean) => void;
    disabled?: boolean;
    size?: ToggleSize;
    tone?: ToggleTone;
    label?: React.ReactNode;
    description?: React.ReactNode;
    /** Places the switch after the label — the settings-row layout. */
    labelPosition?: 'left' | 'right';
    id?: string;
    name?: string;
    className?: string;
}

const track: Record<ToggleSize, string> = {
    sm: 'tw-w-9 tw-h-5',
    md: 'tw-w-11 tw-h-6',
};

const knob: Record<ToggleSize, string> = {
    sm: 'tw-h-4 tw-w-4',
    md: 'tw-h-5 tw-w-5',
};

const knobOn: Record<ToggleSize, string> = {
    sm: 'tw-translate-x-4',
    md: 'tw-translate-x-5',
};

const tones: Record<ToggleTone, string> = {
    brand: 'tw-bg-brand-500',
    success: 'tw-bg-success-500',
};

const Toggle: React.FC<ToggleProps> = ({
    checked,
    onChange,
    disabled,
    size = 'md',
    tone = 'brand',
    label,
    description,
    labelPosition = 'right',
    id,
    name,
    className,
}) => {
    const control = (
        <button
            type="button"
            role="switch"
            id={id}
            name={name}
            aria-checked={checked}
            disabled={disabled}
            onClick={() => !disabled && onChange && onChange(!checked)}
            className={cn(
                'wpsp-ui tw-relative tw-inline-flex tw-shrink-0 tw-items-center tw-rounded-full',
                'tw-border-0 tw-p-0 tw-cursor-pointer tw-transition-colors tw-duration-200',
                track[size],
                checked ? tones[tone] : 'tw-bg-[#d7dbdf]',
                disabled && 'tw-opacity-50 tw-cursor-not-allowed'
            )}
        >
            <span
                className={cn(
                    'tw-pointer-events-none tw-inline-block tw-rounded-full tw-bg-white',
                    'tw-shadow tw-transition-transform tw-duration-200 tw-ml-0.5',
                    knob[size],
                    checked ? knobOn[size] : 'tw-translate-x-0'
                )}
            />
        </button>
    );

    if (!label && !description) {
        return <span className={className}>{control}</span>;
    }

    return (
        <div
            className={cn(
                'tw-flex tw-items-start tw-gap-3',
                labelPosition === 'left' && 'tw-flex-row-reverse tw-justify-between',
                className
            )}
        >
            {control}

            <span className="tw-flex tw-flex-col tw-gap-0.5">
                {label && (
                    <label
                        htmlFor={id}
                        onClick={() => !disabled && onChange && onChange(!checked)}
                        className="tw-text-base tw-font-medium tw-text-ink tw-m-0 tw-cursor-pointer"
                    >
                        {label}
                    </label>
                )}
                {description && (
                    <span className="tw-text-xs tw-text-ink-muted">{description}</span>
                )}
            </span>
        </div>
    );
};

export default Toggle;
