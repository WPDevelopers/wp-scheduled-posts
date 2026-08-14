import React from 'react';
import cn from './cn';
import Badge from './Badge';

export interface TabItem {
    id: string;
    /** Omit to render the icon alone; `ariaLabel` then names the tab. */
    label?: React.ReactNode;
    icon?: React.ReactNode;
    /** Accessible name and tooltip. Required when there is no visible label. */
    ariaLabel?: string;
    /** Rendered as a small pill after the label — counts, "Pro", "New". */
    badge?: React.ReactNode;
    disabled?: boolean;
}

export interface TabsProps {
    items: TabItem[];
    activeId: string;
    onChange: (id: string) => void;
    /**
     * `logo` is a segmented control for icon-only tabs: the active one lifts
     * onto white rather than filling with brand colour, which would fight the
     * platform logos sitting on it.
     */
    variant?: 'underline' | 'pill' | 'vertical' | 'logo';
    className?: string;
}

const Tabs: React.FC<TabsProps> = ({
    items,
    activeId,
    onChange,
    variant = 'underline',
    className,
}) => (
    <div
        role="tablist"
        className={cn(
            variant === 'vertical'
                ? 'tw-flex tw-flex-col tw-gap-1'
                : 'tw-flex tw-items-center tw-gap-1 tw-overflow-x-auto',
            variant === 'underline' &&
                'tw-border-0 tw-border-b tw-border-solid tw-border-line',
            className
        )}
    >
        {items.map((item) => {
            const isActive = item.id === activeId;

            return (
                <button
                    key={item.id}
                    type="button"
                    role="tab"
                    aria-selected={isActive}
                    aria-label={item.ariaLabel}
                    title={item.ariaLabel}
                    disabled={item.disabled}
                    onClick={() => !item.disabled && onChange(item.id)}
                    className={cn(
                        // No background here: utilities have equal specificity,
                        // so a base `bg-transparent` can out-order the active
                        // `bg-brand-500` in the stylesheet and win.
                        'wpsp-ui tw-inline-flex tw-items-center tw-gap-2',
                        'tw-text-base tw-font-medium tw-cursor-pointer tw-whitespace-nowrap',
                        'tw-transition-colors tw-duration-150 tw-border-0',
                        item.disabled && 'tw-opacity-50 tw-cursor-not-allowed',

                        variant === 'underline' && [
                            'tw-bg-transparent tw-px-4 tw-py-3 tw--mb-px tw-border-b-2 tw-border-solid',
                            isActive
                                ? 'tw-border-brand-500 tw-text-brand-600'
                                : 'tw-border-transparent tw-text-ink-muted hover:tw-text-ink',
                        ],

                        variant === 'pill' && [
                            'tw-px-4 tw-py-2 tw-rounded-md',
                            isActive
                                ? 'tw-bg-brand-500 tw-text-white tw-shadow-card'
                                : 'tw-bg-transparent tw-text-ink-muted hover:tw-bg-white hover:tw-text-brand-600',
                        ],

                        variant === 'vertical' && [
                            'tw-px-4 tw-py-2.5 tw-rounded-md tw-justify-start tw-text-left tw-w-full',
                            isActive
                                ? 'tw-bg-brand-50 tw-text-brand-600'
                                : 'tw-bg-transparent tw-text-ink-muted hover:tw-bg-canvas hover:tw-text-ink',
                        ],

                        variant === 'logo' && [
                            'tw-px-3 tw-py-2 tw-rounded',
                            isActive
                                ? 'tw-bg-white tw-shadow-card'
                                : // Inactive logos are dimmed rather than
                                  // recoloured, so each brand stays itself.
                                  'tw-bg-transparent tw-opacity-45 hover:tw-opacity-100',
                        ]
                    )}
                >
                    {item.icon}
                    {item.label && <span className="tw-flex-1">{item.label}</span>}
                    {item.badge &&
                        (typeof item.badge === 'string' ? (
                            <Badge size="sm" tone={isActive ? 'brand' : 'neutral'}>
                                {item.badge}
                            </Badge>
                        ) : (
                            item.badge
                        ))}
                </button>
            );
        })}
    </div>
);

export default Tabs;
