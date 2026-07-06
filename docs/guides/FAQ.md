# FAQ & Troubleshooting

Common questions and fixes for SchedulePress. For the full official FAQ, see https://wpdeveloper.com/docs-category/wp-scheduled-posts/.

## General

### Where did "WP Scheduled Posts" go?
It was **rebranded to SchedulePress**. It's the same plugin with the same features plus an improved interface. Your content is not affected.

### Do I need any other plugins to use SchedulePress?
No. Install and activate SchedulePress and you're ready to go — no dependencies.

### Is it free?
Yes, the core plugin is free. Some advanced features (auto/manual/missed scheduling, unlimited social profiles, Google Business, and more) require **SchedulePress Pro**. See [Free vs Pro](FEATURES.md#15-free-vs-pro-at-a-glance).

### Which post types can I schedule?
Any public post type you enable under **Settings → General → Show Post Types** (Posts by default; add Pages or custom post types as needed).

## Scheduling

### A scheduled post didn't publish at its time — why?
This is WordPress's built-in **cron** missing the scheduled moment, which happens on low-traffic sites (WP-Cron only runs when someone visits). SchedulePress **Pro's Missed Schedule** handler detects and auto-publishes these posts. Without Pro, you can also set up a real server cron for WP-Cron.

### The post published at the wrong time.
Check **WordPress → Settings → General → Timezone** (it should match your locale), and your **Calendar Default Schedule Time** in SchedulePress → Settings → General.

### Can I schedule from the classic editor / Elementor?
Yes — scheduling works in the **block editor**, the **classic editor**, and **Elementor**. The "Schedule And Share" panel appears in each.

### How do I publish a scheduled post right now?
Use **"Publish future post immediately"** — choose **Current Date** (stamp it now) or **Future Date** (publish now, keep the future date shown). See [Scheduling Posts](SCHEDULING.md#publish-a-scheduled-post-immediately).

### I don't see the SchedulePress panel in my editor.
Make sure (1) the post type is enabled in **Settings → General → Show Post Types**, and (2) your user role is listed in **Allow users**.

## Social sharing

### Can I share scheduled posts on social media?
Yes. SchedulePress integrates with **Facebook, Twitter/X, LinkedIn, Pinterest, Instagram, Medium, Threads**, and (Pro) **Google Business Profile**. Connect an account and your scheduled posts share automatically on publish. See [Connecting Social Accounts](SOCIAL-SETUP.md).

### My post didn't get shared.
Check that:
- the profile's **status toggle is ON** in Settings → Social Profile,
- the platform is enabled for that post type,
- the post wasn't **opted out** of sharing, and
- the account is still authorized (tokens can expire — **reconnect** if needed).

### Authorization keeps failing.
The **Redirect / Callback URL** in your social app must exactly match the URL SchedulePress shows in the connect modal. Copy it precisely.

### How many social accounts can I connect?
**One per network** in free; **unlimited per network** (plus LinkedIn pages, multiple Pinterest boards, Google Business) in **Pro**.

### How do I change how the shared message reads?
Edit per-platform templates at **Settings → Social Templates** using `{title}`, `{content}`, `{url}`, `{tags}` tokens, or write a **custom message per post** in the editor. See [Social Templates](FEATURES.md#9-social-templates).

## AI captions

### How do I use AI captions?
Add your **OpenAI API key** at **Settings → Write With AI**, then open the **AI Caption** drawer in the post editor and pick a tone, length, and hashtag option. See [AI Caption](FEATURES.md#11-ai-caption-write-with-ai).

### Does the AI feature cost extra?
SchedulePress doesn't charge for it, but it uses **your own OpenAI account**, so OpenAI's usage charges apply to your key.

## Notifications

### How do I get emailed when a post is scheduled/published/reviewed?
Turn on the relevant notifications at **Settings → Email Notify** and choose recipients by role, username, or email. This also powers a simple **review/approval workflow** for multi-author sites. See [Email notifications](FEATURES.md#12-email-notifications--review-workflow).

## Still stuck?

- Official docs: https://wpdeveloper.com/docs-category/wp-scheduled-posts/
- Support forum: https://wordpress.org/support/plugin/wp-scheduled-posts/
- Video tutorials: https://www.youtube.com/playlist?list=PLWHp1xKHCfxDgooG8tj4i-w-XIfRrwGpF
