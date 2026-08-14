import React from 'react'
import { BuilderProvider, useBuilder } from 'quickbuilder';
import { ToastContainer } from "react-toastify";

import SettingsApp from './shell/SettingsApp';
import '../assets/sass/index.scss';
// Loaded after the legacy sass so utilities win while the screens migrate over.
import '../assets/css/tailwind.css';

/**
 * quickbuilder is used headlessly: `useBuilder` gives us the value store,
 * change handlers and conditional-rule evaluation, while `SettingsApp` renders
 * the whole UI. Its `FormBuilder`, field components and stylesheet are no
 * longer involved.
 */
const SettingsWrapper = ({ wpspObject }) => {
    const builder = useBuilder(wpspObject.settings);

    return (
        <BuilderProvider value={builder}>
            <SettingsApp wpspObject={wpspObject} />
            <ToastContainer />
        </BuilderProvider>
    )
}

export default SettingsWrapper;
