# Connecting Social Accounts

SchedulePress can automatically share your posts to social media when they publish, and let you share instantly with a **Share Now** button. This guide explains how connecting works and walks through each platform.

All connections are managed at **SchedulePress → Settings → Social Profile**.

## How connecting works (the general flow)

SchedulePress connects to each network using **your own app credentials** — this keeps your data private and your posting limits your own. The pattern is the same for every platform:

1. Go to **Settings → Social Profile** and pick a platform.
2. Open its **connect modal**. You'll see fields for the platform's **API keys** (App ID / secret, etc.) and a **Redirect / Callback URL** to copy.
3. In the platform's developer portal, **create an app**, paste the copied **Redirect URL** into it, and copy the app's keys back into SchedulePress.
4. Click **Connect / Authorize** and approve access.
5. **Enable** the connected profile with its toggle.

Each modal includes links to the official step-by-step doc for that platform. Once connected and enabled, scheduled posts auto-share to that profile on publish.

> **Free vs Pro:** In free you can connect **one profile per network**. **Pro** unlocks **unlimited profiles per network**, **LinkedIn Company pages**, **multiple Pinterest boards**, and **Google Business Profile**.

## Facebook

- Connect a Facebook **Page** or **Group**.
- Create a Facebook app in the Meta for Developers portal, add the Redirect URL, and authorize.
- Options include Open Graph/meta data and thumbnail handling (see [Social Templates](FEATURES.md#9-social-templates)).

## Twitter / X

- Connect your Twitter/X account with your app's API keys.
- Tweets respect the 280-character limit; you can include the post thumbnail.

## LinkedIn

- **Free:** connect your **personal LinkedIn profile**.
- **Pro:** connect **LinkedIn Company / organization pages**.
- Content can be shared as a link, status, or media (1,300-character limit).

## Pinterest

- Connect Pinterest and choose a **board** to pin to.
- **Pro:** select among **multiple boards and sections**.
- You can add an image link with each pin (500-character limit).

## Instagram

- Connect an Instagram **Business** account.
- Shares respect a 2,100-character limit; you can strip CSS from content.

## Medium

- Connect your Medium account to publish/share posts (up to 45,000 characters).

## Threads

- Connect your Threads account (480-character limit).

## Bluesky

- Connect with your **handle** (`name.bsky.social`) and a Bluesky **App Password** — no developer app or OAuth needed.
- Shares respect Bluesky's **300-character** limit.
- Step-by-step guide with screenshots: [Bluesky integration](bluesky/README.md).

## Google Business Profile — Pro

- Connect your Google Business listing to auto-share posts (1,500-character limit). This platform is **Pro-only**.

## Sharing options after you connect

- **Auto-share on publish** — enabled profiles receive the post automatically when it goes live.
- **Share Now (instant)** — in the editor's **Social Share** section (block editor) or the **Social Share Settings** box (classic editor), pick profiles and share immediately; a status view shows the result per profile.
- **Custom message per post** — override the global template for a single post; see [Social Templates](FEATURES.md#9-social-templates) and [Custom per-post messages](FEATURES.md#10-custom-per-post-social-messages).
- **Custom social banner image** — upload a specific image to share for a post (classic editor).
- **Opt a post out** — disable social sharing for an individual post.

## How the shared message is formatted

Control wording per platform at **Settings → Social Templates** using tokens:

- `{title}` — the post title
- `{content}` — excerpt or full content (your choice)
- `{url}` — the post link
- `{tags}` — categories/tags as hashtags

Each platform enforces its real character limit, shown with a live counter. See [Social Templates](FEATURES.md#9-social-templates) for every option.

## Troubleshooting

- **Authorization failed / redirect mismatch** — the **Redirect URL** in your app must exactly match the one shown in SchedulePress.
- **Post didn't share** — confirm the profile's **status toggle is on**, the platform is enabled for that post type, and the post isn't opted out of sharing.
- **Reconnect needed** — tokens expire; if sharing stops, reconnect the profile from Social Profile.
- **Need the official per-platform guide?** Use the doc links inside each connect modal, or see https://wpdeveloper.com/docs-category/wp-scheduled-posts/.
