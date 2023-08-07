import React from 'react';


const Header = ({image_path}) => {
    return (
        <div className="wpsp-admin-header">
            {/* @ts-ignore */}
            <img src={`${image_path}mainLogo.png`} alt="mainLogo" />
        </div>
    )
}

export default Header;