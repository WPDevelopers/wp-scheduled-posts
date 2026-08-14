/**
 * Tailwind configuration for the SchedulePress admin Settings app.
 *
 * Two guard rails keep Tailwind from fighting with wp-admin:
 *  - `prefix: 'tw-'` so utilities can never collide with core/plugin classes
 *    (`.hidden`, `.block`, `.container`, `.button` all exist in wp-admin).
 *  - `preflight: false` because Tailwind's reset is global and would wipe out
 *    the surrounding admin chrome. A scoped reset lives in
 *    `app/assets/css/tailwind.css` instead.
 *
 * `important: true` is needed because wp-admin ships very opinionated element
 * selectors (`#wpbody-content a`, `input[type="text"]`, `.wrap h1`, …) that
 * otherwise out-specify a single utility class.
 */
module.exports = {
    prefix: 'tw-',
    important: true,
    corePlugins: {
        preflight: false,
    },
    content: [
        './app/**/*.{js,jsx,ts,tsx}',
        './app/**/*.scss',
    ],
    theme: {
        extend: {
            colors: {
                /* Brand purple — buttons, links, active states. */
                brand: {
                    50: '#f3f2ff',
                    100: '#e9e7ff',
                    200: '#d1ceff',
                    300: '#b3adff',
                    400: '#8e86ff',
                    500: '#6c62ff',
                    600: '#5a4ff0',
                    700: '#4a3fd6',
                    800: '#3d34ad',
                    900: '#332c8a',
                },
                /* Deep navy used for every heading in the current design. */
                ink: {
                    DEFAULT: '#1b1b50',
                    muted: '#6e6e8d',
                    subtle: '#989fab',
                    placeholder: '#a6a6ba',
                },
                line: {
                    DEFAULT: '#ebeef5',
                    strong: '#e1e5e9',
                },
                canvas: {
                    DEFAULT: '#f9fafc',
                    raised: '#ffffff',
                },
                success: {
                    50: '#e8f9f2',
                    500: '#02ac6e',
                    600: '#019560',
                },
                warning: {
                    50: '#fcf4ed',
                    100: '#ffefe1',
                    200: '#ffd6b2',
                    500: '#ff9437',
                    600: '#e8821e',
                    900: '#73400d',
                },
                danger: {
                    50: '#fef6f6',
                    500: '#dc3545',
                    600: '#c82333',
                },
            },
            fontFamily: {
                sans: [
                    'Inter',
                    '-apple-system',
                    'BlinkMacSystemFont',
                    'Segoe UI',
                    'Roboto',
                    'Helvetica Neue',
                    'sans-serif',
                ],
            },
            fontSize: {
                /* The admin uses a slightly tighter scale than Tailwind's default. */
                xxs: ['11px', '16px'],
                xs: ['12px', '16px'],
                sm: ['13px', '20px'],
                base: ['14px', '22px'],
                lg: ['16px', '24px'],
                xl: ['18px', '22px'],
                '2xl': ['22px', '28px'],
                '3xl': ['28px', '36px'],
            },
            borderRadius: {
                DEFAULT: '6px',
                md: '6px',
                lg: '8px',
                xl: '12px',
            },
            boxShadow: {
                card: '0 1px 2px 0 rgba(27, 27, 80, 0.04)',
                raised: '0 4px 16px 0 rgba(27, 27, 80, 0.08)',
                popover: '0 8px 32px 0 rgba(27, 27, 80, 0.12)',
                focus: '0 0 0 3px rgba(108, 98, 255, 0.2)',
            },
            keyframes: {
                'wpsp-fade-in': {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                'wpsp-scale-in': {
                    from: { opacity: '0', transform: 'scale(0.97)' },
                    to: { opacity: '1', transform: 'scale(1)' },
                },
                'wpsp-shimmer': {
                    '100%': { transform: 'translateX(100%)' },
                },
            },
            animation: {
                'fade-in': 'wpsp-fade-in 0.15s ease-out',
                'scale-in': 'wpsp-scale-in 0.15s ease-out',
                shimmer: 'wpsp-shimmer 1.4s infinite',
            },
        },
    },
    plugins: [],
};
