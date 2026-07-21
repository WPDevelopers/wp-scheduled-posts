import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { components, default as ReactSelect } from 'react-select';
import { selectStyles } from '../../../helper/styles';

export type Option = { value: string; label: string };

type Props = {
    options: Option[];
    values: string[];
    placeholder?: string;
    onChange: (values: string[]) => void;
};

// Searchable option row with a checkbox (matches CheckboxSelectAsync).
const CheckOption = (props) => (
    <div className="checkbox-select-menu-list-item">
        <components.Option {...props}>
            <span>{props.label}</span>
        </components.Option>
    </div>
);

/**
 * Searchable multi-select over a static option list, rendering selected values
 * as removable chips. Shared by Post Type / Post Status filters. Mirrors the
 * app's CheckboxSelect look via `selectStyles` + `checkbox-select` classes.
 */
const ChipMultiSelect = ({ options, values, placeholder, onChange }: Props) => {
    const labelFor = useMemo(() => {
        const map: Record<string, string> = {};
        options.forEach((o) => (map[o.value] = o.label));
        return (val: string) => map[val] || val;
    }, [options]);

    const selectedOptions = values.map((v) => ({ value: v, label: labelFor(v) }));

    const remove = (val: string) => onChange(values.filter((v) => v !== val));

    return (
        <div className="wpsp-tax-select">
            {values.length > 0 && (
                <div className="wpsp-tax-chips">
                    {values.map((val) => (
                        <span className="chip" key={val}>
                            {labelFor(val)}
                            <span
                                className="x"
                                role="button"
                                aria-label={`Remove ${labelFor(val)}`}
                                onClick={() => remove(val)}>
                                ×
                            </span>
                        </span>
                    ))}
                </div>
            )}
            <span className="d-inline-block wpsp-tax-select-wrap">
                <ReactSelect
                    options={options}
                    value={selectedOptions}
                    styles={selectStyles}
                    isMulti
                    closeMenuOnSelect={false}
                    hideSelectedOptions={false}
                    controlShouldRenderValue={false}
                    components={{ Option: CheckOption }}
                    placeholder={placeholder || __('Search & select…', 'wp-scheduled-posts')}
                    className="checkbox-select"
                    classNamePrefix="checkbox-select"
                    onChange={(picked: any[]) =>
                        onChange((picked || []).map((p) => p.value))
                    }
                />
            </span>
        </div>
    );
};

export default ChipMultiSelect;
