/**
 * quickbuilder's `handleChange` runs the value through `executeChange`, which
 * reads `event.target.{type,name,value,checked}` off a DOM event. Our controls
 * are not native inputs, so they hand it a synthetic target of the same shape.
 *
 * Note the per-type contract in `executeChange`:
 *   number/range → `parseFloat(value)`
 *   checkbox     → `checked`
 *   toggle/other → `value` verbatim
 * so a toggle must pass the boolean as `value`, not as `checked`.
 */
export function emitChange(props: any, value: any, typeOverride?: string) {
    const type = typeOverride || props?.type || 'text';

    props?.onChange?.(
        {
            target: {
                type,
                name: props?.name,
                value,
                checked: type === 'checkbox' ? !!value : undefined,
            },
        },
        // Lets quickbuilder block the write and fire the upsell on pro fields.
        { isPro: !!props?.is_pro, popup: props?.popup }
    );
}

/** Description text lives under one of three keys depending on the field. */
export function fieldDescription(props: any): string | undefined {
    return props?.help || props?.desc || props?.sub_title || undefined;
}

/** `label` is legitimately `false` or `null` on unlabelled wrapper sections. */
export function hasLabel(label: any): label is string {
    return typeof label === 'string' && label.trim().length > 0;
}
