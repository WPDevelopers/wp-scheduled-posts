import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
const Linkedin = ({profileData }) => {
    console.log('from profile data component',profileData?.page);
    const [isErrorMessage, setIsErrorMessage] = useState(false)
    const addProfileToggle = (item, index, e) => {
    }
  return (
    <React.Fragment>
        <div className="modalhead">
            <h3>This is modal for linkedin header</h3>
        </div>
        <div className="modalbody">
            <ul className="prfile-list">
                {profileData?.linkedin?.pages?.map((item,index) => (
                        <li id={'linkedin_page_' + index} key={index}>
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
                            </div>
                        </div>
                    </li>
                ))}
            </ul>
        </div>
    </React.Fragment>
  );
};
export default Linkedin;
