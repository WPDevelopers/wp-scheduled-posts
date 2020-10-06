import React from 'react'
import Setting from './Settings'
import Topbar from '../components/Topbar'
const Admin = ({ wpspObject }) => {
    return (
        <div className='wrap'>
            <Topbar
                pluginRootUri={wpspObject.plugin_root_uri}
                freeVersion={wpspObject.free_version}
                proVersion={wpspObject.pro_version}
            />
            <Setting wpspObject={wpspObject} />
        </div>
    )
}
export default Admin
