import React from 'react'
import { __ } from '@wordpress/i18n';
import Features from './Features';
import SocialProfile from './SocialProfile';

const Field = (r, type, props) => {

    switch (type) {
        case "features":
            return <Features {...props} />;
        case "social_profile":
            return <SocialProfile {...props} />;
        default:
            return <></>;
    }
};

export default Field;