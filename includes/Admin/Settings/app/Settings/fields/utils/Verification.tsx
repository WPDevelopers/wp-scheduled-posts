import React from 'react'
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const Verification = ({ email, submitOTP, resendOTP, isRequestSending, isSendingResendRequest }) => {
	const [otp, setOTP] = useState('');
	return (
		<div className="btl-verification-msg">
			<p>
				{__('License Verification code has been sent to this ', 'wp-scheduled-posts')} <span>{email}</span>
				{__('. Please check your email for the code & insert it below 👇', 'wp-scheduled-posts')}
			</p>
			<div className="btl-verification-input-container">
				<div className="btl-verification-input">
					<input type="text" value={otp} onChange={(e) => setOTP(e.target.value)} placeholder={__('Enter Your Verification Code', 'wp-scheduled-posts')} />
					<button type="button" disabled={otp.length === 0} className={otp.length === 0 ? 'disabled' : ''} onClick={() => submitOTP(otp)}>
						{isRequestSending ? __('Verifying...', 'wp-scheduled-posts') : __('Verify', 'wp-scheduled-posts')}
					</button>
				</div>
				<p>
					{__('Haven’t received an email? Please hit this ')}{' '}
					<a onClick={resendOTP} style={{ fontWeight: 'bold', cursor: 'pointer' }}>
						{isSendingResendRequest ? __('Resending...', 'wp-scheduled-posts') : __('Resend', 'wp-scheduled-posts')}
					</a>
					{__(' button to retry. Please note that this verification code will expire after 15 minutes.')}
				</p>
			</div>
			<div className="btl-verification-msg">
                <div className="short-description">
                    <b style={{ fontWeight: 700 }}>{__('Note', 'wp-scheduled-posts')}: </b> {__('Check out this ', 'wp-scheduled-posts')}{' '}
                    <a href="https://wpdeveloper.com/docs/activate-wp-scheduled-posts-license/" target="_blank">
                        {__('guide', 'wp-scheduled-posts')}
                    </a>{' '}
                    {__(' to verify your license key. If you need any assistance with retrieving your License Verification Key, please ', 'wp-scheduled-posts')}{' '}
                    <a href="https://wpdeveloper.com/support/" target="_blank">
                        {__('contact support', 'wp-scheduled-posts')}
                    </a>
                    .
                </div>
            </div>
		</div>
	);
};

export default Verification;
