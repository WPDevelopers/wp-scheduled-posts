import React from 'react';
import cn from './cn';
import { ProBadge } from './Badge';

export interface SettingRowProps extends React.HTMLAttributes<HTMLDivElement> {
    label: React.ReactNode;
    description?: React.ReactNode;
    /** The control itself — toggle, input, select, button group. */
    control?: React.ReactNode;
    /** Marks the row as a paid feature and dims it. */
    isPro?: boolean;
    /** Stacks the control under the label instead of beside it. */
    layout?: 'inline' | 'stacked';
    divider?: boolean;
    /** Extra content revealed under the row, e.g. when the toggle is on. */
    children?: React.ReactNode;
}

/**
 * The repeating "label + description on the left, control on the right" row
 * that makes up most of the SchedulePress settings screens.
 */
const SettingRow: React.FC<SettingRowProps> = ({
    label,
    description,
    control,
    isPro,
    layout = 'inline',
    divider,
    className,
    children,
    ...rest
}) => (
    <div
        className={cn(
            'tw-py-4',
            divider && 'tw-border-0 tw-border-b tw-border-solid tw-border-line',
            className
        )}
        {...rest}
    >
        <div
            className={cn(
                'tw-flex tw-gap-4',
                layout === 'inline'
                    ? 'tw-flex-wrap tw-items-center tw-justify-between'
                    : 'tw-flex-col'
            )}
        >
            <div className="tw-min-w-0 tw-flex-1">
                <div className="tw-flex tw-items-center tw-gap-2">
                    <span className="tw-text-base tw-font-medium tw-text-ink">
                        {label}
                    </span>
                    {isPro && <ProBadge />}
                </div>

                {description && (
                    <p className="tw-text-sm tw-text-ink-muted tw-m-0 tw-mt-1">
                        {description}
                    </p>
                )}
            </div>

            {control && (
                <div
                    className={cn(
                        // Stacked controls are block-level: a flex container
                        // would size a full-width child to its content.
                        layout === 'inline'
                            ? 'tw-flex tw-items-center tw-gap-2 tw-shrink-0'
                            : 'tw-block tw-w-full tw-min-w-0',
                        isPro && 'tw-opacity-60 tw-pointer-events-none'
                    )}
                >
                    {control}
                </div>
            )}
        </div>

        {children && <div className="tw-mt-4">{children}</div>}
    </div>
);

export default SettingRow;
