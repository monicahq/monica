---
version: alpha
name: kollek-design-analysis
description: A clean software interface anchored on white canvas with black primary CTAs and simple typography. The system reads as friendly modern SaaS — generous whitespace, soft-rounded cards (~12px), product UI fragments shown directly inside cards, and a dark navy footer that visually closes long-scroll pages.

colors:
  primary: "#111111"
  primary-active: "#242424"
  primary-disabled: "#e5e7eb"
  ink: "#111111"
  body: "#374151"
  muted: "#6b7280"
  muted-soft: "#898989"
  hairline: "#e5e7eb"
  hairline-soft: "#f3f4f6"
  canvas: "#ffffff"
  surface-soft: "#f8f9fa"
  surface-card: "#f5f5f5"
  surface-strong: "#e5e7eb"
  surface-dark: "#101010"
  surface-dark-elevated: "#1a1a1a"
  on-primary: "#ffffff"
  on-dark: "#ffffff"
  on-dark-soft: "#a1a1aa"
  brand-accent: "#3b82f6"
  success: "#10b981"
  warning: "#f59e0b"
  error: "#ef4444"
  badge-orange: "#fb923c"
  badge-pink: "#ec4899"
  badge-violet: "#8b5cf6"
  badge-emerald: "#34d399"

typography:
  display-xl:
    fontFamily: "Inter, sans-serif"
    fontSize: 64px
    fontWeight: 600
    lineHeight: 1.05
    letterSpacing: -2px
  display-lg:
    fontFamily: "Inter, sans-serif"
    fontSize: 48px
    fontWeight: 600
    lineHeight: 1.1
    letterSpacing: -1.5px
  display-md:
    fontFamily: "Inter, sans-serif"
    fontSize: 36px
    fontWeight: 600
    lineHeight: 1.15
    letterSpacing: -1px
  display-sm:
    fontFamily: "Inter, sans-serif"
    fontSize: 28px
    fontWeight: 600
    lineHeight: 1.2
    letterSpacing: -0.5px
  title-lg:
    fontFamily: "Inter, sans-serif"
    fontSize: 22px
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: -0.3px
  title-md:
    fontFamily: "Inter, sans-serif"
    fontSize: 18px
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: 0
  title-sm:
    fontFamily: "Inter, sans-serif"
    fontSize: 16px
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: 0
  body-md:
    fontFamily: "Inter, sans-serif"
    fontSize: 16px
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: 0
  body-sm:
    fontFamily: "Inter, sans-serif"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: 0
  caption:
    fontFamily: "Inter, sans-serif"
    fontSize: 13px
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: 0
  code:
    fontFamily: "JetBrains Mono, ui-monospace, monospace"
    fontSize: 14px
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: 0
  button:
    fontFamily: "Inter, sans-serif"
    fontSize: 14px
    fontWeight: 600
    lineHeight: 1
    letterSpacing: 0
  nav-link:
    fontFamily: "Inter, sans-serif"
    fontSize: 14px
    fontWeight: 500
    lineHeight: 1.4
    letterSpacing: 0

rounded:
  xs: 4px
  sm: 6px
  md: 8px
  lg: 12px
  xl: 16px
  pill: 9999px
  full: 9999px

spacing:
  xxs: 4px
  xs: 8px
  sm: 12px
  md: 16px
  lg: 24px
  xl: 32px
  xxl: 48px
  section: 96px

components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    padding: 12px 20px
    height: 40px
  button-primary-active:
    backgroundColor: "{colors.primary-active}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.md}"
  button-primary-disabled:
    backgroundColor: "{colors.primary-disabled}"
    textColor: "{colors.muted}"
    rounded: "{rounded.md}"
  button-secondary:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.button}"
    rounded: "{rounded.md}"
    padding: 12px 20px
    height: 40px
  button-icon-circular:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.full}"
    size: 36px
  button-text-link:
    backgroundColor: transparent
    textColor: "{colors.ink}"
    typography: "{typography.button}"
  text-link:
    backgroundColor: transparent
    textColor: "{colors.ink}"
    typography: "{typography.body-md}"
  top-nav:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.nav-link}"
    height: 64px
  nav-pill-group:
    backgroundColor: "{colors.surface-soft}"
    textColor: "{colors.ink}"
    typography: "{typography.nav-link}"
    rounded: "{rounded.pill}"
    padding: 6px
  hero-band:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.display-xl}"
    padding: 96px
  hero-app-mockup-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.xl}"
  feature-card:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink}"
    typography: "{typography.title-md}"
    rounded: "{rounded.lg}"
    padding: 32px
  feature-icon-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.title-sm}"
    rounded: "{rounded.lg}"
    padding: 24px
  product-mockup-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.lg}"
    padding: 24px
  testimonial-card:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink}"
    typography: "{typography.body-md}"
    rounded: "{rounded.lg}"
    padding: 24px
  pricing-tier-card:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.title-lg}"
    rounded: "{rounded.lg}"
    padding: 32px
  pricing-tier-card-featured:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.on-dark}"
    typography: "{typography.title-lg}"
    rounded: "{rounded.lg}"
    padding: 32px
  text-input:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.body-md}"
    rounded: "{rounded.md}"
    padding: 10px 14px
    height: 40px
  text-input-focused:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    rounded: "{rounded.md}"
  category-tab:
    backgroundColor: transparent
    textColor: "{colors.muted}"
    typography: "{typography.nav-link}"
    padding: 8px 14px
    rounded: "{rounded.md}"
  category-tab-active:
    backgroundColor: "{colors.canvas}"
    textColor: "{colors.ink}"
    typography: "{typography.nav-link}"
    rounded: "{rounded.md}"
  avatar-circle:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink}"
    rounded: "{rounded.full}"
    size: 36px
  badge-pill:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink}"
    typography: "{typography.caption}"
    rounded: "{rounded.pill}"
    padding: 4px 12px
  rating-stars:
    backgroundColor: transparent
    textColor: "{colors.badge-orange}"
    typography: "{typography.caption}"
  cta-band-light:
    backgroundColor: "{colors.surface-card}"
    textColor: "{colors.ink}"
    typography: "{typography.display-sm}"
    rounded: "{rounded.lg}"
    padding: 48px
  footer:
    backgroundColor: "{colors.surface-dark}"
    textColor: "{colors.on-dark-soft}"
    typography: "{typography.body-sm}"
    padding: 64px
---

## Overview

KolleK is a clean, friendly modern-SaaS interface — white canvas (`{colors.canvas}` — #ffffff) with black primary CTAs (`{colors.primary}` — #111111), Inter display typography, and `{colors.surface-card}` (#f5f5f5) light-gray cards holding product UI fragments. The system reads as confidently engineered without trying to impress — every band has clear hierarchy, generous whitespace, and a single primary action.

KolleK doesn't paint marketing illustrations of the product; it shows the actual product chrome at small scale embedded in the marketing flow.

KolleK doesn't use illustrations inside the logged app; it only displays icons and text to deliver value.

KolleK's design is simple. It has long separators (borders) that take the entire available width and height. It is aimed for efficiency, and for geeks who prefer efficiency and clarity over complexity.

Everything needed to build UI is in this file: **YAML above** (every color hex, type scale, spacing,
corner radii, border widths, and a few control "recipes") plus **this prose** (how the pieces fit
together). Rules use RFC 2119: MUST, MUST NOT, SHOULD, MAY.

**Key Characteristics:**
- White canvas with black primary CTA (`{colors.primary}` — #111111). Buttons are `{rounded.md}` (8px) with confident weight-600 labels. Standard friendly-SaaS button.
- Avatars are circular (`{rounded.full}`), 36px diameter.
- Footer is dark navy (`{colors.surface-dark}` — #101010) with light text (`{colors.on-dark-soft}` — #a1a1aa).
- Spacing rhythm is `{spacing.section}` (96px) between major bands — tight enough to feel modern-SaaS but generous enough to breathe.
- Border radius is hierarchical: `{rounded.md}` (8px) for buttons + inputs, `{rounded.lg}` (12px) for content cards, `{rounded.xl}` (16px) for the hero app-mockup container, `{rounded.pill}` for nav-pill-group + badges, `{rounded.full}` for avatars + icon buttons.

### Atmosphere rules

- **MUST** default to neutral backgrounds (`surface`, `background-neutral`) for the ~90% of the
  canvas that is not communicating meaning.
- **MUST** reserve saturated color for one of: semantic roles (brand, danger, warning, success,
  information, discovery), decorative accents (tags, file types, avatar chips), or data
  visualization.
- **MUST** compose layouts on the 8px grid via the `spacing` scale (`space-100`, `space-200`, etc.
- **MUST** pair every elevated surface with its matching shadow token.
- **MUST NOT** imitate Material's depth theatre (floating cards everywhere), generic Shadcn/Tailwind
  neutral gradients, or marketing site branding; this system is for practical product UI.
- **SHOULD** prefer whitespace and borders for grouping before reaching for elevation.
- **SHOULD** let the canvas be mostly quiet so that color and motion (when used) carry actual
  signal.

## Colors

### Brand & Accent
- **Primary** (`{colors.primary}` — #111111): The dominant action color. All primary CTAs, h1/h2 display type. Press state shifts to `{colors.primary-active}` (#242424).
- **Brand Accent** (`{colors.brand-accent}` — #3b82f6): Used sparely on inline links and on a small badge / "Customer story" highlight. KolleK is a near-monochrome brand — the blue appears rarely.
- **Badge Pastels** — A small pastel set for category badges and avatar fills: `{colors.badge-orange}` (#fb923c), `{colors.badge-pink}` (#ec4899), `{colors.badge-violet}` (#8b5cf6), `{colors.badge-emerald}` (#34d399). These appear on tag pills and small accent moments inside product UI fragments — never on hero CTAs.

### Surface
- **Canvas** (`{colors.canvas}` — #ffffff): The default page floor.
- **Surface Soft** (`{colors.surface-soft}` — #f8f9fa): Nav-pill-group background, very-soft section dividers.
- **Surface Card** (`{colors.surface-card}` — #f5f5f5): Feature cards, testimonial cards, badge pills, default avatar fills.
- **Surface Strong** (`{colors.surface-strong}` — #e5e7eb): Hairline border alternative; disabled button background.
- **Surface Dark** (`{colors.surface-dark}` — #101010): The footer background — the only dark surface on every page. Also used for the featured pricing tier card.
- **Surface Dark Elevated** (`{colors.surface-dark-elevated}` — #1a1a1a): Used for nested cards inside the dark footer or featured pricing card.
- **Hairline** (`{colors.hairline}` — #e5e7eb): The 1px border tone on light surfaces. Used on input borders, table dividers, content card outlines (sometimes).
- **Hairline Soft** (`{colors.hairline-soft}` — #f3f4f6): A barely-visible divider used between sections that share the white canvas.

### Text
- **Ink** (`{colors.ink}` — #111111): All headlines and primary text.
- **Body** (`{colors.body}` — #374151): Default running-text color.
- **Muted** (`{colors.muted}` — #6b7280): Secondary text — sub-headings, breadcrumbs, footer body.
- **Muted Soft** (`{colors.muted-soft}` — #898989): Tertiary text — captions, fine-print, copyright lines.
- **On Primary / On Dark** (`{colors.on-primary}` / `{colors.on-dark}` — #ffffff): Text on primary buttons and dark footer.
- **On Dark Soft** (`{colors.on-dark-soft}` — #a1a1aa): Footer body text — slightly muted white for the link rows.

### Semantic
- **Success** (`{colors.success}` — #10b981): Confirmation states, success badges in product UI.
- **Warning** (`{colors.warning}` — #f59e0b): Warning callouts.
- **Error** (`{colors.error}` — #ef4444): Validation errors.

## Typography

### Font Family
The system runs **Inter** for everything. The fallback stack walks `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif` for both families.

### Hierarchy

| Token | Size | Weight | Line Height | Letter Spacing | Use |
|---|---|---|---|---|---|
| `{typography.display-xl}` | 64px | 600 | 1.05 | -2px | Homepage h1 ("The better way to schedule your meetings") — Inter |
| `{typography.display-lg}` | 48px | 600 | 1.1 | -1.5px | Section heads ("Your all-purpose scheduling app") — Inter |
| `{typography.display-md}` | 36px | 600 | 1.15 | -1px | Sub-section heads, card titles — Inter |
| `{typography.display-sm}` | 28px | 600 | 1.2 | -0.5px | CTA-band heads, pricing tier prices — Inter |
| `{typography.title-lg}` | 22px | 600 | 1.3 | -0.3px | Pricing plan names — Inter |
| `{typography.title-md}` | 18px | 600 | 1.4 | 0 | Feature card titles, intro paragraphs |
| `{typography.title-sm}` | 16px | 600 | 1.4 | 0 | Small card titles, list labels |
| `{typography.body-md}` | 16px | 400 | 1.5 | 0 | Default running-text |
| `{typography.body-sm}` | 14px | 400 | 1.5 | 0 | Footer body, fine-print |
| `{typography.caption}` | 13px | 500 | 1.4 | 0 | Badge labels, captions |
| `{typography.code}` | 14px | 400 | 1.5 | 0 | Code snippets, API examples — JetBrains Mono |
| `{typography.button}` | 14px | 600 | 1.0 | 0 | Standard button labels |
| `{typography.nav-link}` | 14px | 500 | 1.4 | 0 | Top-nav menu items |

### Principles
Inter is the brand voice — every display headline uses it. Inter handles the supporting type. The boundary is strict: never put body copy in never put a display headline in Inter. Inter without negative letter-spacing reads as off-brand — the -0.5 to -2px tracking is part of the voice.

Display weight stays at 600 across all sizes — never 700, never 500. The middle weight is what makes Inter feel modern and confident without becoming bombastic.

## Layout

### Spacing System
- **Base unit:** 4px.
- **Tokens:** `{spacing.xxs}` 4px · `{spacing.xs}` 8px · `{spacing.sm}` 12px · `{spacing.md}` 16px · `{spacing.lg}` 24px · `{spacing.xl}` 32px · `{spacing.xxl}` 48px · `{spacing.section}` 96px.
- **Section padding:** `{spacing.section}` (96px) — the universal vertical rhythm between editorial bands.
- **Card internal padding:** `{spacing.xl}` (32px) for feature cards and pricing tier cards; `{spacing.lg}` (24px) for testimonial and product-mockup cards.
- **Gutters:** `{spacing.lg}` (24px) between cards in 3-up grids; `{spacing.md}` (16px) inside footer columns.

### Grid & Container
- **Max content width:** ~1200px centered on marketing pages.
- **Editorial body:** Single 12-column grid; hero band often uses 7/5 split (h1 left, app mockup card right).
- **Feature card grids:** 3-up at desktop, 2-up at tablet, 1-up at mobile.
- **Pricing grid:** 4-up at desktop, 2-up at tablet, 1-up at mobile.
- **Footer:** 4-column link list at desktop, wrapping to 2-up at tablet, 1-up at mobile.

### Whitespace Philosophy
KolleK uses generous but not excessive whitespace — section padding sits at 96px (modern-SaaS standard), and card internal padding stays at 32px. The rhythm is calibrated for fast scanning: every band has a single h1 + h2 + supporting cards, never densely packed lists. The result reads as confident-not-shouting.

## Elevation & Depth

| Level | Treatment | Use |
|---|---|---|
| Flat | No shadow, no border | Body sections, top nav, hero bands |
| Soft hairline | 1px `{colors.hairline}` border | Inputs, table dividers, occasionally on cards |
| Card surface | `{colors.surface-card}` background — no shadow | Feature cards, testimonials |
| Subtle drop shadow | Faint shadow at low alpha | Pricing tier cards, hover-elevated states (the system uses `0 1px 2px rgba(0,0,0,0.05)` and `0 4px 12px rgba(0,0,0,0.08)`) |
| Featured tier | `{colors.surface-dark}` background, no shadow needed | The featured pricing tier inverts to dark surface — color contrast does the elevation work |

The elevation philosophy is **soft and modern** — small drop shadows on elevated cards, color-block contrast for emphasis. No heavy shadows, no neumorphism, no glassmorphism.

### Decorative Depth
- Calendar widgets and product UI fragments embedded inside marketing cards carry their own internal shadows from the product UI itself — these are not system tokens, they're product chrome shown as content.
- Avatar circles in testimonial sections sometimes carry pastel fill colors (`{colors.badge-orange}`, `{colors.badge-pink}`, etc.) — adds a small chromatic flourish without breaking the monochrome brand voice.

## Shapes

### Border Radius Scale

| Token | Value | Use |
|---|---|---|
| `{rounded.xs}` | 4px | Almost no use — reserved for badge accents |
| `{rounded.sm}` | 6px | Small inline buttons, dropdown items |
| `{rounded.md}` | 8px | Standard CTA buttons, text inputs, category tabs |
| `{rounded.lg}` | 12px | Content cards (feature cards, testimonial cards, pricing tier cards) |
| `{rounded.xl}` | 16px | Hero app-mockup card (a slightly larger radius for the marquee component) |
| `{rounded.pill}` | 9999px | Nav-pill-group, badge pills |
| `{rounded.full}` | 9999px / 50% | Avatars, icon buttons |

### Photography Geometry
Avatar photos use `{rounded.full}` (perfect circles) at 36px or 40px. Product UI fragments inside marketing cards retain their native chrome (which often has its own internal radii — e.g., calendar grid cells, button rows). Hero illustration zones use 16:9 or 4:3 ratios with `{rounded.xl}` corners.

## Components

### Top Navigation

**`top-nav`** — White nav bar pinned to the top of every page. 64px tall, `{colors.canvas}` background. Carries the KolleK wordmark + logo at left (the lowercase "KolleK" with the brand circle), primary horizontal menu (Product, Solutions, Resources, Pricing, Enterprise) center, right-side cluster with "Sign in" text-link, "Sign up free" `{component.button-primary}`, and a sometimes-visible language selector. Menu items in `{typography.nav-link}` (Inter 14px / 500).

**`nav-pill-group`** — A small pill-radius wrapper around 2-3 sub-nav segments (e.g., the product-mode switcher between "Personal" / "Teams" / "Enterprise"). Background `{colors.surface-soft}` with internal padding 6px, rounded `{rounded.pill}`. Active segment renders as a white-canvas pill with a subtle drop shadow inside the wrapper. The pill-in-pill treatment is one of KolleK's signature interactive components.

### Buttons

**`button-primary`** — The signature primary CTA. Background `{colors.primary}` (#111111), text `{colors.on-primary}`, type `{typography.button}` (Inter 14px / 600), padding 12px × 20px, height 40px, rounded `{rounded.md}` (8px). Active state `button-primary-active` shifts to `{colors.primary-active}` (#242424).

**`button-secondary`** — White button with hairline outline. Background `{colors.canvas}`, text `{colors.ink}`, 1px hairline border, same padding + height + radius as primary.

**`button-icon-circular`** — 36 × 36px circular icon button. Background `{colors.canvas}`, hairline border, ink-color icon. Used for share, "view more", carousel arrows.

**`button-text-link`** — Inline text button, no background. Used for "Sign in" in the top nav and inline CTA links inside cards.

**`text-link`** — Inline body links in `{colors.ink}` (the brand keeps inline links monochrome). Underlined on hover (not documented per the no-hover policy, but mentioned for context).

### Inputs & Forms

**`text-input`** — Standard text input. Background `{colors.canvas}`, text `{colors.ink}`, type `{typography.body-md}`, rounded `{rounded.md}` (8px), padding 10px × 14px, height 40px. 1px hairline border in `{colors.hairline}`.

**`text-input-focused`** — Focus state. Border thickens or shifts to `{colors.ink}` for emphasis.

### Tags / Badges

**`badge-pill`** — Small pill label used for category tags ("Product", "Article", "New") and pastel-fill avatar substitutes. Background `{colors.surface-card}` or one of the badge pastels (`{colors.badge-orange}`, `{colors.badge-pink}`, etc.), text `{colors.ink}`, type `{typography.caption}` (13px / 500), rounded `{rounded.pill}`, padding 4px × 12px.

**`avatar-circle`** — 36px diameter, rounded `{rounded.full}`. Either holds a photo or a pastel fill with initials in `{typography.caption}`.

**`rating-stars`** — Inline star rating in `{colors.badge-orange}` (#fb923c). Used near testimonial avatars to display a 5-star satisfaction score.

### Tab / Filter

**`category-tab`** + **`category-tab-active`** — Used inside the nav-pill-group. Inactive: transparent background, `{colors.muted}` text. Active: `{colors.canvas}` background, `{colors.ink}` text, subtle drop shadow inside the pill-group wrapper. Padding 8px × 14px, rounded `{rounded.md}`.

### CTA / Footer

**`cta-band-light`** — A pre-footer "Smarter, simpler scheduling" CTA card. Background `{colors.surface-card}`, rounded `{rounded.lg}`, padding `{spacing.xxl}` (48px). Carries an h2 in `{typography.display-sm}`, a sub-line, and a `{component.button-primary}` centered.

**`footer`** — Dark navy footer that closes every page. Background `{colors.surface-dark}` (#101010), text `{colors.on-dark-soft}`. 4-column link list at desktop covering Product / Solutions / Company / Resources. Vertical padding 64px. The KolleK wordmark sits at the top-left in `{colors.on-dark}`. The footer is the only dark surface on every page — the deliberate inversion visually closes the page.

## Do's and Don'ts

### Do
- Reserve `{colors.primary}` (#111111) for primary CTAs and h1/h2 type. KolleK's button is near-black, not blue.
- Use Inter for every display headline. Pair with Inter body. Never blur the boundary.
- Apply negative letter-spacing on display sizes (-0.5 to -2px). Inter without it reads as off-brand.
- Use `{component.feature-card}` (light gray) and `{component.product-mockup-card}` (white with chrome) deliberately — the gray cards signal "abstract feature claim", white cards signal "look at the actual product".
- Embed real product UI fragments inside marketing cards. Don't paint marketing illustrations of the product when you can show the product itself.
- Keep avatar circles at 36px, perfect circles, sometimes with pastel fills. Avatars are the only place where badge pastels appear.
- Use `{component.nav-pill-group}` for grouped sub-nav segments. The pill-in-pill treatment is signature.
- End every page with the dark footer. The light-to-dark transition is part of the editorial rhythm.

### Don't
- Don't use accent colors (`{colors.brand-accent}`, badge pastels) on primary CTAs. The system is monochrome at the action layer.
- Don't bold display weight beyond 600. Inter at 700 reads as bombastic.
- Don't use rounded radius beyond `{rounded.xl}` (16px) on cards. Larger radii read as consumer-app, not professional booking software.
- Don't put dark surface cards anywhere except the footer and the featured pricing tier. The dark surface is a deliberate, scarce signal.
- Don't repeat the same surface mode in two consecutive bands. KolleK's pacing alternates white → light-gray → white → product-mockup-card → white → dark-footer.
- Don't add hover state styling beyond what the system already encodes — primary darkens on press; nothing else changes.

## Responsive Behavior

### Breakpoints

| Name | Width | Key Changes |
|---|---|---|
| Mobile | < 768px | Hamburger nav; hero h1 64→32px; hero-app-mockup-card stacks below content; feature grids 1-up; pricing 1-up; footer 4 cols → 1 |
| Tablet | 768–1024px | Top nav stays horizontal but tightens; nav-pill-group wraps; feature cards 2-up; pricing 2-up |
| Desktop | 1024–1440px | Full top-nav with all menu items; 3-up feature cards; 4-up pricing tiers |
| Wide | > 1440px | Same as desktop with more outer breathing room; max content width caps at 1200px |

### Touch Targets
- `{component.button-primary}` at minimum 40 × 40px.
- `{component.button-icon-circular}` at exactly 36 × 36 — slightly under WCAG's 44 × 44 but the centered icon and full-circle silhouette compensate.
- `{component.text-input}` height is 40px.
- `{component.category-tab}` rendered inside nav-pill-group has 8 × 14 padding; effective tap area meets 44px+ with the surrounding pill.

### Collapsing Strategy
- Top nav collapses to hamburger at < 768px; menu opens as a full-screen sheet.
- Hero band's 7-5 grid collapses to single-column on mobile — h1 + sub-head + buttons first, then the app-mockup card below.
- Feature grids reduce columns rather than scaling cards down.
- Pricing tier cards collapse 4 → 2 → 1; featured-tier dark surface stays visually distinct at every breakpoint.
- Nav-pill-group wraps to multi-row on tablet if the segments don't fit horizontally.
