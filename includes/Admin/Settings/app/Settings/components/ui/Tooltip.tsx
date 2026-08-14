import React from 'react';
import cn from './cn';

export type TooltipPlacement = 'top' | 'bottom' | 'left' | 'right';

export interface TooltipProps {
    content: React.ReactNode;
    placement?: TooltipPlacement;
    /** Caps the bubble width; pass a number of pixels. */
    maxWidth?: number;
    className?: string;
    children: React.ReactNode;
}

const placements: Record<TooltipPlacement, string> = {
    top: 'tw-bottom-full tw-left-1/2 tw--translate-x-1/2 tw-mb-2',
    bottom: 'tw-top-full tw-left-1/2 tw--translate-x-1/2 tw-mt-2',
    left: 'tw-right-full tw-top-1/2 tw--translate-y-1/2 tw-mr-2',
    right: 'tw-left-full tw-top-1/2 tw--translate-y-1/2 tw-ml-2',
};

/**
 * CSS-only tooltip — no positioning library, so it must stay inside a container
 * that does not clip overflow. Good enough for the icon hints in Settings.
 */
const Tooltip: React.FC<TooltipProps> = ({
    content,
    placement = 'top',
    maxWidth = 240,
    className,
    children,
}) => (
    <span className={cn('tw-relative tw-inline-flex tw-group', className)}>
        {children}

        <span
            role="tooltip"
            style={{ maxWidth }}
            className={cn(
                'tw-pointer-events-none tw-absolute tw-z-[100001] tw-w-max',
                'tw-rounded-md tw-bg-ink tw-px-2.5 tw-py-1.5 tw-text-xs tw-text-white',
                'tw-opacity-0 tw-invisible tw-transition-opacity tw-duration-150',
                'group-hover:tw-opacity-100 group-hover:tw-visible',
                placements[placement]
            )}
        >
            {content}
        </span>
    </span>
);

export default Tooltip;
