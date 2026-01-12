import React from 'react';
const ManageSchedule = () => {

    return (
        <div className="wpsp-post-panel-modal-settings-schedule">
            <div className="wpsp-post--card">
                <div className="card--title">
                    <h4 className="title">Manage Schedule</h4>
                    <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="9" cy="9.5" r="9" fill="#FFEEE0"></circle>
                        <path d="M4.06398 11.3651C3.83586 9.88232 3.60775 8.39961 3.37964 6.91686C3.32905 6.58817 3.70304 6.364 3.96906 6.56353C4.67976 7.09656 5.39043 7.62955 6.10113 8.16257C6.33513 8.33807 6.6685 8.28096 6.83073 8.03758L8.60571 5.37508C8.79329 5.09372 9.20669 5.09372 9.39426 5.37508L11.1693 8.03758C11.3315 8.28096 11.6649 8.33803 11.8989 8.16257C12.6096 7.62955 13.3202 7.09656 14.0309 6.56353C14.2969 6.364 14.6709 6.58817 14.6204 6.91686C14.3923 8.39961 14.1642 9.88232 13.936 11.3651H4.06398Z" fill="#FFA454"></path>
                        <path d="M13.4218 13.8328H4.57914C4.29489 13.8328 4.06445 13.6024 4.06445 13.3181V12.1875H13.9365V13.3181C13.9365 13.6024 13.706 13.8328 13.4218 13.8328Z" fill="#FFA454"></path>
                    </svg>
                </div>
                <div className="wpsp-post-items--wrapper">
                    <div className="wpsp-post--items">
                        <div className="card--title">
                            <h5 className="title">Auto Schedule</h5>
                            <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="9" cy="9.5" r="9" fill="#FFEEE0"></circle>
                                <path d="M4.06398 11.3651C3.83586 9.88232 3.60775 8.39961 3.37964 6.91686C3.32905 6.58817 3.70304 6.364 3.96906 6.56353C4.67976 7.09656 5.39043 7.62955 6.10113 8.16257C6.33513 8.33807 6.6685 8.28096 6.83073 8.03758L8.60571 5.37508C8.79329 5.09372 9.20669 5.09372 9.39426 5.37508L11.1693 8.03758C11.3315 8.28096 11.6649 8.33803 11.8989 8.16257C12.6096 7.62955 13.3202 7.09656 14.0309 6.56353C14.2969 6.364 14.6709 6.58817 14.6204 6.91686C14.3923 8.39961 14.1642 9.88232 13.936 11.3651H4.06398Z" fill="#FFA454"></path>
                                <path d="M13.4218 13.8328H4.57914C4.29489 13.8328 4.06445 13.6024 4.06445 13.3181V12.1875H13.9365V13.3181C13.9365 13.6024 13.706 13.8328 13.4218 13.8328Z" fill="#FFA454"></path>
                            </svg>
                        </div>
                        <div className="wpsp-date--picker">
                            <form action="/action_page.php">
                                <input type="datetime-local" id="birthdaytime" name="birthdaytime" />
                            </form>
                        </div>
                    </div>
                    <div className="wpsp-post--items">
                        <div className="card--title">
                            <h5 className="title">Manual Schedule</h5>
                            <svg width="18" height="19" viewBox="0 0 18 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="9" cy="9.5" r="9" fill="#FFEEE0"></circle>
                                <path d="M4.06398 11.3651C3.83586 9.88232 3.60775 8.39961 3.37964 6.91686C3.32905 6.58817 3.70304 6.364 3.96906 6.56353C4.67976 7.09656 5.39043 7.62955 6.10113 8.16257C6.33513 8.33807 6.6685 8.28096 6.83073 8.03758L8.60571 5.37508C8.79329 5.09372 9.20669 5.09372 9.39426 5.37508L11.1693 8.03758C11.3315 8.28096 11.6649 8.33803 11.8989 8.16257C12.6096 7.62955 13.3202 7.09656 14.0309 6.56353C14.2969 6.364 14.6709 6.58817 14.6204 6.91686C14.3923 8.39961 14.1642 9.88232 13.936 11.3651H4.06398Z" fill="#FFA454"></path>
                                <path d="M13.4218 13.8328H4.57914C4.29489 13.8328 4.06445 13.6024 4.06445 13.3181V12.1875H13.9365V13.3181C13.9365 13.6024 13.706 13.8328 13.4218 13.8328Z" fill="#FFA454"></path>
                            </svg>
                        </div>
                        <div className="wpsp-select--option">
                            <select id="cars">
                                <option value="volvo">June 14, 2023 at 2:50 PM</option>
                                <option value="saab">June 14, 2023 at 2:50 PM</option>
                                <option value="opel">June 14, 2023 at 2:50 PM</option>
                                <option value="audi">June 14, 2023 at 2:50 PM</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ManageSchedule;
