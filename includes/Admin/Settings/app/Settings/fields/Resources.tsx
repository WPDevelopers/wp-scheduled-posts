import React from 'react';
import { __ } from '@wordpress/i18n';
import { cn } from '../components/ui';

/**
 * The "learn how this works" block for a Scheduling Hub feature: the
 * walkthrough video and the documentation links, kept together in one panel of
 * their own.
 *
 * These two used to be loose fields sitting beside the feature switch — the
 * video in a second grid column, the links stacked under the toggle — so the
 * switch read as the heading of a marketing block rather than as a setting.
 * Here the help material is one clearly separate section below the setting.
 *
 * Config shape (see `Admin/Settings.php`), mirroring the older `video` and
 * `list` field types it replaces:
 *
 *     'type'        => 'resources',
 *     'label'       => 'Learn how it works',
 *     'description' => '…',
 *     'url'         => 'https://www.youtube.com/embed/…',
 *     'video_label' => 'Video walkthrough',
 *     'docs_label'  => 'Documentation',
 *     'content'     => [ [ 'link' => …, 'text' => … ], … ],
 */
const Resources = (props) => {
    const docs = (props?.content || []).filter((item) => item?.link && item?.text);
    const video = props?.url;

    if (!video && !docs.length) {
        return null;
    }

    return (
        <div
            className={cn(
                'wprf-control',
                'wprf-resources',
                `wprf-${props.name}-resources`,
                props?.classes
            )}
        >
            <div className="tw-rounded-md tw-border tw-border-solid tw-border-line tw-bg-canvas-sunken tw-p-5">
                {(props?.label || props?.description) && (
                    <div className="tw-mb-4">
                        {props?.label && (
                            <h4 className="tw-m-0 tw-text-base tw-font-semibold tw-text-ink">
                                {props.label}
                            </h4>
                        )}

                        {props?.description && (
                            <p className="tw-m-0 tw-mt-1 tw-text-sm tw-text-ink-muted">
                                {props.description}
                            </p>
                        )}
                    </div>
                )}

                {/* One column each when both exist; whichever is present takes
                    the full width on its own. Splitting earlier than `xl`
                    leaves the video too small — the nav rail and the page
                    padding already take most of a 1024px viewport. */}
                <div
                    className={cn(
                        'tw-grid tw-items-start tw-gap-5',
                        video && docs.length > 0 && 'xl:tw-grid-cols-2'
                    )}
                >
                    {video && (
                        <section>
                            <SubHeading
                                icon={<PlayIcon />}
                                text={
                                    props?.video_label ||
                                    __('Video walkthrough', 'wp-scheduled-posts')
                                }
                            />

                            {/* 16:9 from aspect-ratio rather than the fixed pixel
                                size the config carries, which overflowed narrow
                                layouts. Capped when it has the row to itself —
                                a full-width embed would dwarf the panel. */}
                            <div className="tw-aspect-video tw-w-full tw-max-w-[560px] tw-overflow-hidden tw-rounded-sm tw-border tw-border-solid tw-border-line tw-bg-white xl:tw-max-w-none">
                                <iframe
                                    src={video}
                                    title={
                                        props?.video_label ||
                                        __('Video walkthrough', 'wp-scheduled-posts')
                                    }
                                    loading="lazy"
                                    allowFullScreen
                                    className="tw-h-full tw-w-full tw-border-0"
                                />
                            </div>
                        </section>
                    )}

                    {docs.length > 0 && (
                        <section>
                            <SubHeading
                                icon={<BookIcon />}
                                text={
                                    props?.docs_label ||
                                    __('Documentation', 'wp-scheduled-posts')
                                }
                            />

                            <ul className="wprf-resources-docs tw-m-0 tw-flex tw-list-none tw-flex-col tw-gap-2 tw-p-0">
                                {docs.map((item, index) => (
                                    <li key={item?.link || index} className="tw-m-0">
                                        <a
                                            href={item.link}
                                            /* Every one of these leaves the admin,
                                               so none of them should replace it. */
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className={cn(
                                                'tw-flex tw-items-center tw-gap-3 tw-rounded-sm tw-border tw-border-solid',
                                                'tw-border-line tw-bg-white tw-px-3.5 tw-py-3 tw-text-sm tw-font-medium',
                                                'tw-leading-5 tw-text-ink tw-no-underline tw-shadow-none tw-transition-colors',
                                                'hover:tw-border-brand-300 hover:tw-text-brand-600',
                                                'focus:tw-text-brand-600 focus:tw-shadow-focus focus:tw-outline-none'
                                            )}
                                        >
                                            <span className="tw-flex-1">{item.text}</span>
                                            <ArrowIcon />
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                </div>
            </div>
        </div>
    );
};

/** Label above the video / the link list. */
const SubHeading: React.FC<{ icon: React.ReactNode; text: string }> = ({ icon, text }) => (
    <h5 className="tw-m-0 tw-mb-2.5 tw-flex tw-items-center tw-gap-2 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wide tw-text-ink-subtle">
        <span className="tw-flex tw-text-ink-subtle">{icon}</span>
        {text}
    </h5>
);

const PlayIcon = () => (
    <svg className="tw-h-4 tw-w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <circle cx="8" cy="8" r="6.25" stroke="currentColor" strokeWidth="1.4" />
        <path d="M6.75 5.75 10.5 8l-3.75 2.25V5.75Z" fill="currentColor" />
    </svg>
);

const BookIcon = () => (
    <svg className="tw-h-4 tw-w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <path
            d="M2.5 3.25h3.75A1.75 1.75 0 0 1 8 5v8a1.5 1.5 0 0 0-1.5-1.5H2.5v-8ZM13.5 3.25H9.75A1.75 1.75 0 0 0 8 5v8a1.5 1.5 0 0 1 1.5-1.5h4v-8Z"
            stroke="currentColor"
            strokeWidth="1.3"
            strokeLinejoin="round"
        />
    </svg>
);

const ArrowIcon = () => (
    <svg
        className="tw-h-3.5 tw-w-3.5 tw-shrink-0 tw-opacity-60"
        viewBox="0 0 16 16"
        fill="none"
        aria-hidden="true"
    >
        <path
            d="M5.5 10.5 10.5 5.5M10.5 5.5H6.25M10.5 5.5v4.25"
            stroke="currentColor"
            strokeWidth="1.6"
            strokeLinecap="round"
            strokeLinejoin="round"
        />
    </svg>
);

export default Resources;
