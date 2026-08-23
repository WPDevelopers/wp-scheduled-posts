import React, { useState } from 'react';

type Props = {
    values: string[];
    placeholder?: string;
    onChange: (values: string[]) => void;
};

/** Chip-style multi-value input (design: .multi + .chip). Enter adds, × removes. */
const MultiChip = ({ values, placeholder, onChange }: Props) => {
    const [draft, setDraft] = useState('');

    const add = () => {
        const val = draft.trim();
        if (!val || values.includes(val)) {
            setDraft('');
            return;
        }
        onChange([...values, val]);
        setDraft('');
    };

    const remove = (val: string) => onChange(values.filter((v) => v !== val));

    return (
        <div className="multi">
            {values.map((val) => (
                <span className="chip" key={val}>
                    {val}
                    <span
                        className="x"
                        role="button"
                        aria-label={`Remove ${val}`}
                        onClick={() => remove(val)}>
                        ×
                    </span>
                </span>
            ))}
            <input
                value={draft}
                placeholder={placeholder}
                onChange={(e) => setDraft(e.target.value)}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        add();
                    }
                }}
            />
        </div>
    );
};

export default MultiChip;
