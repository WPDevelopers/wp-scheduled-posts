import React from 'react'
import { __ } from '@wordpress/i18n';
import Features from './Features';
import Facebook from './Facebook';
import Linkedin from './Linkedin';
import Pinterest from './Pinterest';
import Twitter from './Twitter';

const Field = (r, type, props) => {

    switch (type) {
        case "features":
            return <Features {...props} />;
        case "facebook":
            return <Facebook {...props} />;
        case "linkedin":
            return <Linkedin {...props} />;
        case "pinterest":
            return <Pinterest {...props} />;
        case "twitter":
            return <Twitter {...props} />;
        default:
            return <></>;
    }
};

export default Field;