import React, { useEffect } from 'react';
import ReactDOM from 'react-dom';
import cn from './cn';

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl';

export interface ModalProps {
    isOpen: boolean;
    onClose?: () => void;
    title?: React.ReactNode;
    description?: React.ReactNode;
    size?: ModalSize;
    /** Footer row — usually the confirm/cancel buttons. */
    footer?: React.ReactNode;
    showCloseButton?: boolean;
    closeOnOverlayClick?: boolean;
    className?: string;
    children?: React.ReactNode;
}

const sizes: Record<ModalSize, string> = {
    sm: 'tw-max-w-md',
    md: 'tw-max-w-xl',
    lg: 'tw-max-w-3xl',
    xl: 'tw-max-w-5xl',
};

const Modal: React.FC<ModalProps> = ({
    isOpen,
    onClose,
    title,
    description,
    size = 'md',
    footer,
    showCloseButton = true,
    closeOnOverlayClick = true,
    className,
    children,
}) => {
    useEffect(() => {
        if (!isOpen) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape' && onClose) {
                onClose();
            }
        };

        document.addEventListener('keydown', onKeyDown);

        // wp-admin keeps its own scrollbar; lock it while the dialog is up.
        const previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKeyDown);
            document.body.style.overflow = previousOverflow;
        };
    }, [isOpen, onClose]);

    if (!isOpen) {
        return null;
    }

    return ReactDOM.createPortal(
        <div
            /* `wpsp-ui-portal` re-applies the scoped base styles outside #wpsp-dashboard-body. */
            className="wpsp-ui-portal tw-fixed tw-inset-0 tw-z-[100000] tw-flex tw-items-center tw-justify-center tw-p-5"
        >
            <div
                className="tw-absolute tw-inset-0 tw-bg-ink/60 tw-animate-fade-in"
                onClick={closeOnOverlayClick ? onClose : undefined}
            />

            <div
                role="dialog"
                aria-modal="true"
                className={cn(
                    'tw-relative tw-w-full tw-bg-white tw-rounded-xl tw-shadow-popover',
                    'tw-max-h-[calc(100vh-80px)] tw-flex tw-flex-col tw-animate-scale-in',
                    sizes[size],
                    className
                )}
            >
                {(title || showCloseButton) && (
                    <div className="tw-flex tw-items-start tw-justify-between tw-gap-4 tw-p-6 tw-pb-4">
                        <div className="tw-min-w-0">
                            {title && (
                                <h2 className="tw-text-xl tw-font-medium tw-text-ink tw-m-0">
                                    {title}
                                </h2>
                            )}
                            {description && (
                                <p className="tw-text-sm tw-text-ink-muted tw-m-0 tw-mt-1">
                                    {description}
                                </p>
                            )}
                        </div>

                        {showCloseButton && (
                            <button
                                type="button"
                                aria-label="Close"
                                onClick={onClose}
                                className="wpsp-ui tw-shrink-0 tw-bg-transparent tw-border-0 tw-p-1 tw-cursor-pointer tw-text-ink-subtle hover:tw-text-ink"
                            >
                                <svg
                                    className="tw-h-5 tw-w-5"
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m5 5 10 10M15 5 5 15"
                                        stroke="currentColor"
                                        strokeWidth="1.8"
                                        strokeLinecap="round"
                                    />
                                </svg>
                            </button>
                        )}
                    </div>
                )}

                <div className="tw-flex-1 tw-overflow-y-auto tw-px-6 tw-py-2 tw-text-base tw-text-ink-muted">
                    {children}
                </div>

                {footer && (
                    <div className="tw-flex tw-items-center tw-justify-end tw-gap-3 tw-p-6 tw-pt-4">
                        {footer}
                    </div>
                )}
            </div>
        </div>,
        document.body
    );
};

export default Modal;
