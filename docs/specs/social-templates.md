# Social Templates — Functional Specification

**Availability:** Free (per-profile/per-network distinct templates are 🔒 Pro)
**Location:** **Settings → Social Templates** (sub-tabbed per platform).

## Summary

Defines the default message format used when a post is shared to each platform, using tokens, content-source options, and per-platform character limits.

## Settings & options (per platform)

Stored under `social_templates.{platform}.*`.

| Option (label) | Option key | Effect | Notes |
|---|---|---|---|
| Content Type | `content_type` | Link / Status / Status+Link / Media | Options vary by platform |
| Content Source | `content_source` | Use post **Excerpt** or full **Content** | |
| Add Category as tags | `is_category_as_tags` | Append categories as hashtags | |
| Template | `template_structure` | The message body | Default `{title}{content}{url}{tags}` |
| Character/Status limit | `status_limit` / `tweet_limit` / `note_limit` | Max characters | Hard-capped on save (see limits) |
| How often to share a post | `post_share_limit` | Number of times to share | `0` = unlimited |
| Remove CSS from content | `remove_css_from_content` | Strips CSS (Facebook/Instagram) | |
| Facebook Open Graph / meta | `is_show_meta` | Send OG/meta data (Facebook) | Default ON |
| Show Post Thumbnail | `is_show_post_thumbnail` | Attach thumbnail (Twitter) | |
| Add Image Link | `is_set_image_link` | Include image link (Pinterest) | |

## Template tokens

| Token | Renders |
|---|---|
| `{title}` | Post title |
| `{content}` | Excerpt or full content (per `content_source`) |
| `{url}` | Post permalink |
| `{tags}` | Categories/tags as hashtags |

## Behavior & rules

- The template is expanded per share, then trimmed to the platform's limit.
- **Character-limit hard caps** (enforced on save):

  | Platform | Max characters |
  |---|---|
  | Facebook | 63,206 |
  | Twitter / X | 280 |
  | LinkedIn | 1,300 |
  | Pinterest | 500 |
  | Instagram | 2,100 |
  | Medium | 45,000 |
  | Threads | 480 |
  | Google Business | 1,500 |

- `post_share_limit = 0` shares without a repeat cap; a positive value limits repeats.
- Platform-specific toggles (OG meta, thumbnail, image link, CSS removal) only appear where relevant.

## States

Template config is a **saved default per platform**. A post can override it — see [custom per-post templates](custom-post-templates.md).

## Interactions

- Consumed by the [social sharing engine](social-sharing.md) for both auto-share and Share Now.
- [AI Caption](ai-caption.md) can generate content that respects the same limits.
- **Pro:** distinct templates per connected profile/network (free edits only the first profile per platform).

## Limits & edge cases

- Content trimmed to the cap may cut mid-word; write titles/excerpts accordingly.
- `{tags}` output depends on `allow_taxonomy_as_tags` and the post's terms.

## Technical touchpoints

- **Option keys:** `social_templates.{platform}.{content_type,content_source,is_category_as_tags,template_structure,status_limit|tweet_limit|note_limit,post_share_limit,remove_css_from_content,is_show_meta,is_show_post_thumbnail,is_set_image_link}`.
- **Tokens:** `{title}`, `{content}`, `{url}`, `{tags}`.
