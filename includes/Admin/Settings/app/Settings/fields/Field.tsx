import React from 'react'
import { __ } from '@wordpress/i18n';
import Features from './Features';
import Facebook from './Facebook';
import Linkedin from './Linkedin';
import Pinterest from './Pinterest';
import Twitter from './Twitter';
import CheckboxSelect from './CheckboxSelect';
import Time from './Time';

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
        case "checkbox-select":
            return <CheckboxSelect {...props} />;
        case "time":
            return <Time {...props} />;
        default:
            return <></>;
    }
};

export default Field;