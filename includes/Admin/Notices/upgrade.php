</p>
<div class="schedulepress-plugin-update-message">
    <style>
        .schedulepress-plugin-update-message p:before { content: ''; margin-right: 0; display: none; }
        .schedulepress-plugin-update-message ul {
            list-style: disc;
            padding-left: 15px;
        }

        .schedulepress-plugin-update-message p:first-of-type,
        .schedulepress-plugin-update-message p.schedulepress-major-update-title {
            font-weight: 700;
            margin-top: 25px;
            margin-bottom: 10px;
        }

        .schedulepress-plugin-update-message p.schedulepress-major-update-title {
            border-bottom: 1px solid #ffb900;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .updated-message .schedulepress-plugin-update-message, .updating-message .schedulepress-plugin-update-message {
            display: none;
        }
    </style>

    <?php
        if ( $major ) { // || $minor
            printf(
                '<p class="schedulepress-major-update-title">%s</p>',
                __( 'Heads up, Please backup before upgrade!', 'wp-scheduled-posts' )
            );
        }

        /**
         * @var object $response;
         */
        echo isset( $response->upgrade_notice ) ? $response->upgrade_notice : '';
    ?>
</div>
<p style="display: none">
