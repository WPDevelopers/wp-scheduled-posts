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
                /*
                 * Brand violet. The 500 is the one value carried over from the
                 * old design — it is the product's colour — but it now sits on
                 * cool neutrals rather than the previous navy/lavender pairing.
                 */
                brand: {
                    50: '#f5f4ff',
                    100: '#ebe9ff',
                    200: '#d9d5ff',
                    300: '#bdb4ff',
                    400: '#968bff',
                    500: '#6c62ff',
                    600: '#5a4ce8',
                    700: '#4a3dc4',
                    800: '#3d339e',
                    900: '#342d7d',
                },
                /* Cool grey ramp; replaces the old navy-tinted text colours. */
                ink: {
                    DEFAULT: '#232c3b',
                    strong: '#141a24',
                    muted: '#5b6779',
                    subtle: '#8b97a8',
                    placeholder: '#aab3c0',
                },
                line: {
                    DEFAULT: '#eaeef4',
                    strong: '#dbe2ec',
                },
                canvas: {
                    DEFAULT: '#f7f8fb',
                    sunken: '#f1f3f8',
                    raised: '#ffffff',
                },
                success: {
                    50: '#ecfdf5',
                    100: '#d1fae5',
                    500: '#10b981',
                    600: '#059669',
                },
                warning: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    500: '#f59e0b',
                    600: '#d97706',
                    900: '#78350f',
                },
                danger: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    500: '#ef4444',
                    600: '#dc2626',
                },
                info: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#3b82f6',
                    600: '#2563eb',
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
            /* Noticeably rounder than the old flat 6px everywhere. */
            borderRadius: {
                DEFAULT: '8px',
                sm: '6px',
                md: '10px',
                lg: '14px',
                xl: '18px',
                '2xl': '24px',
            },
            /* Layered and soft, rather than a single hard 1px edge. */
            boxShadow: {
                card: '0 1px 2px 0 rgba(20, 26, 36, 0.04), 0 1px 3px 0 rgba(20, 26, 36, 0.03)',
                raised: '0 2px 4px -1px rgba(20, 26, 36, 0.04), 0 8px 20px -4px rgba(20, 26, 36, 0.08)',
                popover: '0 4px 8px -2px rgba(20, 26, 36, 0.06), 0 16px 48px -8px rgba(20, 26, 36, 0.16)',
                focus: '0 0 0 3px rgba(108, 98, 255, 0.18)',
                'focus-danger': '0 0 0 3px rgba(239, 68, 68, 0.16)',
                nav: 'inset 3px 0 0 0 #6c62ff',
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
                'wpsp-slide-up': {
                    from: { opacity: '0', transform: 'translateY(6px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-in': 'wpsp-fade-in 0.15s ease-out',
                'scale-in': 'wpsp-scale-in 0.15s ease-out',
                'slide-up': 'wpsp-slide-up 0.2s ease-out',
                shimmer: 'wpsp-shimmer 1.4s infinite',
            },
        },
    },
    plugins: [],
};
