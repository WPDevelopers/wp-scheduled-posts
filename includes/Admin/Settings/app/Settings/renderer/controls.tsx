import { __ } from '@wordpress/i18n';
import React from 'react';
import cn from '../components/ui/cn';
import { Input, SettingRow, Toggle } from '../components/ui';
import { emitChange, fieldDescription, hasLabel } from './emit';

/** Toggle laid out as a settings row: label and help on the left, switch right. */
export const ToggleField: React.FC<{ props: any }> = ({ props }) => (
    <SettingRow
        label={props?.label}
        description={fieldDescription(props)}
        isPro={!!props?.is_pro}
        control={
            <Toggle
                id={props?.name}
                checked={!!props?.value}
                disabled={!!props?.disable}
                onChange={(checked) => emitChange(props, checked, 'toggle')}
            />
        }
    />
);

/**
 * Text and number share a row. Numbers get a narrow field since they hold
 * counts and limits, never prose.
 */
export const TextField: React.FC<{ props: any }> = ({ props }) => {
    const isNumber = props?.type === 'number';

    return (
        <SettingRow
            label={props?.label}
            description={fieldDescription(props)}
            isPro={!!props?.is_pro}
            control={
                <Input
                    id={props?.name}
                    type={isNumber ? 'number' : 'text'}
                    value={props?.value ?? ''}
                    disabled={!!props?.disable}
                    min={props?.min}
                    max={props?.max}
                    /* getFieldProps defaults placeholder to the label, which
                       would just echo the row heading — only honour a real one. */
                    placeholder={props?.placeholder === props?.label ? '' : props?.placeholder}
                    wrapperClassName={isNumber ? 'tw-w-[120px]' : 'tw-w-[280px]'}
                    onChange={(e) =>
                        emitChange(props, e.target.value, isNumber ? 'number' : 'text')
                    }
                />
            }
        />
    );
};

/** Wide controls (selects, custom fields) read better stacked under the label. */
export const StackedField: React.FC<{ props: any; children: React.ReactNode }> = ({
    props,
    children,
}) => (
    <SettingRow
        layout="stacked"
        label={props?.label}
        description={fieldDescription(props)}
        isPro={!!props?.is_pro}
        control={children}
    />
);

export const RadioCardField: React.FC<{ props: any }> = ({ props }) => {
    const options = props?.options || props?.option || [];

    return (
        <div className="tw-py-4">
            {hasLabel(props?.label) && (
                <p className="tw-m-0 tw-mb-1 tw-text-base tw-font-medium tw-text-ink">
                    {props.label}
                </p>
            )}

            {fieldDescription(props) && (
                <p className="tw-m-0 tw-mb-3 tw-text-sm tw-text-ink-muted">
                    {fieldDescription(props)}
                </p>
            )}

            <div className="tw-grid tw-gap-3 tw-grid-cols-1 sm:tw-grid-cols-2 xl:tw-grid-cols-3">
                {options.map((option: any) => {
                    const isActive = props?.value == option.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            onClick={() => emitChange(props, option.value, 'radio-card')}
                            className={cn(
                                'wpsp-ui tw-flex tw-w-full tw-items-start tw-gap-3 tw-rounded-md tw-p-4',
                                'tw-cursor-pointer tw-border tw-border-solid tw-text-left',
                                'tw-transition-colors tw-duration-150',
                                isActive
                                    ? 'tw-border-brand-500 tw-bg-brand-50'
                                    : 'tw-border-line-strong tw-bg-white hover:tw-border-brand-300'
                            )}
                        >
                            <span
                                className={cn(
                                    'tw-mt-0.5 tw-flex tw-h-4 tw-w-4 tw-shrink-0 tw-items-center tw-justify-center tw-rounded-full tw-border tw-border-solid',
                                    isActive
                                        ? 'tw-border-[5px] tw-border-brand-500'
                                        : 'tw-border-line-strong'
                                )}
                            />

                            <span className="tw-min-w-0">
                                <span className="tw-block tw-text-base tw-font-medium tw-text-ink">
                                    {option.label}
                                </span>
                                {option.help && (
                                    <span className="tw-mt-0.5 tw-block tw-text-xs tw-text-ink-muted">
                                        {option.help}
                                    </span>
                                )}
                            </span>
                        </button>
                    );
                })}
            </div>
        </div>
    );
};

/** Raw markup fields — the plugin ships a few notice/banner blocks this way. */
export const HtmlField: React.FC<{ props: any }> = ({ props }) => (
    <div
        className="tw-py-4 tw-text-base tw-text-ink-muted [&_a]:tw-text-brand-500"
        dangerouslySetInnerHTML={{ __html: props?.text || props?.value || '' }}
    />
);
