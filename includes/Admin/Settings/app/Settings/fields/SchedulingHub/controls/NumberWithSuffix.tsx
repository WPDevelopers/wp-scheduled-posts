import React from 'react';

type Props = {
    value: number;
    suffix: string;
    min?: number;
    max?: number;
    onChange: (value: number) => void;
};

/** Number input with a right-aligned unit suffix (design: .input-row + .suffix). */
const NumberWithSuffix = ({ value, suffix, min = 0, max, onChange }: Props) => (
    <div className="input-row">
        <input
            type="number"
            min={min}
            max={max}
            value={value}
            onChange={(e) => onChange(Number(e.target.value))}
        />
        <span className="suffix">{suffix}</span>
    </div>
);

export default NumberWithSuffix;
