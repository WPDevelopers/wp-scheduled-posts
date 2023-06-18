import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
const Facebook = ({profileData }) => {
    console.log('from profile data component',profileData?.page);
    const [isErrorMessage, setIsErrorMessage] = useState(false)
    const addProfileToggle = (item, index, e) => {
    }
  return (
    <React.Fragment>
        <div className="modalhead">
            <h3>This is modal for facebook header</h3>
        </div>
        <div className="modalbody">
            <ul className="prfile-list">
                {profileData?.page?.map((item,index) => (
                        <li id={'facebook_page_' + index} key={index}>
                        <div className='item-content'>
                            <div className='entry-thumbnail'>
                                <img
                                    src={item.thumbnail_url}
                                    alt='logo'
                                />
                            </div>
                            <h4 className='entry-title'>
                                {item.name}
                            </h4>
                            <div className='control'>
                                <input
                                    type='checkbox'
                                    name={`test_name`}
                                    onChange={(e) =>
                                        addProfileToggle(
                                            item,
                                            index,
                                            e
                                        )
                                    }
                                />
                                <div></div>
                            </div>
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    </React.Fragment>
  );
};
export default Facebook;
