import React from 'react'
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const Verification = ({ email, submitOTP, resendOTP, isRequestSending }) => {
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
						{__('"Resend"', 'wp-scheduled-posts')}
					</a>
					{__(' button to retry. Please note that this verification code will expire after 15 minutes.')}
				</p>
			</div>
		</div>
	);
};

export default Verification;
