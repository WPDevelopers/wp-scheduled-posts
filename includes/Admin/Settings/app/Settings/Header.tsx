import React from 'react';


const Header = () => {
    return (
        <div className="wpsp-admin-header">
            {/* @ts-ignore */}
            <img src={`${wpspSettingsGlobal?.image_path}mainLogo.png`} alt="mainLogo" />
        </div>
    )
}

export default Header;