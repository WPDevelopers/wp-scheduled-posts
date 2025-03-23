import React from 'react'
import { __ } from '@wordpress/i18n';
import Features from './Features';
import Facebook from './Facebook';
import Linkedin from './Linkedin';
import Pinterest from './Pinterest';
import Instagram from './Instagram';
import Twitter from './Twitter';
import CheckboxSelect from './CheckboxSelect';
import Time from './Time';
import Calendar from './Calendar';
import List from './List';
import Video from './Video';
import Html from './Html';
import AutoScheduler from './AutoScheduler';
import ManualScheduler from './ManualScheduler';
import ScheduleHubFeature from './ScheduleHubFeature';
import License from './License';
import ProToggle from './utils/ProToggle';
import Medium from './Medium';
import CheckboxSelectAsync from './CheckboxSelectAsync';
import Threads from './Threads';

const Field = (ret, type, props) => {

    switch (type) {
        case "features":
            return <Features {...props} />;
        case "schedule-hub-features":
            return <ScheduleHubFeature {...props} />;
        case "pro-toggle":
            return <ProToggle {...props} />
        case "auto-scheduler":
            return <AutoScheduler {...props} />;
        case "manual-scheduler":
            return <ManualScheduler {...props} />;
        case "list":
            return <List {...props} />;
        case "html":
            return <Html {...props} />;
        case "video":
            return <Video {...props} />;
        case "facebook":
            return <Facebook {...props} />;
        case "linkedin":
            return <Linkedin {...props} />;
        case "instagram":
            return <Instagram {...props} />;
        case "pinterest":
            return <Pinterest {...props} />;
        case "twitter":
            return <Twitter {...props} />;
        case "medium":
            return <Medium {...props} />;
        case "threads":
            return <Threads {...props} />;
        case "checkbox-select":
            return <CheckboxSelect {...props} />;
        case "checkbox-select-async":
            return <CheckboxSelectAsync {...props} />;
        case "time":
            return <Time {...props} />;
        case "calendar":
            return <Calendar {...props} />;
        case "license":
            return <License {...props} />;
        default:
            return ret;
    }
};

export default Field;