import { __ } from '@wordpress/i18n';
import React from 'react';
import { components } from 'react-select';
import cn from './ui/cn';

/**
 * Shared pieces for the "checkbox select" fields (post types, taxonomies,
 * categories, roles). react-select handles the menu and keyboard behaviour;
 * everything the user sees at rest is ours.
 */

/** Menu row with a real checkbox, so a multi-select looks like one. */
export const CheckboxOption = (props: any) => (
    <components.Option {...props}>
        <span className="tw-flex tw-items-center tw-gap-2.5 tw-px-2.5 tw-py-2">
            <span
                className={cn(
                    'tw-flex tw-h-[18px] tw-w-[18px] tw-shrink-0 tw-items-center tw-justify-center',
                    'tw-rounded-[5px] tw-border tw-border-solid tw-transition-colors tw-duration-150',
                    props.isSelected
                        ? 'tw-border-brand-500 tw-bg-brand-500'
                        : 'tw-border-line-strong tw-bg-white'
                )}
            >
                {props.isSelected && (
                    <svg
                        className="tw-h-3.5 tw-w-3.5 tw-text-white"
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
                )}
            </span>

            <span
                className={cn(
                    'tw-truncate',
                    props.isSelected ? 'tw-font-medium tw-text-ink' : 'tw-text-ink-muted'
                )}
            >
                {props.label}
            </span>
        </span>
    </components.Option>
);

interface ChipProps {
    label: React.ReactNode;
    onRemove: () => void;
}

/** One selected value. */
export const SelectChip: React.FC<ChipProps> = ({ label, onRemove }) => (
    <span className="tw-inline-flex tw-max-w-full tw-items-center tw-gap-1 tw-rounded tw-bg-brand-50 tw-py-1 tw-pl-2.5 tw-pr-1 tw-text-sm tw-font-medium tw-text-brand-600">
        <span className="tw-truncate">{label}</span>

        <button
            type="button"
            onClick={onRemove}
            aria-label={__('Remove', 'wp-scheduled-posts')}
            className="wpsp-ui tw-inline-flex tw-h-4 tw-w-4 tw-shrink-0 tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-full tw-border-0 tw-bg-transparent tw-p-0 tw-text-brand-500 hover:tw-bg-brand-200 hover:tw-text-brand-700"
        >
            <svg className="tw-h-2.5 tw-w-2.5" viewBox="0 0 10 10" fill="none" aria-hidden="true">
                <path
                    d="m2 2 6 6M8 2l-6 6"
                    stroke="currentColor"
                    strokeWidth="1.8"
                    strokeLinecap="round"
                />
            </svg>
        </button>
    </span>
);

interface ShellProps {
    /** Chips for the current selection; omitted entirely when nothing is set. */
    chips?: React.ReactNode;
    children: React.ReactNode;
    className?: string;
}

/**
 * The single bordered box the chips and the react-select input share, so the
 * field reads as one control rather than a list floating above a small box.
 */
export const SelectShell: React.FC<ShellProps> = ({ chips, children, className }) => (
    <div
        className={cn(
            'tw-flex tw-w-full tw-flex-wrap tw-items-center tw-gap-1.5 tw-rounded-md',
            'tw-border tw-border-solid tw-border-line-strong tw-bg-white tw-px-2.5 tw-py-2',
            'tw-transition-colors tw-duration-150',
            'focus-within:tw-border-brand-500 focus-within:tw-shadow-focus',
            className
        )}
    >
        {chips}
        <div className="tw-min-w-[130px] tw-flex-1">{children}</div>
    </div>
);
