import React, { useContext } from 'react';
import Header from './components/common/Header.js';
import Content from './components/common/Content.js';
import Footer from './components/common/Footer.js';
import Modals from './components/modals/Modals.js';
import './scss/styles.scss';
import { AppContext } from './context/AppContext.js';
const App = () => {
      const { state, dispatch } = useContext(AppContext);
    const { isOpenCustomSocialMessageModal } = state;
    return (
        <div className='wpsp-post-panel-wrapper' id='wpsp-post-panel-wrapper'>
            <div id='wpsp-post-panel' hidden={isOpenCustomSocialMessageModal} className='wpsp-post-panel'>
                <Header />
                <Content />
                <Footer />
            </div>
            <Modals/>
        </div>
    );
};

export default App;
