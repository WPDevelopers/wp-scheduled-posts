import React from 'react'
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const Verification = ({ email, submitOTP, resendOTP, isRequestSending, isSendingResendRequest }) => {
	const [otp, setOTP] = useState('');
	return (
		<div className="wpsp-verification-msg">
			<p>
				{__('License Verification code has been sent to this ', 'wp-scheduled-posts')} <span>{email}</span>
				{__('. Please check your email for the code & insert it below 👇', 'wp-scheduled-posts')}
			</p>
			<div className="wpsp-verification-input-container">
				<div className="wpsp-verification-input">
					<input type="text" value={otp} onChange={(e) => setOTP(e.target.value)} placeholder={__('Enter Your Verification Code', 'wp-scheduled-posts')} />
					<button type="button" disabled={otp.length === 0} className={otp.length === 0 ? 'disabled' : ''} onClick={() => submitOTP(otp)}>
						{isRequestSending ? __('Verifying...', 'wp-scheduled-posts') : __('Verify', 'wp-scheduled-posts')}
					</button>
				</div>
			</div>
			<div className="wpsp-verification-msg">
                <div className="short-description">
					<p>
						{__('Haven’t received an email? Retry clicking on ')}{' '}
						<a onClick={resendOTP} style={{  cursor: 'pointer', margin: 0 }}>
							{isSendingResendRequest ? __('Resending...', 'wp-scheduled-posts') : __('Resend', 'wp-scheduled-posts')}
						</a>
						{ __(' button. Please note that this verification code will expire after 15 minutes. Facing any issues ? Follow this ','wp-scheduled-posts') }
						<a style={{ cursor: 'pointer', margin: 0  }} href="https://wpdeveloper.com/docs/activate-wp-scheduled-posts-license/" target='_blank'>{ __('Guide','wp-scheduled-posts') }</a> { __(' or Contact ','wp-scheduled-posts') } <a style={{ cursor: 'pointer', margin: 0  }} href='https://wpdeveloper.com/support' target='_blank'>{ __(' Support','wp-scheduled-posts') }</a>
					</p>
                </div>
            </div>
		</div>
	);
};

export default Verification;
