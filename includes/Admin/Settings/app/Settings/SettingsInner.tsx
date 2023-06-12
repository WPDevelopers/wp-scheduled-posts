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
        <div className='nx-admin-wrapper'>
            <Content>
                <FormBuilder {...builderContext} onChange={onChange} />
            </Content>
            <Sidebar>
                <div className='card'>
                    <h3>Documentation</h3>
                    <p>Get started spending some time with the documentation to get familiar with SchedulePress.</p>
                    <button>Documentation</button>
                </div>
            </Sidebar>
        </div>
    )
}

export default SettingsInner;