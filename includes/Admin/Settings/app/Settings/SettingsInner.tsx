import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react'
import { FormBuilder, useBuilderContext } from 'quickbuilder';

import Content from './Content';
import Sidebar from './Sidebar';


const SettingsInner = (props) => {
    const builderContext = useBuilderContext();
    console.log(props, builderContext);

    useEffect(() => {
        // let iconLists = {};
        // iconLists['source'] = <SourceIcon />
        // iconLists['design'] = <DesignIcon />
        // iconLists['content'] = <ContentIcon />
        // iconLists['display'] = <DisplayIcon />
        // iconLists['customize'] = <CustomizeIcon />
        // builderContext.registerIcons('tabs', iconLists);

        // builderContext.registerAlert('pro_alert', proAlert);
        // builderContext.registerAlert('toast', ToastAlert);
    }, []);

    const onChange = (event) => {

    }


    return (
        <>
            <Content>
                <FormBuilder {...builderContext} onChange={onChange} />
            </Content>
            <Sidebar>
                <h1>Hello World</h1>
            </Sidebar>
        </>
    )
}

export default SettingsInner;