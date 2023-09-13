const { createElement } = wp.element
const { createSlotFill } = wp.components;

const { Fill, Slot } = createSlotFill( 'WpspProSlot' );

const WpspProSlot = ( { children } ) => {
    return <Fill>{ children }</Fill>
}

WpspProSlot.Slot = Slot;

export default WpspProSlot;