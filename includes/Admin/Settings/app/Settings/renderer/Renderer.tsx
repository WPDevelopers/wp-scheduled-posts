import React, { useEffect, useState } from 'react';
import { applyFilters } from '@wordpress/hooks';
import { useBuilderContext } from 'quickbuilder';
import cn from '../components/ui/cn';
import { Card, Tabs } from '../components/ui';
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
 * Platform logos for the Social Templates sub-tabs, keyed by the sub-tab name
 * the PHP config uses. Filenames match the `logo` paths the social profile
 * fields already point at. Sub-tabs not listed here (the Scheduling Hub's)
 * keep their text label.
 */
const PLATFORM_LOGOS: Record<string, string> = {
    layouts_facebook: 'facebook.svg',
    layouts_twitter: 'twitter.svg',
    layouts_linkedin: 'linkedin.svg',
    layouts_pinterest: 'pinterest.svg',
    layouts_instagram: 'instagram.svg',
    layouts_medium: 'medium.svg',
    layouts_threads: 'threads.svg',
    layouts_bluesky: 'bluesky.svg',
    layouts_mastodon: 'mastodon.svg',
    layouts_google_business: 'google-my-business-logo.svg',
};

function platformLogo(name: string): string | undefined {
    const file = PLATFORM_LOGOS[name];

    if (!file) {
        return undefined;
    }

    // @ts-ignore — localised by Assets.php
    return `${wpspSettingsGlobal?.assets_path}images/${file}`;
}

/** Sub-tabs, used by the Scheduling Hub. Each child section is one tab. */
const NestedTabs: React.FC<{ field: any; depth: number }> = ({ field, depth }) => {
    const sections = (field?.fields || []).filter((section: any) => hasLabel(section?.label));

    // Restore from `?section=`, but only if it names one of *these* sub-tabs —
    // the parameter may still be describing the tab we just came from.
    const [activeId, setActiveId] = useState(() => {
        const wanted = readRoute().section;
        const match = sections.find((section: any) => section.name === wanted);

        return match ? match.name : sections[0]?.name;
    });

    const active = sections.find((section: any) => section.name === activeId) || sections[0];

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
        <div className="tw-flex tw-flex-col tw-gap-5">
            <Tabs
                variant={asLogos ? 'logo' : 'pill'}
                activeId={active?.name}
                onChange={selectSection}
                items={sections.map((section: any) => {
                    const logo = asLogos ? platformLogo(section.name) : undefined;

                    return {
                        id: section.name,
                        // The logo replaces the name; the name still labels the
                        // control for screen readers and on hover.
                        label: logo ? undefined : section.label,
                        ariaLabel: section.label,
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
                <FieldList fields={active?.fields} depth={depth} />
            </div>
        </div>
    );
};

export default FieldList;
