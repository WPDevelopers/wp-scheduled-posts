import React, { useEffect, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { useBuilderContext } from 'quickbuilder';
import cn from '../components/ui/cn';
import { Card, ProBadge, ProLock, Tabs } from '../components/ui';
import CustomField from '../fields/Field';
import { readRoute, writeRoute } from '../shell/routing';
import { HtmlField, RadioCardField, StackedField, TextField, ToggleField } from './controls';
import { hasLabel } from './emit';

/**
 * Renders the settings tree ourselves instead of using quickbuilder's
 * `FormBuilder`. quickbuilder is kept purely as the headless state layer:
 * `getFieldProps` still resolves each field's value, `onChange`, pro-gating and
 * `visible` (its `rules` conditional logic), so saving and dependencies behave
 * exactly as before — only the markup is ours.
 */

interface NodeProps {
    field: any;
    /** Path of the enclosing group, if any — group values are nested objects. */
    parent?: string[];
    parentType?: string;
    depth: number;
}

/** Control types the renderer draws itself; anything else is a custom field. */
const NATIVE_TYPES = ['toggle', 'text', 'number', 'radio-card', 'html', 'section', 'group', 'tab'];

const FieldNode: React.FC<NodeProps> = ({ field, parent, parentType, depth }) => {
    const builderContext = useBuilderContext();

    const props = builderContext.getFieldProps({
        ...field,
        ...(parent ? { parent, parenttype: parentType } : {}),
    });

    // `visible` is quickbuilder's evaluation of the field's `rules`.
    if (props?.visible === false) {
        return null;
    }

    /*
     * getFieldProps reads a group child through its parent path, but the
     * onChange it hands back resolves the target field from `target.name`
     * alone — which would write the child to the top level. quickbuilder's own
     * Group field patches this the same way, by writing to the full path.
     */
    if (parent && parentType === 'group') {
        props.onChange = (event: any) => {
            const value = event?.target?.value;
            builderContext.setFieldValue([...parent, field.name], value);
        };
    }

    switch (field?.type) {
        case 'section':
            return <SectionPanel field={field} props={props} depth={depth} />;

        case 'group':
            return <GroupPanel field={field} props={props} depth={depth} parent={parent} />;

        case 'tab':
            return <NestedTabs field={field} depth={depth} />;

        case 'toggle':
            return <ToggleField props={props} />;

        case 'text':
        case 'number':
            return <TextField props={props} />;

        case 'radio-card':
            return <RadioCardField props={props} />;

        case 'html':
            return <HtmlField props={props} />;

        case 'action':
            /*
             * An escape hatch other plugins render into: the field names a WP
             * filter and whatever that filter returns is the field. The Pro
             * plugin's license activation form arrives this way.
             */
            return (
                <div className="tw-py-2">
                    {applyFilters(field.action, '', props) as React.ReactNode}
                </div>
            );

        default: {
            // Everything else is one of our own field components. `Field` is the
            // same router the `custom_field` filter used to go through.
            const rendered = CustomField(null, field?.type, props);

            if (!rendered) {
                return null;
            }

            // Social platform and calendar fields own their whole panel; the
            // rest are ordinary controls that still want a label above them.
            return NEEDS_OWN_LAYOUT.includes(field?.type) ? (
                <div className="tw-py-2">{rendered}</div>
            ) : (
                <StackedField props={props}>{rendered}</StackedField>
            );
        }
    }
};

/** Field types that render their own heading and card, so we add no chrome. */
const NEEDS_OWN_LAYOUT = [
    'features',
    'schedule-hub-features',
    'license',
    'calendar',
    'mcp',
    'video',
    'facebook',
    'twitter',
    'linkedin',
    'pinterest',
    'instagram',
    'medium',
    'threads',
    'bluesky',
    'mastodon',
    'google-business',
    'auto-scheduler',
    'manual-scheduler',
    'pro-toggle',
    'list',
    'resources',
];

export const FieldList: React.FC<{
    fields: any[];
    parent?: string[];
    parentType?: string;
    depth?: number;
}> = ({ fields, parent, parentType, depth = 0 }) => (
    <>
        {(fields || []).map((field, index) => (
            <FieldNode
                key={field?.name || index}
                field={field}
                parent={parent}
                parentType={parentType}
                depth={depth}
            />
        ))}
    </>
);

/**
 * A top-level section becomes a card. Nested sections stay inside their parent
 * card as a titled block, so the page never turns into cards within cards.
 */
const SectionPanel: React.FC<{ field: any; props: any; depth: number }> = ({
    field,
    props,
    depth,
}) => {
    /*
     * Sections whose children all draw their own card (social platforms, the
     * calendar, the license form) would otherwise sit inside a second card
     * repeating the page heading. Render those as a bare stack.
     */
    const childrenSelfContained =
        (field?.fields || []).length > 0 &&
        (field?.fields || []).every((child: any) =>
            NEEDS_OWN_LAYOUT.includes(child?.type) || child?.type === 'action'
        );

    /*
     * The SCSS for screens still on the old styles (calendar, scheduling hub,
     * modals) is written against quickbuilder's section markup, so the section
     * wrapper keeps those two class names even though the layout is ours.
     */
    const outerClass = `wprf-control-section ${field?.name || ''}`;

    /*
     * A section made entirely of social platform panels becomes a logo tab
     * strip — the same shape as Social Templates — instead of ten tall cards
     * stacked down the page.
     */
    const socialPlatforms = (field?.fields || []).filter((child: any) =>
        SOCIAL_PLATFORM_TYPES.includes(child?.type)
    );

    if (
        socialPlatforms.length > 1 &&
        socialPlatforms.length === (field?.fields || []).length
    ) {
        return (
            <div className={`${outerClass} wprf-section-fields`}>
                <SocialPlatformTabs fields={socialPlatforms} depth={depth + 1} />
            </div>
        );
    }

    if (depth === 0 && childrenSelfContained) {
        return (
            <div className={`${outerClass} tw-flex tw-flex-col tw-gap-5`}>
                <div className="wprf-section-fields tw-flex tw-flex-col tw-gap-5">
                    <FieldList fields={field?.fields} depth={depth + 1} />
                </div>
            </div>
        );
    }

    if (depth === 0) {
        return (
            <Card padding="none" className={`${outerClass} tw-overflow-hidden`}>
                {hasLabel(field?.label) && (
                    <div className="tw-border-0 tw-border-b tw-border-solid tw-border-line tw-px-6 tw-py-4">
                        <h2 className="tw-m-0 tw-text-lg tw-font-semibold tw-text-ink">
                            {field.label}
                        </h2>
                        {props?.help && (
                            <p className="tw-m-0 tw-mt-1 tw-text-sm tw-text-ink-muted">
                                {props.help}
                            </p>
                        )}
                    </div>
                )}

                <div className="wprf-section-fields tw-divide-y tw-divide-solid tw-divide-line tw-px-6">
                    <FieldList fields={field?.fields} depth={depth + 1} />
                </div>
            </Card>
        );
    }

    /*
     * Plenty of sections exist only to group fields for a conditional rule and
     * carry no label. Boxing those would add a visual step that means nothing,
     * so they pass their children straight through to the parent's row stack.
     */
    if (!hasLabel(field?.label)) {
        return (
            <div className={`${outerClass} wprf-section-fields tw-divide-y tw-divide-solid tw-divide-line`}>
                <FieldList fields={field?.fields} depth={depth} />
            </div>
        );
    }

    return (
        <div className={`${outerClass} tw-py-4`}>
            <h3 className="tw-m-0 tw-mb-2 tw-text-base tw-font-semibold tw-text-ink">
                {field.label}
            </h3>
            <div className="tw-rounded-md tw-bg-canvas tw-px-4">
                <div className="wprf-section-fields tw-divide-y tw-divide-solid tw-divide-line">
                    <FieldList fields={field?.fields} depth={depth + 1} />
                </div>
            </div>
        </div>
    );
};

/**
 * Group children write into a nested object, so their change events have to be
 * rewritten to the `[group, child]` path — the same thing quickbuilder's own
 * Group field does.
 */
const GroupPanel: React.FC<{
    field: any;
    props: any;
    depth: number;
    parent?: string[];
}> = ({ field, depth, parent }) => {
    const path = parent ? [...parent, field.name] : [field.name];

    return (
        <div className="tw-py-4">
            {hasLabel(field?.label) && (
                <h3 className="tw-m-0 tw-mb-3 tw-text-base tw-font-semibold tw-text-ink">
                    {field.label}
                </h3>
            )}

            <div className="tw-divide-y tw-divide-solid tw-divide-line">
                <FieldList
                    fields={field?.fields}
                    parent={path}
                    parentType="group"
                    depth={depth + 1}
                />
            </div>
        </div>
    );
};

/**
 * Platform logo per platform key. Filenames match the `logo` paths the social
 * profile fields already point at.
 */
const PLATFORM_LOGOS: Record<string, string> = {
    facebook: 'facebook.svg',
    twitter: 'twitter.svg',
    linkedin: 'linkedin.svg',
    pinterest: 'pinterest.svg',
    instagram: 'instagram.svg',
    medium: 'medium.svg',
    threads: 'threads.svg',
    bluesky: 'bluesky.svg',
    mastodon: 'mastodon.svg',
    google_business: 'google-my-business-logo.svg',
};

/**
 * The same platform is spelled three ways across the config — `layouts_twitter`
 * for a template sub-tab, `twitter_profile_list` for a profile field, and
 * `google-business` as a field type. Normalise to one key.
 */
function platformKey(value: string): string {
    return String(value || '')
        .replace(/^layouts_/, '')
        .replace(/_profile_list$/, '')
        .replace(/-/g, '_');
}

function platformLogo(value: string): string | undefined {
    const file = PLATFORM_LOGOS[platformKey(value)];

    if (!file) {
        return undefined;
    }

    // @ts-ignore — localised by Assets.php
    return `${wpspSettingsGlobal?.assets_path}images/${file}`;
}

/** Field types that render a whole social platform panel. */
const SOCIAL_PLATFORM_TYPES = [
    'facebook',
    'twitter',
    'linkedin',
    'pinterest',
    'instagram',
    'medium',
    'threads',
    'bluesky',
    'mastodon',
    'google-business',
];

/**
 * Social Profile as a logo tab strip rather than ten stacked cards, matching
 * Social Templates. Only the selected platform is mounted.
 *
 * Two things the stacked list gave away for free have to be handled here:
 * connections are no longer all visible at once, so connected platforms carry
 * a dot; and the OAuth round trip comes back to `?action=…&type=<platform>`
 * expecting that platform's modal to be on the page, so that platform wins the
 * initial selection over anything in `?section=`.
 */
const SocialPlatformTabs: React.FC<{ fields: any[]; depth: number }> = ({
    fields,
    depth,
}) => {
    const builderContext = useBuilderContext();

    const [activeName, setActiveName] = useState(() => {
        const params = new URLSearchParams(window.location.search);
        const returning = params.get('action') === 'wpsp_social_add_social_profile'
            ? params.get('type')
            : null;

        if (returning) {
            const match = fields.find(
                (field: any) => platformKey(field?.type) === platformKey(returning)
            );

            if (match) {
                return match.name;
            }
        }

        const wanted = readRoute().section;
        const restored = fields.find((field: any) => field?.name === wanted);

        return restored ? restored.name : fields[0]?.name;
    });

    const active = fields.find((f: any) => f.name === activeName) || fields[0];

    const select = (name: string) => {
        setActiveName(name);
        writeRoute({ section: name });
    };

    useEffect(() => {
        const onPopState = () => {
            const wanted = readRoute().section;
            const match = fields.find((f: any) => f.name === wanted);

            if (match) {
                setActiveName(match.name);
            }
        };

        window.addEventListener('popstate', onPopState);

        return () => window.removeEventListener('popstate', onPopState);
    }, [fields]);

    return (
        <div className="tw-flex tw-flex-col tw-gap-5">
            <Tabs
                variant="logo"
                activeId={active?.name}
                onChange={select}
                className="tw-rounded-md tw-bg-canvas-sunken tw-p-1"
                items={fields.map((field: any) => {
                    const props = builderContext.getFieldProps(field);
                    const connected = Array.isArray(props?.value)
                        ? props.value.length
                        : 0;

                    return {
                        id: field.name,
                        ariaLabel: connected
                            ? `${field.label} (${connected})`
                            : field.label,
                        icon: (
                            <span className="tw-relative tw-flex">
                                <img
                                    src={platformLogo(field.type)}
                                    alt=""
                                    className="tw-h-6 tw-w-6 tw-object-contain"
                                />
                                {connected > 0 && (
                                    <span
                                        className="tw-absolute tw--right-1 tw--top-1 tw-h-2 tw-w-2 tw-rounded-full tw-bg-success-500 tw-ring-2 tw-ring-white"
                                        aria-hidden="true"
                                    />
                                )}
                            </span>
                        ),
                    };
                })}
            />

            <div key={active?.name} className="tw-animate-fade-in">
                <FieldList fields={active ? [active] : []} depth={depth} />
            </div>
        </div>
    );
};

/** Sub-tabs, used by the Scheduling Hub. Each child section is one tab. */
/** Copy for the locked state of a paid sub-tab, keyed by section name. */
const PRO_LOCK_COPY: Record<string, { description: string; points: string[] }> = {
    layout_manage_schedule: {
        description: __(
            'Hand your publishing calendar to SchedulePress and let it place posts for you.',
            'wp-scheduled-posts'
        ),
        points: [
            __('Spread posts automatically across the week', 'wp-scheduled-posts'),
            __('Set how many go out each day, and between which hours', 'wp-scheduled-posts'),
            __('Or pick exact days and times to publish on', 'wp-scheduled-posts'),
        ],
    },
};

const NestedTabs: React.FC<{ field: any; depth: number }> = ({ field, depth }) => {
    const builderContext = useBuilderContext();
    const sections = (field?.fields || []).filter((section: any) => hasLabel(section?.label));

    // Restore from `?section=`, but only if it names one of *these* sub-tabs —
    // the parameter may still be describing the tab we just came from.
    const [activeId, setActiveId] = useState(() => {
        const wanted = readRoute().section;
        const match = sections.find((section: any) => section.name === wanted);

        return match ? match.name : sections[0]?.name;
    });

    const active = sections.find((section: any) => section.name === activeId) || sections[0];
    /* `is_pro` resolves to true only when the feature is not licensed. */
    const activeLocked = !!(active && builderContext.getFieldProps(active)?.is_pro);

    const selectSection = (id: string) => {
        setActiveId(id);
        writeRoute({ section: id });
    };

    // Back/forward through sub-tabs of the tab we are already on.
    useEffect(() => {
        const onPopState = () => {
            const wanted = readRoute().section;
            const match = sections.find((section: any) => section.name === wanted);

            if (match) {
                setActiveId(match.name);
            }
        };

        window.addEventListener('popstate', onPopState);

        return () => window.removeEventListener('popstate', onPopState);
    }, [sections]);

    if (!sections.length) {
        return <FieldList fields={field?.fields} depth={depth} />;
    }

    // A logo strip only makes sense if every sub-tab has one.
    const asLogos = sections.every((section: any) => platformLogo(section.name));

    return (
        /*
         * The enclosing card only pads horizontally — ordinary rows bring their
         * own `py`, but a tab strip has none, so it would sit flush against the
         * card's top edge.
         */
        <div className="tw-flex tw-flex-col tw-gap-5 tw-py-5">
            <Tabs
                variant={asLogos ? 'logo' : 'pill'}
                activeId={active?.name}
                onChange={selectSection}
                items={sections.map((section: any) => {
                    const logo = asLogos ? platformLogo(section.name) : undefined;
                    const locked = !!builderContext.getFieldProps(section)?.is_pro;

                    return {
                        id: section.name,
                        // The logo replaces the name; the name still labels the
                        // control for screen readers and on hover.
                        label: logo ? undefined : section.label,
                        ariaLabel: locked
                            ? `${section.label} (${__('Pro', 'wp-scheduled-posts')})`
                            : section.label,
                        badge: locked && !logo ? <ProBadge /> : undefined,
                        icon: logo ? (
                            <img
                                src={logo}
                                alt=""
                                className="tw-h-6 tw-w-6 tw-object-contain"
                            />
                        ) : undefined,
                    };
                })}
                className="tw-rounded-md tw-bg-canvas-sunken tw-p-1"
            />

            <div className="tw-flex tw-flex-col tw-gap-5 tw-animate-fade-in">
                {activeLocked ? (
                    <ProLock
                        title={active?.label}
                        description={PRO_LOCK_COPY[active?.name]?.description}
                        points={PRO_LOCK_COPY[active?.name]?.points}
                    >
                        <FieldList fields={active?.fields} depth={depth} />
                    </ProLock>
                ) : (
                    <FieldList fields={active?.fields} depth={depth} />
                )}
            </div>
        </div>
    );
};

export default FieldList;
