import React from 'react';
const mainLogo =  require("../assets/images/mainLogo.png");


const Header = () => {
    return (
        <div className="nx-admin-header">
            <img src={mainLogo} alt='mainLogo' />
            {/* <img src={require('../assets/images/mainLogo.png')} alt="mainLogo" /> */}
        </div>
    )
}

export default Header;