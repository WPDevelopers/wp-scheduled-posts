import React from 'react';
import cn from './cn';

export type SpinnerSize = 'xs' | 'sm' | 'md' | 'lg';

export interface SpinnerProps extends React.SVGAttributes<SVGSVGElement> {
    size?: SpinnerSize;
}

const sizes: Record<SpinnerSize, string> = {
    xs: 'tw-h-3 tw-w-3',
    sm: 'tw-h-4 tw-w-4',
    md: 'tw-h-5 tw-w-5',
    lg: 'tw-h-6 tw-w-6',
};

const Spinner: React.FC<SpinnerProps> = ({ size = 'sm', className, ...rest }) => (
    <svg
        className={cn('tw-animate-spin', sizes[size], className)}
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true"
        focusable="false"
        {...rest}
    >
        <circle
            className="tw-opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="3"
        />
        <path
            className="tw-opacity-90"
            fill="currentColor"
            d="M12 2a10 10 0 0 1 10 10h-3a7 7 0 0 0-7-7V2Z"
        />
    </svg>
);

export default Spinner;
