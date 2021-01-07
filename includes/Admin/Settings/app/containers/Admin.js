import React from 'react'
import Setting from './Settings'
import Topbar from '../components/Topbar'
const Admin = ({ wpspObject }) => {
    return (
        <React.Fragment>
            <Topbar
                pluginRootUri={wpspObject.plugin_root_uri}
                freeVersion={wpspObject.free_version}
                proVersion={wpspObject.pro_version}
            />
            <Setting wpspObject={wpspObject} />
        </React.Fragment>
    )
}
export default Admin
