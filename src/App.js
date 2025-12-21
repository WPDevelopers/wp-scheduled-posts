import React from 'react';
import Header from './components/common/Header.js';
import Content from './components/common/Content.js';
import Footer from './components/common/Footer.js';
import './scss/styles.scss';
const App = () => {
    return (
        <div id='wpsp-post-panel' className='wpsp-post-panel'>
            <Header />
            <Content />
            <Footer />
        </div>
    );
};

export default App;
