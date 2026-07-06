# Getting Started with SchedulePress

This guide takes you from install to your first scheduled, auto-shared post.

## 1. Requirements

- WordPress 5.8 or newer (works on 4.0+, but newer is recommended)
- PHP 7.4 or newer
- No other plugins required

## 2. Install & activate

**From your dashboard:**

1. Go to **Plugins → Add New**.
2. Search for **"SchedulePress"**.
3. Click **Install Now**, then **Activate**.

**Manual upload:** download the ZIP, then **Plugins → Add New → Upload Plugin**, choose the file, install, and activate.

After activation you'll be taken to the SchedulePress welcome screen, and a new **SchedulePress** menu appears in the left sidebar.

## 3. First-run setup (5 minutes)

Open **SchedulePress → Settings → General** and set the essentials:

1. **Show Post Types** — pick which content types SchedulePress should manage (default: Posts). Add pages or custom post types if you schedule those.
2. **Allow users** — choose which roles can use SchedulePress (default: Administrator). Add Editor/Author for multi-author sites.
3. **Calendar Default Schedule Time** — the time given to posts you create by clicking a calendar day (default 00:00).
4. Leave the **Dashboard Widget** and **Admin Bar** toggles on for at-a-glance visibility.

Click **Save**.

## 4. Schedule your first post

1. Create or edit a post.
2. In the editor, open the **SchedulePress** box and click **"Schedule And Share"**.
3. Under **Publish On**, pick a future date and time.
4. Publish/schedule the post as usual.

Your post is now scheduled. See it on **SchedulePress → Calendar**, in the **dashboard widget**, and in the **admin bar** menu.

> Full details of every scheduling method are in **[Scheduling Posts](SCHEDULING.md)**.

## 5. (Optional) Connect a social account

To auto-share posts when they publish:

1. Go to **SchedulePress → Settings → Social Profile**.
2. Pick a platform (e.g. Facebook) and open its connect modal.
3. Follow the on-screen steps — you'll create an app, paste API keys, copy the **Redirect/Callback URL**, and authorize.
4. Enable the connected profile.

Now any scheduled post will auto-share to that profile on publish. Step-by-step guides for each platform are in **[Connecting Social Accounts](SOCIAL-SETUP.md)**.

## 6. Where to go next

- **[Feature Guide](FEATURES.md)** — the full tour.
- **[Scheduling Posts](SCHEDULING.md)** — calendar, publish-immediately, and (Pro) auto/manual/missed scheduling.
- **[FAQ & Troubleshooting](FAQ.md)** — if something isn't working.

## Quick reference

| I want to… | Go to |
|---|---|
| See all scheduled posts on a calendar | SchedulePress → Calendar |
| Change what's scheduled/who can schedule | Settings → General |
| Connect social accounts | Settings → Social Profile |
| Control how shared posts are worded | Settings → Social Templates |
| Get email alerts on post status | Settings → Email Notify |
| Generate captions with AI | Settings → Write With AI |
| Unlock auto/manual/missed scheduling | Scheduling Hub (Pro) |
