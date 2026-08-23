import React from 'react';

/** Small "?" help icon with a hover/focus tooltip (design: .help-ic[data-tip]). */
const HelpTip = ({ text }: { text: string }) => (
    <span className="help-ic" data-tip={text} tabIndex={0} aria-label={text}>
        ?
    </span>
);

export default HelpTip;
