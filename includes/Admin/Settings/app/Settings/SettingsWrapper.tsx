import { __ } from '@wordpress/i18n';
import React from 'react'
import { BuilderProvider, useBuilder } from 'quickbuilder';
import SettingsInner from './SettingsInner';
import 'quickbuilder/dist/index.css';
import '../sass/index.scss';

const SettingsWrapper = (props) => {
    console.log(props);
    const builder = useBuilder(props.wpspObject.settings);

    return (
        <BuilderProvider value={builder}>
            <SettingsInner props={props} />
        </BuilderProvider>
    )
}
export default SettingsWrapper;