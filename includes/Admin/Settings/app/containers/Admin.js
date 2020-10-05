import React from 'react'
import Setting from './Settings'
const Admin = ({ wpObject }) => {
    return (
        <div className='wrap'>
            <Setting wpObject={wpObject} />
        </div>
    )
}
export default Admin
