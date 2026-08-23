import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import React, { useEffect, useMemo, useState } from 'react';
import { components, default as ReactSelect } from 'react-select';
import { fetchCategories } from '../../../helper/helper';
import { selectStyles } from '../../../helper/styles';

type TermOption = {
    value: string; // `postType.taxonomy.slug`
    label: string;
    taxonomy: string;
};

type Props = {
    values: string[];
    onChange: (values: string[]) => void;
};

// Searchable option row with a checkbox (matches CheckboxSelectAsync).
const Option = (props) => (
    <div className="checkbox-select-menu-list-item">
        <components.Option {...props}>
            <span>{props.label}</span>
        </components.Option>
    </div>
);

/**
 * Taxonomy term filter (feature #4). Searchable multi-select of category / tag /
 * custom-taxonomy terms fetched from the `get-categories` REST endpoint. Selected
 * terms show as removable chips; stores term `value` strings (`postType.taxonomy.slug`).
 */
const TaxonomyFilter = ({ values, onChange }: Props) => {
    const [options, setOptions] = useState<TermOption[]>([]);
    const [loading, setLoading] = useState(false);
    const [page, setPage] = useState(1);

    const fetchPage = (p: number) => {
        setLoading(true);
        fetchCategories({ limit: 20, page: p })
            .then((res: any[]) => {
                const mapped = (res || []).map((t) => ({
                    value: t.value,
                    label: `${t.label} (${t.taxonomy})`,
                    taxonomy: t.taxonomy,
                }));
                setOptions((prev) => {
                    const seen = new Set(prev.map((o) => o.value));
                    return [...prev, ...mapped.filter((o) => !seen.has(o.value))];
                });
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        fetchPage(1);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const labelFor = useMemo(() => {
        const map: Record<string, string> = {};
        options.forEach((o) => (map[o.value] = o.label));
        return (val: string) => map[val] || val.split('.').pop() || val;
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
            <span
                className={classNames('d-inline-block wpsp-tax-select-wrap', {
                    'wpsp-checkbox-async-loading': loading,
                })}>
                <ReactSelect
                    options={options}
                    value={selectedOptions}
                    styles={selectStyles}
                    isMulti
                    isLoading={loading}
                    closeMenuOnSelect={false}
                    hideSelectedOptions={false}
                    controlShouldRenderValue={false}
                    components={{ Option }}
                    placeholder={__('Search & select terms…', 'wp-scheduled-posts')}
                    className="checkbox-select"
                    classNamePrefix="checkbox-select"
                    onChange={(picked: any[]) =>
                        onChange((picked || []).map((p) => p.value))
                    }
                    onMenuScrollToBottom={() => {
                        if (loading) return;
                        const next = page + 1;
                        setPage(next);
                        fetchPage(next);
                    }}
                />
            </span>
        </div>
    );
};

export default TaxonomyFilter;
