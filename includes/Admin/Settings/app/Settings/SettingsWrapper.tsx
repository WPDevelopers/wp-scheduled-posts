import { __ } from '@wordpress/i18n';
import React from 'react'
import { BuilderProvider, useBuilder } from 'quickbuilder';
import "quickbuilder/dist/fields/Section";
import "quickbuilder/dist/fields/Group";
import "quickbuilder/dist/fields/Input";
import "quickbuilder/dist/fields/Input";
import "quickbuilder/dist/fields/Button";
import "quickbuilder/dist/fields/Toggle";
import "quickbuilder/dist/fields/RadioCard";

import SettingsInner from './SettingsInner';
import 'quickbuilder/dist/index.css';
import Header from './Header';
import '../assets/sass/index.scss';
import { ToastContainer } from "react-toastify";

const SettingsWrapper = ({wpspObject}) => {
    const builder = useBuilder(wpspObject.settings);

    return (
        <>
            <Header image_path={wpspObject?.image_path} />
            <BuilderProvider value={builder}>
                <SettingsInner {...wpspObject} />
                <ToastContainer />
            </BuilderProvider>
        </>
    )
}
export default SettingsWrapper;