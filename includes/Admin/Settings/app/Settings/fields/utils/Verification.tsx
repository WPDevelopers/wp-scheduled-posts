import React from 'react'
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Input } from '../../components/ui';

const linkClass = 'tw-cursor-pointer tw-text-brand-500 hover:tw-text-brand-700';

const Verification = ({ email, submitOTP, resendOTP, isRequestSending, isSendingResendRequest }) => {
	const [otp, setOTP] = useState('');

	return (
		<div className="wpsp-verification-msg tw-flex tw-flex-col tw-gap-4">
			<p className="tw-text-base tw-text-ink-muted tw-m-0">
				{__('License Verification code has been sent to this ', 'wp-scheduled-posts')}
				<span className="tw-font-medium tw-text-ink">{email}</span>
				{__('. Please check your email for the code & insert it below 👇', 'wp-scheduled-posts')}
			</p>

			<div className="wpsp-verification-input-container">
				<div className="wpsp-verification-input tw-flex tw-flex-wrap tw-items-start tw-gap-3">
					<Input
						inputSize="lg"
						wrapperClassName="tw-flex-1 tw-min-w-[240px]"
						value={otp}
						onChange={(e) => setOTP(e.target.value)}
						placeholder={__('Enter Your Verification Code', 'wp-scheduled-posts')}
					/>

					<Button
						size="lg"
						disabled={otp.length === 0}
						loading={isRequestSending}
						onClick={() => submitOTP(otp)}
					>
						{isRequestSending
							? __('Verifying...', 'wp-scheduled-posts')
							: __('Verify', 'wp-scheduled-posts')}
					</Button>
				</div>
			</div>

			<p className="tw-text-sm tw-text-ink-muted tw-m-0">
				{__('Haven’t received an email? Retry clicking on ')}{' '}
				<a onClick={resendOTP} className={linkClass}>
					{isSendingResendRequest
						? __('Resending...', 'wp-scheduled-posts')
						: __('Resend', 'wp-scheduled-posts')}
				</a>
				{__(' button. Please note that this verification code will expire after 15 minutes. Facing any issues ? Follow this ', 'wp-scheduled-posts')}
				<a
					href="https://wpdeveloper.com/docs/activate-wp-scheduled-posts-license/"
					target="_blank"
					rel="noopener noreferrer"
					className={linkClass}
				>
					{__('Guide', 'wp-scheduled-posts')}
				</a>
				{__(' or Contact ', 'wp-scheduled-posts')}
				<a
					href="https://wpdeveloper.com/support"
					target="_blank"
					rel="noopener noreferrer"
					className={linkClass}
				>
					{__(' Support', 'wp-scheduled-posts')}
				</a>
			</p>
		</div>
	);
};

export default Verification;
