import React from 'react';

/**
 * Inline nav icons. The bundled `wpsp` icon font does not cover every settings
 * tab and cannot be recoloured per-state as cleanly, so the shell draws its own.
 */
const Svg: React.FC<{ children: React.ReactNode; className?: string }> = ({
    children,
    className,
}) => (
    <svg
        className={className || 'tw-h-[18px] tw-w-[18px]'}
        viewBox="0 0 20 20"
        fill="none"
        stroke="currentColor"
        strokeWidth="1.6"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
    >
        {children}
    </svg>
);

export const IconSliders = () => (
    <Svg>
        <path d="M3 6h9M15 6h2M3 14h2M8 14h9" />
        <circle cx="13.5" cy="6" r="1.8" />
        <circle cx="6.5" cy="14" r="1.8" />
    </Svg>
);

export const IconCalendar = () => (
    <Svg>
        <rect x="2.75" y="4" width="14.5" height="13.25" rx="2.5" />
        <path d="M2.75 8h14.5M6.5 2.5v3M13.5 2.5v3" />
    </Svg>
);

export const IconMail = () => (
    <Svg>
        <rect x="2.5" y="4.25" width="15" height="11.5" rx="2.5" />
        <path d="m3.5 6 5.6 4.2a1.5 1.5 0 0 0 1.8 0L16.5 6" />
    </Svg>
);

export const IconShare = () => (
    <Svg>
        <circle cx="15" cy="4.5" r="2.25" />
        <circle cx="5" cy="10" r="2.25" />
        <circle cx="15" cy="15.5" r="2.25" />
        <path d="m7 8.9 6-3.3M7 11.1l6 3.3" />
    </Svg>
);

export const IconTemplate = () => (
    <Svg>
        <rect x="2.75" y="3" width="14.5" height="14" rx="2.5" />
        <path d="M2.75 7.5h14.5M7.5 7.5V17" />
    </Svg>
);

export const IconSparkles = () => (
    <Svg>
        <path d="M7.5 2.75 8.8 6.2 12.25 7.5 8.8 8.8 7.5 12.25 6.2 8.8 2.75 7.5 6.2 6.2Z" />
        <path d="M14.5 11.5l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7Z" />
    </Svg>
);

export const IconPlug = () => (
    <Svg>
        <path d="M7 2.75v4M13 2.75v4" />
        <path d="M4.75 6.75h10.5v3a5.25 5.25 0 0 1-10.5 0Z" />
        <path d="M10 15v2.25" />
    </Svg>
);

export const IconHub = () => (
    <Svg>
        <circle cx="10" cy="10" r="2.5" />
        <path d="M10 2.75v4.75M10 12.5v4.75M2.75 10h4.75M12.5 10h4.75" />
    </Svg>
);

export const IconKey = () => (
    <Svg>
        <circle cx="6.75" cy="6.75" r="3.5" />
        <path d="m9.4 9.4 7.35 7.35M13.5 13.5l1.75-1.75M15.5 15.5l1.75-1.75" />
    </Svg>
);

export const IconCheck = () => (
    <Svg className="tw-h-4 tw-w-4">
        <path d="m4 10.5 3.5 3.5L16 5.5" />
    </Svg>
);

export const IconBook = () => (
    <Svg className="tw-h-4 tw-w-4">
        <path d="M3 4.25A1.5 1.5 0 0 1 4.5 2.75H16v13H4.5A1.5 1.5 0 0 0 3 17.25Z" />
        <path d="M3 15.75A1.5 1.5 0 0 1 4.5 14.25H16" />
    </Svg>
);

export const IconLifebuoy = () => (
    <Svg className="tw-h-4 tw-w-4">
        <circle cx="10" cy="10" r="7.25" />
        <circle cx="10" cy="10" r="3" />
        <path d="m4.9 4.9 2.98 2.98M12.12 12.12l2.98 2.98M15.1 4.9l-2.98 2.98M7.88 12.12 4.9 15.1" />
    </Svg>
);

export const IconChevronRight = () => (
    <Svg className="tw-h-4 tw-w-4">
        <path d="M7.5 4.5 13 10l-5.5 5.5" />
    </Svg>
);

export const IconExternal = () => (
    <Svg className="tw-h-3.5 tw-w-3.5">
        <path d="M11 3.5h5.5V9M16.5 3.5 9 11" />
        <path d="M15 12v3.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 3 15.5v-9A1.5 1.5 0 0 1 4.5 5H8" />
    </Svg>
);

/**
 * Nav icon per settings tab. Tabs the Pro plugin adds later fall through to the
 * generic sliders glyph rather than rendering nothing.
 */
const TAB_ICONS: Record<string, React.FC> = {
    layout_general: IconSliders,
    layout_calendar: IconCalendar,
    layout_email_notify: IconMail,
    layout_social_profile: IconShare,
    layout_social_template: IconTemplate,
    layout_ai: IconSparkles,
    layout_mcp: IconPlug,
    layout_scheduling_hub: IconHub,
    layout_license: IconKey,
};

export function tabIcon(id: string): React.FC {
    return TAB_ICONS[id] || IconSliders;
}
