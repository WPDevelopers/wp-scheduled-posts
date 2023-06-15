import React, { useEffect, useState } from "react";
import { __ } from "@wordpress/i18n";
const ProfileData = ({ platform,profileData }) => {
    console.log('from profile data component',profileData?.page);
    
  return (
    <React.Fragment>
        <div className="modalbody">
            <ul>
                {profileData?.page?.map((item,index) => (
                    <li>
                        {item.name}
                    </li>
                ))}
            </ul>
        </div>
    </React.Fragment>
  );
};
export default ProfileData;
