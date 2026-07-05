# AI Caption (Write With AI) — Functional Specification

**Availability:** Free (requires your own OpenAI API key)
**Location:** **Settings → Write With AI** (key); post editor → **AI Caption** drawer (generation).

## Summary

Generates social captions with OpenAI from the post's content, tuned by tone, length, and hashtag options, respecting each platform's character limit.

## Settings & options

| Option (label) | Option key | Effect |
|---|---|---|
| OpenAI API Key | `openai_api_key` | Your OpenAI key; validated against OpenAI before use |

### Generation options (AI Caption drawer)

| Option | Values | Effect |
|---|---|---|
| Platforms | any connected platforms | Which platforms to write captions for |
| Tone | Professional, Casual, Friendly, Witty, Bold, Informative, **Match Post Tone** | Voice of the caption |
| Length | Auto, Short (≤280), Medium (≤500), Long (500+) | Target caption length |
| Generate hashtags | on/off | Append relevant hashtags |
| Auto-generate from post content | on/off | Seed generation from the post body |

## Behavior & rules

- The key is **validated** against OpenAI (models endpoint) before captions can be generated.
- Captions are generated **per selected platform** and trimmed to that platform's [character limit](social-templates.md).
- Each generated caption can be **Inserted**, **Edited**, or **dismissed** independently.
- "Match Post Tone" derives the voice from the post content rather than a fixed tone.

## States

| State | Meaning |
|---|---|
| No key | Feature inert; drawer prompts to add a key. |
| Key valid | Generation available. |
| Generated | Captions returned per platform, pending insert/edit/dismiss. |
| Inserted | Caption applied to that platform's message. |

## Interactions

- Feeds the [custom per-post message](custom-post-templates.md) and thus the [social sharing engine](social-sharing.md).
- Respects the same per-platform limits as [social templates](social-templates.md).

## Limits & edge cases

- **Costs** accrue on **your** OpenAI account (SchedulePress adds no charge).
- Generation quality/availability depends on OpenAI API status and your quota.
- The endpoint itself is not Pro-gated, but applying captions across **multiple profiles** follows the Pro rule in [custom templates](custom-post-templates.md).

## Technical touchpoints

- **Option key:** `openai_api_key`.
- **REST:** `POST wp-scheduled-posts/v1/ai-caption/{post_id}` (generate), `ai-test-key` (validate key).
- **Provider:** OpenAI Chat Completions; key validated via the models endpoint.
