import React from 'react'
import { __ } from '@wordpress/i18n';
import Features from './Features';
import SocialProfile from './SocialProfile';
import Facebook from './Facebook';
import Linkedin from './Linkedin';

const Field = (r, type, props) => {

    switch (type) {
        case "features":
            return <Features {...props} />;
        // case "social_profile":
        //     return <SocialProfile {...props} />;
        case "facebook":
            return <Facebook {...props} />;
        // case "linkedin":
        //     return <Linkedin {...props} />;
        default:
            return <></>;
    }
};

export default Field;