import React from 'react'
import { __ } from '@wordpress/i18n';
import Features from './Features';

const Field = (r, type, props) => {

    switch (type) {
        case "features":
            return <Features {...props} />;
        default:
            return <></>;
    }
};

export default Field;