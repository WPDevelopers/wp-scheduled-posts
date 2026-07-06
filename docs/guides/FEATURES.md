# SchedulePress (Free) — Feature Guide

A plain-language guide to **every feature** in the free SchedulePress plugin: what it does, where to find it, what you can configure, and how it behaves.

- **Plugin:** SchedulePress (free) by WPDeveloper
- **Admin menu:** **SchedulePress** (in the WordPress left sidebar)
- **Settings are saved in:** the `wpsp_settings_v5` option

> Items marked **🔒 Pro** are visible in the free plugin but locked — clicking them shows an upgrade popup. Everything else is fully usable for free. For the full list of Pro-only features, see the Pro guide.

---

## Table of contents

1. [Where everything lives (admin menu)](#1-where-everything-lives)
2. [Settings screen overview](#2-settings-screen-overview)
3. [General settings](#3-general-settings)
4. [Scheduling posts](#4-scheduling-posts)
5. [Schedule Calendar](#5-schedule-calendar)
6. [Dashboard widget](#6-dashboard-widget)
7. [Sitewide admin bar menu](#7-sitewide-admin-bar-menu)
8. [Social auto-share & profiles](#8-social-auto-share--profiles)
9. [Social templates](#9-social-templates)
10. [Custom per-post social messages](#10-custom-per-post-social-messages)
11. [AI Caption (Write With AI)](#11-ai-caption-write-with-ai)
12. [Email notifications & review workflow](#12-email-notifications--review-workflow)
13. [Post type & role controls](#13-post-type--role-controls)
14. [Other features](#14-other-features)
15. [Free vs Pro at a glance](#15-free-vs-pro-at-a-glance)

---

## 1. Where everything lives

After activation you get a top-level **SchedulePress** menu with:

- **Settings** — the main single-page dashboard where every option lives (built as a React app).
- **Calendar** — the drag-and-drop editorial calendar.

Additionally, a **"Calendar"** link is added *under each post type you enable* (e.g. under **Posts**), so editors can jump straight to that content type's calendar.

Who can see it: only users whose role is in the **allowed users** list (default: Administrator). See [§13](#13-post-type--role-controls).

---

## 2. Settings screen overview

**SchedulePress → Settings** is organized into these tabs:

| Tab | What it's for |
|---|---|
| **General** | Global display + workflow toggles, post types, roles, categories, admin-bar template |
| **Calendar** | The editorial calendar, embedded inside settings |
| **Email Notify** | Author/reviewer email notifications |
| **Social Profile** | Connect and enable your social accounts |
| **Social Templates** | How each shared post is formatted, per platform |
| **Write With AI** | Your OpenAI key for AI-generated captions |
| **Scheduling Hub** 🔒 Pro | Auto Scheduler, Manual Scheduler, Missed Schedule handling |

Each section has its own **Save** button. Settings persist immediately after saving.

---

## 3. General settings

Found in **Settings → General**. The main controls:

| Setting | What it does | Default |
|---|---|---|
| **Show Scheduled Posts in Dashboard Widget** | Adds the "Scheduled Posts" box to the WP dashboard ([§6](#6-dashboard-widget)) | ON |
| **Show Scheduled Posts in Sitewide Admin Bar** | Shows the scheduled-posts menu on the **front end** admin bar | ON |
| **Show Scheduled Posts in Admin Bar** | Shows it in the **wp-admin** admin bar | ON |
| **Show Post Types** | Which post types SchedulePress manages everywhere | Posts |
| **Allow Taxonomy as Tags** | Which taxonomies are treated as tags when sharing | Category, Tag |
| **Show Categories** | Limit scheduling to specific categories | All |
| **Allow users** | Which roles can use SchedulePress | Administrator |
| **Calendar Default Schedule Time** | Default time given to posts created/dropped on the calendar | 00:00 |
| **Show Publish Post Immediately Button** | Adds "Publish future post immediately" to the classic editor | ON |
| **Show Scheduled Posts in Elementor** | Enables scheduling from the Elementor editor | ON |

### Admin bar list template

Under General you can customize how each scheduled post looks in the admin-bar menu:

- **Item template** — default `<strong>%TITLE%</strong> / %AUTHOR% / %DATE%`. Available tokens: **`%TITLE%`**, **`%AUTHOR%`**, **`%DATE%`** (HTML allowed).
- **Title length** — how many characters of the title to show (default 45).
- **Date format** — PHP date format (default `M-d h:i:a`).

### Pro toggles shown here (locked in free)

**Show Elementor Section Schedule** 🔒, **Post Republish and Unpublish** 🔒, **Active Republish Social Share** 🔒, **Publish Now with Future Date** 🔒, **Auto-Share upon Publishing** 🔒.

---

## 4. Scheduling posts

### In the block editor / Elementor — "Schedule And Share"

In the post editor you'll see a **SchedulePress** box with a **"Schedule And Share"** button. It opens a full-screen panel where you manage the whole publishing + sharing workflow in one place:

- **Publish On** — a date & time picker to schedule (or publish) the post. It reads the current post date and works across the block editor, classic editor, and page builders. Includes a "Now" shortcut.
- **Publish future post immediately** — appears when the post is *already* scheduled. Two buttons:
  - **Current Date** — publish right now with today's date/time.
  - **Future Date** — publish right now but keep displaying the chosen future date.
- **Publishing Cycle** 🔒 Pro — "Unpublish On" / "Republish On" and an Advanced Schedule toggle.
- **Social Share** — pick connected profiles and share (see [§8](#8-social-auto-share--profiles)).

### In the classic editor

For any post already in **Scheduled** status, a **"Publish future post immediately"** checkbox appears in the Publish box (when the General toggle is on). It gives the same **Current Date / Future Date** choice, with an inline help tooltip.

### Auto / Manual / Missed scheduling — 🔒 Pro

The **Scheduling Hub** tab holds three Pro features:

- **Advanced Schedule** — schedule an *update* to an already-published post (keep it live, or move it to Draft until the update publishes).
- **Manage Schedule** — **Auto Scheduler** (auto-queue content into time slots) and **Manual Scheduler** (set exact preferred times).
- **Missed Schedule** — automatically publishes posts that WordPress missed at their scheduled time.

---

## 5. Schedule Calendar

**SchedulePress → Calendar** (also under each enabled post type). A full editorial calendar where you can:

- **Drag and drop** scheduled posts to reschedule them.
- **Create a new post** by clicking a date/time cell (uses your default calendar time).
- **Edit inline** — change title and category.
- **Delete** a post from the calendar.
- **Filter** by category and by post type (only the post types you've enabled).

It respects your site timezone, start-of-week, and per-user edit/delete permissions — good for planning across multiple authors.

---

## 6. Dashboard widget

When enabled, a **"Scheduled Posts"** box appears on the WordPress dashboard. It lists every upcoming (scheduled) post across your enabled post types, showing: **title** (links to edit), **post type**, **category**, **scheduled date & time**, and **author**. If nothing is scheduled it shows *"No post is scheduled."*

---

## 7. Sitewide admin bar menu

A **"Scheduled Posts (N)"** menu in the WordPress admin bar (N = number of upcoming posts):

- Shows in **wp-admin** and/or on the **front end**, depending on your General toggles.
- Lists all scheduled posts (oldest first), grouped **8 per submenu** with prev/next paging.
- Each item uses your **admin bar template** (`%TITLE%` / `%AUTHOR%` / `%DATE%`) and links to the edit screen.
- Only visible to allowed roles.

---

## 8. Social auto-share & profiles

### Supported platforms

| Platform | Free? | Notes |
|---|---|---|
| Facebook | ✅ | Page / Group |
| Twitter / X | ✅ | |
| LinkedIn | ✅ | Personal **Profile** free · **Page/organization** is 🔒 Pro |
| Pinterest | ✅ | Board + section selection |
| Instagram | ✅ | Business account |
| Medium | ✅ | |
| Threads | ✅ | |
| Google Business Profile | 🔒 Pro | Entire platform is Pro-only |

> In free you connect **personal profiles**. **"Page"-type accounts** (except Facebook) and connecting **many profiles** are Pro.

### Connecting an account (Settings → Social Profile)

Each platform has a row with:
- An **enable/disable** toggle.
- A **connect modal** where you paste your app's API keys and copy the **Redirect/Callback URL**, with links to setup docs.
- Your connected accounts, each with its own on/off toggle.

### Auto-share on publish

When a scheduled post publishes, SchedulePress automatically shares it to every **enabled** connected profile — no manual step needed.

### "Share Now" (manual/instant share)

Share a post immediately without scheduling:

- **Block editor** — in the Schedule And Share panel's **Social Share** section, pick profiles and click **Share Now**. A status modal shows the result per profile.
- **Classic editor** — a **"Social Share Settings"** box lets you choose platforms, upload a **custom social banner image**, disable sharing for that post, choose a Pinterest board, and hit **Share Now**.

---

## 9. Social templates

**Settings → Social Templates**, sub-tabbed per platform. Controls *how* each shared post is written:

- **Content Type** — Link / Status / Status+Link / Media (varies per platform).
- **Content Source** — use the post **Excerpt** or full **Content**.
- **Add category as tags** — append categories as hashtags.
- **Template** — the message body, using tokens **`{title}`**, **`{content}`**, **`{url}`**, **`{tags}`**. Default: `{title}{content}{url}{tags}`.
- **Character limit** — capped to each platform's real max (e.g. Twitter 280, LinkedIn 1300, Facebook 63,206, Instagram 2,100, Threads 480, Pinterest 500, Medium 45,000, Google Business 1,500).
- **How often to share a post** — 0 means unlimited.
- Platform extras — Facebook Open Graph/meta, Twitter post thumbnail, Pinterest image link, remove-CSS-from-content, etc.

---

## 10. Custom per-post social messages

From the post editor you can override the global template for a single post: write a **custom message per platform** (or a global one), **preview** how it will look, **edit** it, pick which profiles to send to, and share/schedule it. Saved with the post.

---

## 11. AI Caption (Write With AI)

Generate social captions with AI, using **your own OpenAI key**:

1. **Settings → Write With AI** — paste your **OpenAI API Key** (validated against OpenAI).
2. In the post editor's custom-template area, open the **AI Caption** drawer. Choose:
   - **Platforms** to write for,
   - **Tone** — Professional, Casual, Friendly, Witty, Bold, Informative, or "Match Post Tone",
   - **Length** — Auto, Short (≤280), Medium (≤500), Long (500+),
   - **Generate hashtags** on/off.
3. Captions are generated per platform (respecting each char limit). You can **Insert**, **Edit**, or dismiss each one.

---

## 12. Email notifications & review workflow

**Settings → Email Notify** (all off by default). Emails are HTML and support the tokens **`%title%`**, **`%date%`**, **`%author%`**, **`%permalink%`**.

| Notification | Fires when | Recipients |
|---|---|---|
| **Under Review** | Post → Pending review | By role / username / email |
| **Rejected** | Post → Trash | Post author |
| **Scheduled** | Post → Scheduled | By role / username / email + author |
| **Scheduled post Published** | Scheduled post goes live | Author |
| **Published** | Post → Published | Author |

Together these give a simple **editorial review/approval flow** for multi-author sites (author submits → reviewer notified → approved/scheduled or rejected). A short cooldown prevents duplicate emails.

---

## 13. Post type & role controls

- **Post types** — enable SchedulePress for any public post type (default: Posts). Enabling a type turns on its calendar, dashboard-widget listing, admin-bar listing, editor panel, and social sharing.
- **Roles** — the **Allow users** setting decides which roles can use SchedulePress features.
- **Categories** — limit scheduling/listing to specific categories.
- Built-in integrations: **Elementor**, **BetterDocs** (docs post type), and **Visual Composer** detection.

---

## 14. Other features

- **Usage tracking (opt-in)** — an optional notice asks permission to share non-sensitive diagnostic data. Purely opt-in.
- **Admin notices / upgrade prompts** — occasional Pro feature cards and upsell notices.
- **Pro overlay** — locked Pro controls are visible but show an upgrade popup instead of working.
- **Elementor scheduling** — schedule content directly from the Elementor editor.
- **License field** — activate a Pro license (free shows an upsell).

---

## 15. Free vs Pro at a glance

**Free includes:** manual scheduling + publish-future-immediately, drag-and-drop Calendar, Dashboard widget, admin-bar menu, email notifications, post-type/role/category controls, connect & auto/instant-share to Facebook / Twitter / LinkedIn (profile) / Pinterest / Instagram / Medium / Threads, per-platform social templates, custom per-post social messages, AI captions (your own OpenAI key), and Elementor post scheduling.

**Pro adds:** Auto Scheduler, Manual Scheduler, Missed Schedule handler, Advanced Schedule (update published posts), Publishing Cycle (Unpublish/Republish), Publish-now-with-future-date, auto-share-on-publish master toggle, Republish social share, Elementor **section** scheduling, Google Business Profile, LinkedIn Pages & other Page-type/multi-profile accounts, and premium support. See the **Pro feature guide** for details.
