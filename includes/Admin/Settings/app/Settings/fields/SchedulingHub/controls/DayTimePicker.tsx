import React, { useState } from 'react';

// Manual Scheduler day/time picker (design: .dt-picker). Phase-1 scaffold —
// local state only, no persistence.
const DT_DAYS = [
    { value: 'sat', label: 'Sat' },
    { value: 'sun', label: 'Sun' },
    { value: 'mon', label: 'Mon' },
    { value: 'tue', label: 'Tue' },
    { value: 'wed', label: 'Wed' },
    { value: 'thu', label: 'Thu' },
    { value: 'fri', label: 'Fri' },
];

const DT_TIMES = Array.from({ length: 24 }, (_, h) => {
    const hh = String(h).padStart(2, '0');
    const label =
        h === 0
            ? '12:00 AM'
            : h < 12
            ? `${h}:00 AM`
            : h === 12
            ? '12:00 PM'
            : `${h - 12}:00 PM`;
    return { value: `${hh}:00`, label };
});

type Slots = Record<string, string[]>; // day.value -> array of time labels

const DayTimePicker = () => {
    const [openPop, setOpenPop] = useState<'days' | 'times' | null>(null);
    const [selDays, setSelDays] = useState<string[]>([]);
    const [selTimes, setSelTimes] = useState<string[]>([]);
    const [slots, setSlots] = useState<Slots>({});

    const toggle = (list: string[], val: string) =>
        list.includes(val) ? list.filter((v) => v !== val) : [...list, val];

    const add = () => {
        if (!selDays.length || !selTimes.length) return;
        setSlots((prev) => {
            const next: Slots = { ...prev };
            selDays.forEach((day) => {
                const existing = new Set(next[day] || []);
                selTimes.forEach((t) => {
                    const label = DT_TIMES.find((x) => x.value === t)?.label || t;
                    existing.add(label);
                });
                next[day] = Array.from(existing);
            });
            return next;
        });
        setSelDays([]);
        setSelTimes([]);
        setOpenPop(null);
    };

    const removeSlot = (day: string, label: string) =>
        setSlots((prev) => ({
            ...prev,
            [day]: (prev[day] || []).filter((t) => t !== label),
        }));

    const label = (kind: 'days' | 'times') => {
        const sel = kind === 'days' ? selDays : selTimes;
        const placeholder = kind === 'days' ? 'Select Days' : 'Select Times';
        if (!sel.length) return placeholder;
        if (sel.length === 1) {
            if (kind === 'times') {
                return DT_TIMES.find((x) => x.value === sel[0])?.label || sel[0];
            }
            return DT_DAYS.find((x) => x.value === sel[0])?.label || sel[0];
        }
        return `${sel.length} ${kind} selected`;
    };

    return (
        <div className="dt-picker">
            <div className="dt-controls">
                {(['days', 'times'] as const).map((kind) => {
                    const options = kind === 'days' ? DT_DAYS : DT_TIMES;
                    const sel = kind === 'days' ? selDays : selTimes;
                    const setSel = kind === 'days' ? setSelDays : setSelTimes;
                    return (
                        <div
                            className={`dt-select ${openPop === kind ? 'is-open' : ''}`}
                            key={kind}>
                            <button
                                className="dt-select-btn"
                                type="button"
                                onClick={() =>
                                    setOpenPop(openPop === kind ? null : kind)
                                }>
                                <span>{label(kind)}</span>
                                <svg
                                    className="chev"
                                    width="14"
                                    height="14"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeWidth="2"
                                    strokeLinecap="round"
                                    strokeLinejoin="round">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </button>
                            <div className="dt-popover">
                                {options.map((opt) => (
                                    <div
                                        key={opt.value}
                                        className={`dt-pop-item ${
                                            sel.includes(opt.value) ? 'is-checked' : ''
                                        }`}
                                        onClick={() =>
                                            setSel(toggle(sel, opt.value))
                                        }>
                                        <span className="dt-pop-check">✓</span>
                                        {opt.label}
                                    </div>
                                ))}
                            </div>
                        </div>
                    );
                })}
                <button className="btn btn-primary" type="button" onClick={add}>
                    + Add
                </button>
            </div>

            <div className="dt-grid">
                {DT_DAYS.map((d) => (
                    <div className="dt-col" key={d.value}>
                        <div className="dt-day">{d.label.toUpperCase()}</div>
                        <div className="dt-chips">
                            {(slots[d.value] || []).map((t) => (
                                <div className="dt-chip" key={t}>
                                    <span>{t}</span>
                                    <span
                                        className="x"
                                        role="button"
                                        aria-label="Remove"
                                        onClick={() => removeSlot(d.value, t)}>
                                        ×
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default DayTimePicker;
