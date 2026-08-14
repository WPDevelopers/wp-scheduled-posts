/**
 * react-select style objects, expressed in the UI kit's tokens.
 *
 * These cannot be Tailwind classes: react-select renders its own DOM and takes
 * styling through this API, so the token values are repeated here as literals.
 * Keep them in step with `tailwind.config.js`.
 */
const TOKENS = {
  brand: '#6c62ff',
  brandTint: '#f5f4ff',
  ink: '#232c3b',
  inkMuted: '#5b6779',
  placeholder: '#aab3c0',
  line: '#eaeef4',
  lineStrong: '#dbe2ec',
  canvas: '#f7f8fb',
};

/** Standalone select — the time picker, the account-type picker, filters. */
export const selectStyles = {
  control: (base, state) => ({
    ...base,
    minHeight: 40,
    minWidth: 110,
    borderRadius: 10,
    backgroundColor: '#fff',
    borderColor: state.isFocused ? TOKENS.brand : TOKENS.lineStrong,
    boxShadow: state.isFocused ? '0 0 0 3px rgba(108, 98, 255, 0.18)' : 'none',
    color: TOKENS.ink,
    transition: 'border-color .15s, box-shadow .15s',
    '&:hover': {
      borderColor: state.isFocused ? TOKENS.brand : '#c7d0dd',
    },
  }),
  valueContainer: (base) => ({ ...base, padding: '2px 10px' }),
  placeholder: (base) => ({ ...base, color: TOKENS.placeholder, fontSize: 14 }),
  singleValue: (base) => ({ ...base, color: TOKENS.ink, fontSize: 14 }),
  input: (base) => ({ ...base, color: TOKENS.ink, fontSize: 14, margin: 0 }),
  /*
   * Chips for a multi-select that renders its values in the control. Selects
   * that keep their chips outside it pass `controlShouldRenderValue={false}`,
   * so these are simply unused there.
   */
  multiValue: (base) => ({
    ...base,
    backgroundColor: TOKENS.brandTint,
    borderRadius: 6,
    margin: '2px 4px 2px 0',
  }),
  multiValueLabel: (base) => ({
    ...base,
    color: TOKENS.brand,
    fontSize: 13,
    fontWeight: 500,
    padding: '3px 2px 3px 8px',
  }),
  multiValueRemove: (base) => ({
    ...base,
    color: TOKENS.brand,
    borderRadius: '0 6px 6px 0',
    paddingLeft: 4,
    paddingRight: 6,
    '&:hover': { backgroundColor: '#ebe9ff', color: TOKENS.brand },
  }),
  indicatorSeparator: () => ({ display: 'none' }),
  dropdownIndicator: (base, state) => ({
    ...base,
    padding: '0 8px',
    color: state.isFocused ? TOKENS.brand : TOKENS.placeholder,
    '&:hover': { color: TOKENS.inkMuted },
  }),
  clearIndicator: (base) => ({ ...base, display: 'none' }),
  menu: (base) => ({
    ...base,
    zIndex: 9999,
    borderRadius: 12,
    border: `1px solid ${TOKENS.line}`,
    boxShadow:
      '0 4px 8px -2px rgba(20, 26, 36, .06), 0 16px 48px -8px rgba(20, 26, 36, .16)',
    overflow: 'hidden',
  }),
  menuList: (base) => ({ ...base, padding: 6 }),
  option: (base, { isFocused, isSelected }) => ({
    ...base,
    borderRadius: 8,
    padding: '8px 10px',
    fontSize: 14,
    cursor: 'pointer',
    color: isSelected ? TOKENS.brand : TOKENS.ink,
    fontWeight: isSelected ? 500 : 400,
    backgroundColor: isSelected
      ? TOKENS.brandTint
      : isFocused
      ? TOKENS.canvas
      : 'transparent',
    '&:active': { backgroundColor: TOKENS.brandTint },
  }),
  noOptionsMessage: (base) => ({ ...base, fontSize: 14, color: TOKENS.inkMuted }),
};

/**
 * Multi-select that lives *inside* our own bordered shell — see
 * `fields/CheckboxSelect.tsx`. The control itself is stripped bare so the shell
 * is the only visible box, and the chips sit alongside it.
 */
export const multiSelectStyles = {
  ...selectStyles,
  // The control is only as wide as the space left beside the chips, so the
  // menu is pinned to a readable width of its own rather than inheriting it.
  menu: (base) => ({
    ...selectStyles.menu(base),
    width: 'auto',
    minWidth: 260,
  }),
  control: (base) => ({
    ...base,
    minHeight: 30,
    minWidth: 120,
    border: 0,
    borderRadius: 0,
    backgroundColor: 'transparent',
    boxShadow: 'none',
    cursor: 'pointer',
    '&:hover': { border: 0 },
  }),
  valueContainer: (base) => ({ ...base, padding: 0 }),
  placeholder: (base) => ({
    ...base,
    color: TOKENS.placeholder,
    fontSize: 14,
    margin: 0,
  }),
  dropdownIndicator: (base) => ({
    ...base,
    padding: '0 2px',
    color: TOKENS.placeholder,
    '&:hover': { color: TOKENS.inkMuted },
  }),
  option: (base, { isFocused }) => ({
    ...base,
    borderRadius: 8,
    padding: 0,
    fontSize: 14,
    cursor: 'pointer',
    color: TOKENS.ink,
    // The row is drawn by the custom Option component, which needs the
    // selected/hover treatment on its own element to fit the checkbox in.
    backgroundColor: isFocused ? TOKENS.canvas : 'transparent',
    '&:active': { backgroundColor: TOKENS.brandTint },
  }),
};
