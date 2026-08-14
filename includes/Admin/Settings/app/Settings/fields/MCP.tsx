import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import React, { useCallback, useEffect, useState } from 'react';
import '../../assets/sass/utils/_mcp.scss';

/**
 * The AI (MCP) settings panel.
 *
 * Everything on this screen is driven by the plugin's own MCP REST routes
 * rather than the settings form, because the connection is live state (a token
 * exists or it doesn't) rather than a value you edit and save. The enable
 * toggle writes through /mcp/settings, which also mints the connection token,
 * so turning MCP on is the only step before a client can connect.
 */

const NS = 'wp-scheduled-posts/v1';

/* ------------------------------------------------------------------ icons */

const Svg = ({ size = 16, children, ...props }) => (
    <svg
        width={size}
        height={size}
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
        {...props}
    >
        {children}
    </svg>
);

const IconCopy = (props) => (
    <Svg {...props}>
        <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
    </Svg>
);

const IconCheck = (props) => (
    <Svg strokeWidth="2.5" {...props}>
        <polyline points="20 6 9 17 4 12" />
    </Svg>
);

const IconCross = (props) => (
    <Svg strokeWidth="2.5" {...props}>
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
    </Svg>
);

const IconChevron = ({ open, ...props }) => (
    <Svg
        strokeWidth="2.5"
        style={{
            transition: 'transform 150ms ease',
            transform: open ? 'rotate(180deg)' : 'none',
        }}
        {...props}
    >
        <polyline points="6 9 12 15 18 9" />
    </Svg>
);

const IconExternal = (props) => (
    <Svg {...props}>
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
        <polyline points="15 3 21 3 21 9" />
        <line x1="10" y1="14" x2="21" y2="3" />
    </Svg>
);

// The Model Context Protocol mark — nested arcs, drawn in the same line style
// as the rest of the panel.
const IconMcp = (props) => (
    <svg
        width="24"
        height="24"
        viewBox="0 0 195 195"
        fill="none"
        stroke="currentColor"
        strokeWidth="12"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden="true"
        {...props}
    >
        <path d="M25 97.85 92.88 29.97c9.37-9.37 24.57-9.37 33.94 0 9.38 9.37 9.38 24.57 0 33.94L75.56 115.18" />
        <path d="M76.27 114.47l50.55-50.56c9.37-9.37 24.57-9.37 33.95 0 9.37 9.38 9.37 24.58 0 33.95l-61.78 61.78c-2.34 2.34-2.34 6.13 0 8.47l19.34 19.35" />
        <path d="M109.85 46.94 59.29 97.5c-9.37 9.37-9.37 24.57 0 33.94 9.38 9.38 24.58 9.38 33.95 0l51.25-51.26" />
    </svg>
);

const IconClaude = (props) => (
    <Svg {...props}>
        <path d="M12 12L20.5 12M12 12L19.15 7.4M12 12L15.53 4.26M12 12L10.79 3.58M12 12L6.44 5.57M12 12L3.85 9.59M12 12L3.85 14.41M12 12L6.44 18.43M12 12L10.79 20.42M12 12L15.53 19.74M12 12L19.15 16.6" />
    </Svg>
);

const IconChatGPT = ({ size = 16, ...props }) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" {...props}>
        <path d="M22.2819 9.8211a5.9847 5.9847 0 0 0-.5157-4.9108 6.0462 6.0462 0 0 0-6.5098-2.9A6.0651 6.0651 0 0 0 4.9807 4.1818a5.9847 5.9847 0 0 0-3.9977 2.9 6.0462 6.0462 0 0 0 .7427 7.0966 5.98 5.98 0 0 0 .511 4.9107 6.051 6.051 0 0 0 6.5146 2.9001A5.9847 5.9847 0 0 0 13.2599 24a6.0557 6.0557 0 0 0 5.7718-4.2058 5.9894 5.9894 0 0 0 3.9977-2.9001 6.0557 6.0557 0 0 0-.7475-7.0729zm-9.022 12.6081a4.4755 4.4755 0 0 1-2.8764-1.0408l.1419-.0804 4.7783-2.7582a.7948.7948 0 0 0 .3927-.6813v-6.7369l2.02 1.1686a.071.071 0 0 1 .038.052v5.5826a4.504 4.504 0 0 1-4.4945 4.4944zm-9.6607-4.1254a4.4708 4.4708 0 0 1-.5346-3.0137l.1419.0852 4.783 2.7582a.7712.7712 0 0 0 .7806 0l5.8428-3.3685v2.3324a.0804.0804 0 0 1-.0332.0615L9.74 19.9502a4.4992 4.4992 0 0 1-6.1408-1.6464zM2.3408 7.8956a4.485 4.485 0 0 1 2.3655-1.9728V11.6a.7664.7664 0 0 0 .3879.6765l5.8144 3.3543-2.0201 1.1685a.0757.0757 0 0 1-.071 0l-4.8303-2.7865A4.504 4.504 0 0 1 2.3408 7.872zm16.5963 3.8558L13.1038 8.364 15.1192 7.2a.0757.0757 0 0 1 .071 0l4.8303 2.7913a4.4944 4.4944 0 0 1-.6765 8.1042v-5.6772a.79.79 0 0 0-.407-.667zm2.0107-3.0231l-.142-.0852-4.7735-2.7818a.7759.7759 0 0 0-.7854 0L9.409 9.2297V6.8974a.0662.0662 0 0 1 .0284-.0615l4.8303-2.7866a4.4992 4.4992 0 0 1 6.6802 4.66zM8.3065 12.863l-2.02-1.1638a.0804.0804 0 0 1-.038-.0567V6.0742a4.4992 4.4992 0 0 1 7.3757-3.4537l-.142.0805L8.704 5.459a.7948.7948 0 0 0-.3927.6813zm1.0976-2.3654l2.602-1.4998 2.6069 1.4998v2.9994l-2.5974 1.4997-2.6067-1.4997z" />
    </svg>
);

const IconTerminal = (props) => (
    <Svg {...props}>
        <polyline points="4 17 10 11 4 5" />
        <line x1="12" y1="19" x2="20" y2="19" />
    </Svg>
);

const IconCursor = (props) => (
    <Svg {...props}>
        <path d="M4 3l16 7-6.6 2.4L11 20 4 3z" />
    </Svg>
);

const IconCode = (props) => (
    <Svg {...props}>
        <polyline points="16 18 22 12 16 6" />
        <polyline points="8 6 2 12 8 18" />
    </Svg>
);

/* ---------------------------------------------------------------- helpers */

/**
 * Copy text to the clipboard.
 *
 * Prefers the async Clipboard API, which only exists in a secure context, and
 * falls back to a hidden textarea so the button still works on a plain-HTTP
 * admin (common on local and staging sites). Resolves false when every path
 * failed, so the caller can tell the user instead of silently doing nothing.
 */
const copyToClipboard = async (text: string): Promise<boolean> => {
    // @ts-ignore
    if (window.navigator?.clipboard?.writeText) {
        try {
            await window.navigator.clipboard.writeText(text);
            return true;
        } catch (e) {
            // Present but blocked (permissions/focus) — fall through.
        }
    }
    const el = document.createElement('textarea');
    el.value = text;
    el.style.cssText = 'position:fixed;left:-999999px;top:-999999px';
    document.body.appendChild(el);
    el.focus();
    el.select();
    let ok = false;
    try {
        ok = document.execCommand('copy');
    } catch (e) {
        ok = false;
    }
    document.body.removeChild(el);
    return ok;
};

// UTF-8 safe base64 — btoa alone chokes on multi-byte characters, which a site
// title or path can easily contain.
const toBase64 = (str: string): string => {
    try {
        return window.btoa(
            encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, (_, p1) =>
                String.fromCharCode(parseInt(p1, 16))
            )
        );
    } catch (e) {
        return '';
    }
};

/* ------------------------------------------------------------- sub-blocks */

const CopyField = ({ label, value, id, copiedId, onCopy }) => (
    <div className="wpsp-mcp-copyfield">
        {label && <span className="wpsp-mcp-copyfield__label">{label}</span>}
        <div className="wpsp-mcp-copyfield__row">
            <code className="wpsp-mcp-copyfield__value">{value}</code>
            <button
                type="button"
                className={`wpsp-mcp-copybtn${copiedId === id ? ' is-copied' : ''}`}
                onClick={() => onCopy(value, id)}
                aria-label={__('Copy to clipboard', 'wp-scheduled-posts')}
            >
                {copiedId === id ? <IconCheck /> : <IconCopy />}
                <span>
                    {copiedId === id
                        ? __('Copied', 'wp-scheduled-posts')
                        : __('Copy', 'wp-scheduled-posts')}
                </span>
            </button>
        </div>
    </div>
);

const ClientCard = ({ client, copiedId, onCopy }) => {
    const { Icon, accent } = client;
    return (
        <div className="wpsp-mcp-client">
            <div className="wpsp-mcp-client__head">
                <span className="wpsp-mcp-client__icon" style={{ color: accent }}>
                    <Icon size={18} />
                </span>
                <h4>{client.name}</h4>
                {client.badge && <span className="wpsp-mcp-pill">{client.badge}</span>}
            </div>

            {client.action === 'copy' && client.payload && (
                <CopyField
                    label={client.fieldLabel}
                    value={client.payload}
                    id={client.id}
                    copiedId={copiedId}
                    onCopy={onCopy}
                />
            )}

            {client.action === 'link' && (
                <a className="wpsp-mcp-btn wpsp-mcp-btn--ghost" href={client.href}>
                    <IconExternal />
                    <span>{client.linkLabel}</span>
                </a>
            )}

            <ol className="wpsp-mcp-steps">
                {client.steps.map((step, i) => (
                    <li key={i}>{step}</li>
                ))}
            </ol>
        </div>
    );
};

/* ============================================================== component */

const MCP = (props) => {
    const [status, setStatus] = useState<any>(null);
    const [apps, setApps] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');
    const [copiedId, setCopiedId] = useState<string | null>(null);
    const [tab, setTab] = useState('oauth');
    const [showCaps, setShowCaps] = useState(false);
    const [confirmReset, setConfirmReset] = useState(false);
    const [testing, setTesting] = useState(false);
    const [testResult, setTestResult] = useState<any>(null);

    const enabled = !!status?.enable_mcp;
    const connected = !!status?.connected;

    const load = useCallback(async () => {
        try {
            const response: any = await apiFetch({ path: `${NS}/mcp/connection` });
            setStatus(response);
            setError('');
        } catch (err) {
            setError(__('Couldn’t load the MCP connection status.', 'wp-scheduled-posts'));
        } finally {
            setLoading(false);
        }
    }, []);

    const loadApps = useCallback(async () => {
        try {
            const response: any = await apiFetch({ path: `${NS}/mcp/apps` });
            setApps(response?.oauth_apps || []);
        } catch (err) {
            // Non-fatal: the connected-apps list is informational.
        }
    }, []);

    useEffect(() => {
        load();
    }, [load]);

    useEffect(() => {
        if (enabled) {
            loadApps();
        }
    }, [enabled, loadApps]);

    const saveSetting = async (data: Record<string, boolean>) => {
        setSaving(true);
        setError('');
        try {
            const response: any = await apiFetch({
                path: `${NS}/mcp/settings`,
                method: 'POST',
                data,
            });
            setStatus(response);
        } catch (err) {
            setError(__('Couldn’t save the MCP setting.', 'wp-scheduled-posts'));
        } finally {
            setSaving(false);
        }
    };

    const toggleEnabled = (next: boolean) => saveSetting({ enable_mcp: next });
    const toggleSocialPublish = (next: boolean) =>
        saveSetting({ enable_mcp_social_publish: next });

    const copyWith = async (text: string, id: string) => {
        const ok = await copyToClipboard(text);
        if (!ok) {
            setError(
                __(
                    'Couldn’t copy to the clipboard. Select the text and copy it manually.',
                    'wp-scheduled-posts'
                )
            );
            return;
        }
        setError('');
        setCopiedId(id);
        setTimeout(() => setCopiedId((cur) => (cur === id ? null : cur)), 2000);
    };

    // Rotating cuts off every header-based client at once (Claude Code, Cursor,
    // VS Code) without disturbing OAuth-connected apps — the leaked-token fix.
    const rotateToken = async () => {
        setSaving(true);
        try {
            const response: any = await apiFetch({
                path: `${NS}/mcp/rotate`,
                method: 'POST',
            });
            setStatus({ ...status, ...response });
            setConfirmReset(false);
        } catch (err) {
            setError(__('Couldn’t reset the connection token.', 'wp-scheduled-posts'));
        } finally {
            setSaving(false);
        }
    };

    const runSelfTest = async () => {
        setTesting(true);
        setTestResult(null);
        try {
            const response: any = await apiFetch({
                path: `${NS}/mcp/self-test`,
                method: 'POST',
            });
            setTestResult(response);
        } catch (err) {
            setError(__('Couldn’t run the connection test.', 'wp-scheduled-posts'));
        } finally {
            setTesting(false);
        }
    };

    const revokeApp = async (clientId: string) => {
        try {
            const response: any = await apiFetch({
                path: `${NS}/mcp/apps/revoke`,
                method: 'POST',
                data: { client_id: clientId },
            });
            setApps(response?.oauth_apps || []);
        } catch (err) {
            setError(__('Couldn’t revoke that app.', 'wp-scheduled-posts'));
        }
    };

    if (loading) {
        return <div className="wpsp-mcp wpsp-mcp--loading">{__('Loading…', 'wp-scheduled-posts')}</div>;
    }

    const endpoint = status?.mcp_endpoint || '';
    const fallback = status?.mcp_endpoint_rest || '';
    const token = status?.connection_token || '';
    const cliCommand = status?.config?.cli || '';
    const toolsCount = status?.tools_count ?? 0;

    // Shared HTTP server entry for the deep-link editors.
    const serverEntry = { url: endpoint, headers: { Authorization: `Bearer ${token}` } };
    const cursorDeepLink = `cursor://anysphere.cursor-deeplink/mcp/install?name=schedulepress&config=${encodeURIComponent(
        toBase64(JSON.stringify(serverEntry))
    )}`;
    const vscodeDeepLink = `vscode:mcp/install?${encodeURIComponent(
        JSON.stringify({ name: 'schedulepress', type: 'http', ...serverEntry })
    )}`;

    const capabilities = [
        __('Read the editorial calendar and the scheduled queue', 'wp-scheduled-posts'),
        __('Find missed schedules and empty slots in the calendar', 'wp-scheduled-posts'),
        __('Schedule, reschedule and bulk-move posts', 'wp-scheduled-posts'),
        __('Unschedule a post or publish it immediately', 'wp-scheduled-posts'),
        __('Review connected social accounts and expiring tokens', 'wp-scheduled-posts'),
        __('Read and edit per-post social share captions', 'wp-scheduled-posts'),
        __('Check what has already been shared, and share a post', 'wp-scheduled-posts'),
        __('Read and change SchedulePress settings', 'wp-scheduled-posts'),
        __('Diagnose scheduling health (cron, timezone, tokens)', 'wp-scheduled-posts'),
    ];

    const oauthClients = [
        {
            id: 'claude',
            name: __('Claude (web, mobile & desktop)', 'wp-scheduled-posts'),
            Icon: IconClaude,
            accent: '#d97757',
            steps: [
                __('In Claude, open Settings → Connectors → Add custom connector.', 'wp-scheduled-posts'),
                __('Paste the connection URL above and name it SchedulePress.', 'wp-scheduled-posts'),
                __('Click Add, then approve access when Claude asks you to sign in.', 'wp-scheduled-posts'),
            ],
        },
        {
            id: 'chatgpt',
            name: 'ChatGPT',
            Icon: IconChatGPT,
            accent: '#000000',
            steps: [
                __('In ChatGPT, open Settings → Connectors → Add.', 'wp-scheduled-posts'),
                __('Enter a name (e.g. SchedulePress).', 'wp-scheduled-posts'),
                __('Paste the connection URL above.', 'wp-scheduled-posts'),
                __('Save, then approve access when ChatGPT asks you to sign in.', 'wp-scheduled-posts'),
            ],
        },
    ];

    const cliClients = [
        {
            id: 'claude-code',
            name: 'Claude Code',
            Icon: IconTerminal,
            accent: '#d97757',
            badge: __('CLI', 'wp-scheduled-posts'),
            action: 'copy',
            fieldLabel: __('Command', 'wp-scheduled-posts'),
            payload: cliCommand,
            steps: [
                __('Copy the command above.', 'wp-scheduled-posts'),
                __('Run it in your terminal from your project directory.', 'wp-scheduled-posts'),
            ],
        },
        {
            id: 'cursor',
            name: 'Cursor',
            Icon: IconCursor,
            accent: '#0ea5e9',
            action: 'link',
            href: cursorDeepLink,
            linkLabel: __('Add to Cursor', 'wp-scheduled-posts'),
            steps: [
                __('Click the button above — Cursor opens with the server prefilled.', 'wp-scheduled-posts'),
                __('Confirm the install in Cursor.', 'wp-scheduled-posts'),
            ],
        },
        {
            id: 'vscode',
            name: 'VS Code',
            Icon: IconCode,
            accent: '#2563eb',
            action: 'link',
            href: vscodeDeepLink,
            linkLabel: __('Add to VS Code', 'wp-scheduled-posts'),
            steps: [
                __('Click the button above — VS Code opens with the server prefilled.', 'wp-scheduled-posts'),
                __('Confirm the install in VS Code.', 'wp-scheduled-posts'),
            ],
        },
    ];

    return (
        <div className="wpsp-mcp">
            {/* ---------------------------------------------------- header */}
            <div className="wpsp-mcp-card wpsp-mcp-hero">
                <div className="wpsp-mcp-hero__icon">
                    <IconMcp />
                </div>
                <div className="wpsp-mcp-hero__body">
                    <div className="wpsp-mcp-hero__title">
                        <h3>{__('AI assistant access', 'wp-scheduled-posts')}</h3>
                        <span className={`wpsp-mcp-status${enabled ? ' is-on' : ''}`}>
                            {enabled
                                ? __('Active', 'wp-scheduled-posts')
                                : __('Inactive', 'wp-scheduled-posts')}
                        </span>
                    </div>
                    <p>
                        {__(
                            'Connect an AI assistant to your content schedule. Ask it what is queued, move a week of posts, or find which social connections need reconnecting — in plain language.',
                            'wp-scheduled-posts'
                        )}
                    </p>
                </div>
                <label className="wpsp-mcp-switch">
                    <input
                        type="checkbox"
                        checked={enabled}
                        disabled={saving}
                        onChange={(e) => toggleEnabled(e.target.checked)}
                    />
                    <span className="wpsp-mcp-switch__track">
                        <span className="wpsp-mcp-switch__thumb" />
                    </span>
                </label>
            </div>

            {error && <div className="wpsp-mcp-alert wpsp-mcp-alert--error">{error}</div>}

            {!status?.runtime_ok && (
                <div className="wpsp-mcp-alert wpsp-mcp-alert--error">
                    {__(
                        'The bundled AI runtime is missing from this installation, so assistants would connect but see no tools. Reinstall SchedulePress from an official build.',
                        'wp-scheduled-posts'
                    )}
                </div>
            )}

            {/* ---------------------------------------------- capabilities */}
            <div className="wpsp-mcp-card">
                <button
                    type="button"
                    className="wpsp-mcp-collapse"
                    onClick={() => setShowCaps(!showCaps)}
                    aria-expanded={showCaps}
                >
                    <span>
                        {__('What a connected assistant can do', 'wp-scheduled-posts')}
                        {enabled && (
                            <span className="wpsp-mcp-pill wpsp-mcp-pill--muted">
                                {/* translators: %d: number of available tools. */}
                                {__('%d tools', 'wp-scheduled-posts').replace('%d', String(toolsCount))}
                            </span>
                        )}
                    </span>
                    <IconChevron open={showCaps} />
                </button>
                {showCaps && (
                    <ul className="wpsp-mcp-caps">
                        {capabilities.map((cap, i) => (
                            <li key={i}>
                                <IconCheck size={13} />
                                <span>{cap}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>

            {!enabled && (
                <div className="wpsp-mcp-empty">
                    {__(
                        'Turn on AI assistant access to generate a connection URL.',
                        'wp-scheduled-posts'
                    )}
                </div>
            )}

            {/* ------------------------------------------------- connection */}
            {enabled && connected && (
                <>
                    <div className="wpsp-mcp-card">
                        <h3 className="wpsp-mcp-card__title">
                            {__('Connect an AI client', 'wp-scheduled-posts')}
                        </h3>

                        <CopyField
                            label={__('Connection URL', 'wp-scheduled-posts')}
                            value={endpoint}
                            id="endpoint"
                            copiedId={copiedId}
                            onCopy={copyWith}
                        />

                        <div className="wpsp-mcp-tabs">
                            <button
                                type="button"
                                className={tab === 'oauth' ? 'is-active' : ''}
                                onClick={() => setTab('oauth')}
                            >
                                {__('Sign in (OAuth)', 'wp-scheduled-posts')}
                            </button>
                            <button
                                type="button"
                                className={tab === 'cli' ? 'is-active' : ''}
                                onClick={() => setTab('cli')}
                            >
                                {__('Editors & CLI', 'wp-scheduled-posts')}
                            </button>
                        </div>

                        {tab === 'oauth' && (
                            <>
                                <p className="wpsp-mcp-note">
                                    {__(
                                        'These clients need only the URL above — they sign in through a one-time approval, so the token below never leaves this screen.',
                                        'wp-scheduled-posts'
                                    )}
                                </p>
                                <div className="wpsp-mcp-clients">
                                    {oauthClients.map((client) => (
                                        <ClientCard
                                            key={client.id}
                                            client={client}
                                            copiedId={copiedId}
                                            onCopy={copyWith}
                                        />
                                    ))}
                                </div>
                            </>
                        )}

                        {tab === 'cli' && (
                            <>
                                <p className="wpsp-mcp-note">
                                    {__(
                                        'These clients authenticate with the connection token. Treat it like a password — anyone holding it can manage this site’s schedule.',
                                        'wp-scheduled-posts'
                                    )}
                                </p>
                                <div className="wpsp-mcp-clients">
                                    {cliClients.map((client) => (
                                        <ClientCard
                                            key={client.id}
                                            client={client}
                                            copiedId={copiedId}
                                            onCopy={copyWith}
                                        />
                                    ))}
                                </div>

                                <details className="wpsp-mcp-details">
                                    <summary>{__('Manual setup', 'wp-scheduled-posts')}</summary>
                                    <CopyField
                                        label={__('Token', 'wp-scheduled-posts')}
                                        value={token}
                                        id="token"
                                        copiedId={copiedId}
                                        onCopy={copyWith}
                                    />
                                    <CopyField
                                        label={__('Fallback URL (plain permalinks)', 'wp-scheduled-posts')}
                                        value={fallback}
                                        id="fallback"
                                        copiedId={copiedId}
                                        onCopy={copyWith}
                                    />
                                </details>
                            </>
                        )}

                        <div className="wpsp-mcp-actions">
                            {!confirmReset ? (
                                <button
                                    type="button"
                                    className="wpsp-mcp-btn wpsp-mcp-btn--ghost"
                                    onClick={() => setConfirmReset(true)}
                                >
                                    {__('Reset token', 'wp-scheduled-posts')}
                                </button>
                            ) : (
                                <div className="wpsp-mcp-confirm">
                                    <span>
                                        {__(
                                            'Resetting disconnects every client using the token. Continue?',
                                            'wp-scheduled-posts'
                                        )}
                                    </span>
                                    <button
                                        type="button"
                                        className="wpsp-mcp-btn wpsp-mcp-btn--danger"
                                        onClick={rotateToken}
                                        disabled={saving}
                                    >
                                        {__('Reset', 'wp-scheduled-posts')}
                                    </button>
                                    <button
                                        type="button"
                                        className="wpsp-mcp-btn wpsp-mcp-btn--ghost"
                                        onClick={() => setConfirmReset(false)}
                                    >
                                        {__('Cancel', 'wp-scheduled-posts')}
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* ------------------------------------------- health */}
                    <div className="wpsp-mcp-card">
                        <div className="wpsp-mcp-card__header">
                            <h3 className="wpsp-mcp-card__title">
                                {__('Connection health', 'wp-scheduled-posts')}
                            </h3>
                            <button
                                type="button"
                                className="wpsp-mcp-btn"
                                onClick={runSelfTest}
                                disabled={testing}
                            >
                                {testing
                                    ? __('Testing…', 'wp-scheduled-posts')
                                    : __('Run test', 'wp-scheduled-posts')}
                            </button>
                        </div>
                        <p className="wpsp-mcp-note">
                            {__(
                                'Calls this site the way an AI client would and reports exactly where it breaks.',
                                'wp-scheduled-posts'
                            )}
                        </p>

                        {testResult && (
                            <>
                                <div
                                    className={`wpsp-mcp-alert ${
                                        testResult.ok
                                            ? 'wpsp-mcp-alert--success'
                                            : 'wpsp-mcp-alert--warning'
                                    }`}
                                >
                                    {testResult.message}
                                </div>
                                <ul className="wpsp-mcp-checks">
                                    {(testResult.checks || []).map((check) => (
                                        <li key={check.id} className={check.ok ? 'is-ok' : 'is-fail'}>
                                            <span className="wpsp-mcp-checks__icon">
                                                {check.ok ? <IconCheck size={13} /> : <IconCross size={13} />}
                                            </span>
                                            <div>
                                                <strong>{check.label}</strong>
                                                <span>{check.detail}</span>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </>
                        )}
                    </div>

                    {/* --------------------------------- social publishing */}
                    <div className="wpsp-mcp-card wpsp-mcp-hero">
                        <div className="wpsp-mcp-hero__body">
                            <div className="wpsp-mcp-hero__title">
                                <h3>
                                    {__('Allow posting to social media', 'wp-scheduled-posts')}
                                </h3>
                                <span
                                    className={`wpsp-mcp-status${
                                        status?.enable_mcp_social_publish ? ' is-on' : ''
                                    }`}
                                >
                                    {status?.enable_mcp_social_publish
                                        ? __('Allowed', 'wp-scheduled-posts')
                                        : __('Blocked', 'wp-scheduled-posts')}
                                </span>
                            </div>
                            <p>
                                {__(
                                    'Off by default. Sharing posts publicly to a real audience and cannot be undone, so an assistant can only do it when this is on — and even then it must confirm each share. Reading the schedule and rescheduling posts do not need this.',
                                    'wp-scheduled-posts'
                                )}
                            </p>
                        </div>
                        <label className="wpsp-mcp-switch">
                            <input
                                type="checkbox"
                                checked={!!status?.enable_mcp_social_publish}
                                disabled={saving}
                                onChange={(e) => toggleSocialPublish(e.target.checked)}
                            />
                            <span className="wpsp-mcp-switch__track">
                                <span className="wpsp-mcp-switch__thumb" />
                            </span>
                        </label>
                    </div>

                    {/* -------------------------------------- connected apps */}
                    {apps.length > 0 && (
                        <div className="wpsp-mcp-card">
                            <h3 className="wpsp-mcp-card__title">
                                {__('Connected AI apps', 'wp-scheduled-posts')}
                            </h3>
                            <ul className="wpsp-mcp-apps">
                                {apps.map((app) => (
                                    <li key={app.client_id}>
                                        <div>
                                            <strong>{app.name}</strong>
                                            <span>
                                                {app.read_only
                                                    ? __('Read-only', 'wp-scheduled-posts')
                                                    : __('Read & write', 'wp-scheduled-posts')}
                                                {' · '}
                                                {app.approved_by}
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            className="wpsp-mcp-btn wpsp-mcp-btn--ghost"
                                            onClick={() => revokeApp(app.client_id)}
                                        >
                                            {__('Revoke', 'wp-scheduled-posts')}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default MCP;
