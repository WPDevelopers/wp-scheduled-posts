import { __ } from '@wordpress/i18n';
import React from 'react'
import { BuilderProvider, useBuilder } from 'quickbuilder';
import SettingsInner from './SettingsInner';
import 'quickbuilder/dist/index.css';
import Header from './Header';
import '../assets/sass/index.scss';

const SettingsWrapper = ({wpspObject}) => {
    const builder = useBuilder(wpspObject.settings);

    return (
        <>
            <Header />
            <BuilderProvider value={builder}>
                <SettingsInner {...wpspObject} />
            </BuilderProvider>
        </>
    )
}
export default SettingsWrapper;