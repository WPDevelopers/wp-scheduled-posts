import React from 'react'
import { __ } from '@wordpress/i18n'
import Instruction from './../components/Instruction'
const Document = ({ pluginRootURI }) => {
    const assetsURI = pluginRootURI + 'assets/images/'
    const instructionData = [
        {
            icon: assetsURI + 'documentation.png',
            title: __('Documentation', 'wp-scheduled-posts'),
            desc: __(
                'Get started spending some time with the documentation to get familiar with WP Scheduled Posts. Build awesome websites for you or your clients with ease.',
                'wp-scheduled-posts'
            ),
            button: {
                text: __('Documentation', 'wp-scheduled-posts'),
                url:
                    'https://wpdeveloper.net/docs/wp-scheduled-posts/?utm_medium=admin&utm_source=wp.org&utm_term=wpsp',
            },
        },
        {
            icon: assetsURI + 'contribute.png',
            title: __('Contribute to WP Scheduled Posts', 'wp-scheduled-posts'),
            desc: __(
                'You can contribute to make WP Scheduled Posts better reporting bugs, creating issues, pull requests at Github.',
                'wp-scheduled-posts'
            ),
            button: {
                text: __('Report A Bug', 'wp-scheduled-posts'),
                url:
                    'https://github.com/WPDevelopers/wp-scheduled-posts/issues/new',
            },
        },
        {
            icon: assetsURI + 'chat.png',
            title: __('Need Help?', 'wp-scheduled-posts'),
            desc: __(
                'Stuck with something? Get help from the community WPDeveloper Forum or Facebook Community. In case of emergency, initiate live chat at WP Scheduled Posts website.',
                'wp-scheduled-posts'
            ),
            button: {
                text: __('Get Support', 'wp-scheduled-posts'),
                url: 'https://wpdeveloper.net/support/',
            },
        },
        {
            icon: assetsURI + 'love.png',
            title: __('Show your Love', 'wp-scheduled-posts'),
            desc: __(
                'We love to have you in WP Scheduled Posts family. We are making it more awesome everyday.',
                'wp-scheduled-posts'
            ),
            button: {
                text: __('Leave a Review', 'wp-scheduled-posts'),
                url:
                    'https://wordpress.org/support/plugin/wp-scheduled-posts/reviews/?rate=5#new-post',
            },
        },
    ]
    return (
        <div className='instruction_wrapper'>
            {instructionData.map((item, index) => (
                <Instruction key={index} {...item} />
            ))}
        </div>
    )
}

export default Document
