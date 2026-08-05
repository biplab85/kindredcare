# KindredCare — Homepage Redesign Plan

**Deliverable:** a single static `index.html` homepage, hand-built with SCSS, GSAP, Swiper and Fancybox.
**Status:** planning document. No code has been written and no project scaffolding created.
**Date:** 31 July 2026

---

## Table of Contents

1. [Scope](#1-scope)
2. [Strategic Basis](#2-strategic-basis)
3. [Audience & Design Principles](#3-audience--design-principles)
4. [Aesthetic Direction](#4-aesthetic-direction)
5. [Design System](#5-design-system)
6. [Homepage Information Architecture](#6-homepage-information-architecture)
7. [Section Specifications](#7-section-specifications)
8. [Library Assignment Map](#8-library-assignment-map)
9. [Motion Specification](#9-motion-specification)
10. [Technical Architecture](#10-technical-architecture)
11. [Responsive Strategy](#11-responsive-strategy)
12. [Accessibility Specification](#12-accessibility-specification)
13. [Performance Budget](#13-performance-budget)
14. [Content & Asset Checklist](#14-content--asset-checklist)
15. [Build Order](#15-build-order)
16. [Out of Scope & Open Decisions](#16-out-of-scope--open-decisions)

---

## 1. Scope

### In scope

- One page: `index.html` — the marketing homepage.
- A design system built to be reused by later pages (How It Works, Pricing, Trust & Safety, service category pages). The homepage is the first consumer of that system, not a one-off.
- SCSS compiled through Node.
- GSAP for scroll-triggered reveals and micro-animation.
- Swiper for all carousels.
- Fancybox for all modals and lightboxes.

### Out of scope for this build

- Backend, forms processing, authentication.
- The marketplace itself (browse, gig detail, booking) — that belongs in the Laravel + Next.js application.
- Any other page.
- Porting the existing WordPress theme. This is a fresh static build.

### Relationship to the existing properties

There are three separate things and they should not be confused:

| Property | Stack | Role after this work |
|---|---|---|
| `kindredcare.ca` (live) | WordPress + WooCommerce, 44 pages, 47 blog posts | Marketing site + blog. Homepage replaced by this build. |
| This build | Static HTML + SCSS + GSAP | The new homepage, later ported into the WordPress theme or kept static |
| `kindredcareapp` repo | Laravel 12 + Next.js 16 | The actual marketplace product. `Sign up` CTAs eventually point here. |

The 47 existing blog posts are a genuine SEO asset and must survive whatever happens to the theme.

---

## 2. Strategic Basis

### Authoritative document

`final-thoughts.md` is the governing strategy: **a Fiverr-style gig marketplace for senior care**. Caregivers publish productized gig listings; families browse, filter, rank by AI match, and book. Platform takes 7.5%.

`initial-thought.md` is research and its B2B recommendation was reversed. `platform-workflow.md` specs that abandoned B2B agency model and should **not** drive this design — only its EVV flow (GPS check-in → task log → check-out → visit summary) and its note that *EVV is not legally mandated in Canada, so it is a family-transparency feature and not a compliance one* carry over.

### What the audit of the live site found

These are the problems this redesign exists to solve.

| # | Finding | Consequence for the design |
|---|---|---|
| 1 | Every statistic renders `0` — including **"0% Verified care-giver"** | Remove all counters. No numeric claims until real data exists. |
| 2 | Services are presented as four agency blocks (In-Home, Specialized, Palliative, Long-Term Care) | Replace with the expanded gig taxonomy — the actual differentiator |
| 3 | The 7.5% fee appears only in the terms of use | Put the price comparison on the homepage |
| 4 | No "How It Works" anywhere | Add a 4-step section |
| 5 | Copy says "**our** caregivers… training", ToS says "we do not employ caregivers" | Rewrite to intermediary language throughout |
| 6 | Verification is a vague claim; the three named tiers are invisible | Add Basic / Trusted / Certified as a real section |
| 7 | Nothing is browsable — shop is empty and login-gated, 0 listings | Homepage must carry the marketplace feeling itself |
| 8 | No founders, no testimonials, no service area, no FAQ | Add all four |
| 9 | Hero leads on "AI Precision" | Lead on outcome and place; AI is a supporting proof point |

### The three things the homepage must communicate

1. **Broader than an agency** — companionship, storytelling, tech help, gardening, errands, not just personal care.
2. **Cheaper for families, better for caregivers** — 7.5% vs a 10–15% agency markup; caregiver keeps 92.5%.
3. **Safe** — named verification tiers, GPS-verified visits, payment through the platform, two-way ratings.

If a visitor leaves remembering only these three things, the page has done its job.

---

## 3. Audience & Design Principles

### Three audiences, one page

| Audience | Who | State of mind | What they need to see |
|---|---|---|---|
| **Primary — the adult child** | 45–65, usually a daughter, arranging care for a parent | Anxious, guilty, time-poor, comparison-shopping | Proof of safety, real prices, what happens step by step |
| **Secondary — the senior** | 65+, 72% report confidence with technology | Cautious about losing independence | Large legible type, warm human imagery, plain language |
| **Tertiary — the caregiver** | Students, new immigrants, retirees, PSWs | Evaluating whether this beats agency work | "Set your own rate, keep 92.5%, choose your gigs" |

The page is written for the adult child, must be **operable** by the senior, and must not lose the caregiver.

### Non-negotiable principles

1. **Legibility over fashion.** Base font size is **18px**, not 16px. No body copy under 17px anywhere, including footer and legal.
2. **No dark patterns, no invented numbers.** Zero fabricated testimonials, listings, or stats. This is the exact behaviour Care.com was FTC-fined for, and our audience is the most vulnerable one there is.
3. **Motion never blocks content.** No scroll-jacking, no content that requires scrolling to become readable, nothing that moves while being read.
4. **Every trust claim is specific.** Not "background checks" but "CPIC criminal record check via Certn, plus Vulnerable Sector Check for personal care."
5. **Warm, not clinical.** The expanded taxonomy is about ordinary life — gardening, tea, photographs, a walk. The design should feel domestic, not medical.

---

## 4. Aesthetic Direction

### The concept: *Editorial Warmth*

The reference point is a well-made Canadian lifestyle magazine, not a healthcare portal and not a SaaS dashboard.

Every competitor in this space looks identical: clinical white background, cool blue, stock photograph of a smiling nurse with a clipboard, rounded sans-serif. Visiting Angels, Comfort Keepers and Care.com are visually interchangeable. Looking like them is the cheapest way to be dismissed as another agency — which is precisely the thing our strategy says we are not.

**The direction:**

- **Paper, not screen.** A warm bone/off-white ground with a faint paper grain, instead of clinical `#FFFFFF`. Sections alternate between bone and a warm sand tint. This single choice separates us from every competitor immediately.
- **Editorial typography.** A characterful humanist serif for display, set large and confident, paired with the brand's DM Sans for interface and body. Real typographic hierarchy — not everything at 24px bold.
- **Asymmetry and overlap.** Hero image sits offset and overlaps the type block. Section headings break the grid. The service grid is a deliberate bento composition, not twelve identical squares.
- **Photography of ordinary life.** Hands in soil, a tablet being shown, a kitchen table, a walk on a Durham street in autumn. Never a clipboard, never scrubs, never a stethoscope.
- **Restraint in motion.** Slow, soft, short. The page should feel calm and considered — the emotional opposite of the panic the visitor arrived with.

### Reference: Sekare (EnvyTheme)

Two demos supplied as inspiration, inspected in-browser on 31 July 2026.

- **Hero reference** — `themes.envytheme.com/sekare/home-demo-three/`
- **Animation reference** — `themes.envytheme.com/sekare/home-demo-four/`

**What the hero reference actually is**

| Element | Sekare demo three |
|---|---|
| Layout | Asymmetric split — warm cream type panel left (~48%), full-bleed photograph right, bleeding to the viewport edge |
| Eyebrow | Small teal label above the headline (`Senior care`) |
| Headline | Large **Playfair Display** serif, dark navy, two lines |
| Sub-headline | Inter, grey, two lines, short |
| CTA | Single solid amber button, **square corners**, with a `↗` arrow glyph |
| Stat cards | Two small cards under the CTA — thumbnail image + large serif number + label (`10+ Winning awards`, `20+ Years of services`), soft pastel backgrounds |
| Slider | Hero is a carousel; teal square arrows overlap the photo's bottom edge |

**Verdict: the structure validates this plan and should be adopted; the content model must not be.**

The bones of demo three are close to what §4 already specifies — warm non-white ground, serif display, asymmetric split, photograph bleeding off the edge, overlapping card elements breaking the grid. That is genuine confirmation the direction is right, and the layout is worth following closely.

| Adopt | Adapt | Reject |
|---|---|---|
| Asymmetric split, cream panel + edge-bleed photo | **Stat cards → "starting rate" cards** (below) | **The stat cards' content model.** `10+ Winning awards`, `20+ Years of services`, `20k+ Happy customer`, `100% Home safety` — we have none of these. Copying this device is precisely how the live site ended up publishing "0% Verified care-giver". |
| Small eyebrow label above the headline | Amber CTA → Heart Red (brand) | **Clinical photography.** Both demos lead with a nurse in scrubs with a stethoscope. Wrong for a gig marketplace whose differentiator is gardening, storytelling and tech help. |
| Large serif display headline, two lines | Square corners → `$radius-sm`, softened slightly | **Hero carousel.** A rotating headline splits the single proposition a first-time visitor needs, and autoplay conflicts with §12. Our hero stays static. |
| Overlapping cards that break the grid | Playfair Display → **Fraunces** (below) | **"Donate" / charity framing.** Sekare is a charity theme. We are a marketplace. |
| Photograph bleeding to the viewport edge | — | Inter for body — brand font is DM Sans |

**The stat-card slot, repurposed.** The two small cards under the CTA are a good visual device and we keep the slot — but filled with something true and on-strategy instead of invented numbers:

> `Companionship — from $22/hr` · `Tech help — from $25/hr`

Same composition, same visual weight, but it now carries the two differentiators (expanded taxonomy + transparent pricing) instead of unverifiable brags. Honest, and strictly better positioned.

**On Playfair vs Fraunces.** Playfair Display is what produces the look being responded to, and it is a defensible choice. Fraunces stays the recommendation for one concrete reason: Playfair is a high-contrast Didone with very thin hairlines, which thin further at smaller sizes and reduce legibility for older readers. Fraunces gives the same warm editorial serif character with lower stroke contrast. If the team prefers Playfair after seeing both, restrict it strictly to `--fs-display` and `--fs-h2` and never below.

### Reference: the animation style (demo four)

Inspected directly. The result is simpler than expected, and good news for this build:

| Finding | Detail |
|---|---|
| **Library** | `scrollCue.min.js` — a ~3KB scroll-reveal library. **No GSAP, no AOS, no WOW.js.** |
| **Elements animated** | 36 total |
| **Animation types** | Exactly **two**: `slideInLeft` (24) and `slideInUp` (12) |
| **Grouping** | `data-cues` on parent containers auto-staggers direct children |
| **Timing** | All defaults — no custom `data-duration` or `data-delay` anywhere |
| Other libs | Swiper, Magnific Popup, jQuery, Bootstrap, Elementor, WooCommerce |
| Fonts | Playfair Display + Inter |

**What this means.** The "animation style" being asked for is restrained staggered scroll-reveal — two motions, default timing, applied consistently. That is almost exactly what §9 already specifies, and it needs no new dependency: GSAP ScrollTrigger is already in this build for the header, counters, accordion and parallax, and reproduces both cues precisely. Adding scrollCue alongside GSAP would mean two animation libraries doing one job.

**One deliberate deviation.** Sekare uses `slideInLeft` on 24 of 36 elements — horizontal motion as the default. We invert that ratio. Horizontal entry is more disruptive to read against, particularly at 200% zoom where a 30px left-offset is a large proportion of the viewport. Our default is `slideInUp`; horizontal entry is reserved for the hero type block and the split sections (`09`), where it reinforces the layout's direction rather than fighting the reading line.

### The one memorable thing

**The service taxonomy section.** A visitor who has spent an hour on agency sites reading "personal care / meal prep / light housekeeping" over and over hits a grid that offers *storytelling*, *conversation in your mother's first language*, *teaching her to video call the grandchildren*, *someone to garden with*, *a companion so she isn't alone at Christmas*. That is the emotional turn of the whole page, and the design should give it the most space and the best photography.

---

## 5. Design System

### 5.1 Colour

Brand colours are fixed by `frontend/src/app/globals.css` and must match so the marketing site and the app read as one brand. oklch is the source of truth; hex values below are approximations and **must be regenerated from the real tokens** during setup.

```scss
// Brand — locked, from globals.css
$trust-blue:    oklch(0.56 0.13 240);  // ≈ #2C6EB5 — interactive, links, focus
$heart-red:     oklch(0.60 0.20 25);   // ≈ #E04A2F — primary CTA, care moments
$leaf-green:    oklch(0.58 0.14 155);  // ≈ #2E9464 — verified / cleared states ONLY

// Warm neutral ground — new, defines the editorial character
$bone:          #FAF7F2;   // page background
$sand:          #F2EDE4;   // alternating section background
$clay:          #E5DACB;   // borders, dividers, card edges
$ink:           #1A1F26;   // body text — warm near-black
$ink-soft:      #4A5058;   // secondary text
$ink-inverse:   #FAF7F2;   // text on dark sections

// Dark section — used once, for the caregiver recruitment block
$deep:          #14202E;   // deep warm navy
```

**Usage discipline**

- Leaf Green is *only* for verification and confirmed states. Never decorative. Its meaning is the whole point.
- Heart Red is the CTA colour and appears sparingly — hero CTA, final CTA, and price highlight. Overuse destroys it.
- Trust Blue carries links, focus rings, and interactive states.
- One dark section on the page only (caregiver recruitment). More than one and the paper concept collapses.

### 5.2 Typography

```scss
// Display — characterful humanist serif, warm not stuffy
// Candidate: Fraunces (variable, has optical + soft axes)
// Alternates: Newsreader, Instrument Serif, Source Serif 4
$font-display: 'Fraunces', Georgia, serif;

// Body / UI — brand font, high x-height, excellent at large sizes
$font-body: 'DM Sans', -apple-system, sans-serif;
```

Do not use Inter, Roboto, Arial, Poppins, Montserrat, or system-ui as a display face. Self-host both families as WOFF2 with `font-display: swap`; do not load from Google's CDN.

**Fluid scale** — base **18px**, ratio ~1.25 mobile / ~1.333 desktop.

| Token | Clamp | Face | Use |
|---|---|---|---|
| `--fs-display` | `clamp(2.75rem, 6vw, 5rem)` | Display | Hero headline only |
| `--fs-h2` | `clamp(2rem, 4vw, 3.25rem)` | Display | Section headings |
| `--fs-h3` | `clamp(1.5rem, 2.5vw, 2rem)` | Display | Card titles, sub-headings |
| `--fs-lead` | `clamp(1.25rem, 2vw, 1.5rem)` | Body | Hero sub-headline, section intros |
| `--fs-body` | `1.125rem` (18px) | Body | Default body copy — **the floor** |
| `--fs-sm` | `1rem` (16px) | Body | Captions, labels, footer. Never smaller. |

Line height: 1.15 display, 1.65 body. Measure capped at **68ch**. Never justify.

### 5.3 Spacing, radius, elevation

```scss
// 8px base, exponential
$space: (1: 4px, 2: 8px, 3: 12px, 4: 16px, 5: 24px, 6: 32px,
         7: 48px, 8: 64px, 9: 96px, 10: 128px, 11: 160px);

// Section rhythm — generous, editorial
$section-pad-y: clamp(80px, 10vw, 160px);

// Mobile app chrome (below 768px only)
$bottom-bar-h:   64px;
$bottom-bar-fab: 56px;   // raised centre action
$safe-bottom:    env(safe-area-inset-bottom, 0px);
$sheet-radius:   20px;   // bottom-sheet top corners

// Radius — soft but not pill-shaped; pills read as consumer-app, not considered
$radius-sm: 6px;
$radius-md: 12px;
$radius-lg: 20px;
$radius-img: 4px;   // photography stays nearly square — editorial, not bubbly

// Elevation — warm-tinted shadows, never neutral grey/black
$shadow-sm: 0 1px 2px rgba(26, 31, 38, 0.06);
$shadow-md: 0 4px 16px rgba(26, 31, 38, 0.08);
$shadow-lg: 0 16px 48px rgba(26, 31, 38, 0.10);
```

### 5.4 Grid & container

- 12-column grid, 24px gutter mobile / 32px desktop.
- Container max **1440px**; reading blocks max **68ch**.
- The wider container applies to layout only. Text blocks stay capped at 68ch regardless — a 1440px-wide paragraph is unreadable, and this audience least of all. Sections 06 (pricing), 12 (FAQ) and all body copy keep the narrower measure and centre within the container.
- A `--bleed` utility for full-width photographic and dark sections.
- Deliberate grid-breaks are allowed and encouraged in hero, taxonomy and founders — but the underlying grid must exist to break.

### 5.5 Texture

- Very fine noise/grain overlay on the bone background (inline SVG turbulence, ~2–3% opacity). This is what makes it read as paper rather than as beige. It must be subtle enough that no one consciously notices it.
- No gradients except one soft radial warm-light behind the hero image.
- **Explicitly forbidden:** purple gradients, glassmorphism, neon, glow, dark-mode-by-default.

---

## 6. Homepage Information Architecture

**14 blocks ship at launch: 11 content sections between header and footer, plus a mobile-only bottom app bar.** Two further sections are built but held back (see [Sections cut or held](#sections-cut-or-held-during-rationale-review)).

Section numbers are stable identifiers, not sequence positions. `02` is retired and its number is not reused, so every reference elsewhere in this document stays valid.

| # | Section | Status vs today | Ships at launch? | Data available now? |
|---|---|---|---|---|
| 00 | Header / navigation | rebuild | ✅ | ✅ |
| 01 | Hero | rewrite | ✅ | ✅ |
| ~~02~~ | ~~Trust bar~~ | **cut** — absorbed into 01 | ❌ | — |
| 03 | How It Works — 4 steps | **new** | ✅ | ✅ |
| 04 | Expanded service taxonomy | **new** (replaces 4 agency blocks) | ✅ | ✅ |
| 05 | Founding caregivers / Featured caregivers | **new** | ✅ | ⚠️ 0 listings — recruitment variant |
| 06 | Price comparison — 7.5% | **new** | ✅ | ✅ |
| 07 | Verification tiers | **new** | ✅ | ✅ |
| 08 | Safety & visit verification | **new** | ✅ | ✅ |
| 09 | Caregiver recruitment (dark) | **new** | ✅ | ✅ |
| 10 | Founders + why we're building this | **new** | ✅ | ⚠️ needs photos + bios |
| 11 | Testimonials | **new** | ⏸ **held** | ❌ none exist |
| 12 | FAQ | **new** | ✅ | ✅ |
| 13 | Final CTA + service area | **new** | ✅ | ✅ |
| 14 | Footer | rebuild | ✅ | ✅ |
| 15 | Mobile bottom app bar | **new** — mobile/tablet only, hidden ≥768px | ✅ | ✅ |

### Business goals every section is measured against

No section exists for its own sake. Each must serve at least one of these five goals, drawn from `final-thoughts.md`.

| ID | Goal | Why it matters |
|---|---|---|
| **G1** | **Acquire families** (demand) in Durham Region | Year 1 target is 50–100 active families |
| **G2** | **Acquire caregivers** (supply) | Two-sided cold start is the **HIGH** risk in the strategy's own risk table. Supply must lead demand. |
| **G3** | **Establish trust** | The precondition for G1 and G2. Vulnerable users, strangers entering homes. |
| **G4** | **Differentiate** from agencies and Care.com | Expanded taxonomy + 7.5% pricing. Without this we are a worse-funded Visiting Angels. |
| **G5** | **Earn organic acquisition** | Marketing budget is $4–6k/month. Paid alone cannot carry this; SEO must. |

### Why each section exists

| # | Purpose — the one job it does | Problem it solves | Goals | Measured by |
|---|---|---|---|---|
| **00** | Orient and give both audiences a permanent route to signup | Current nav offers no path to understanding before committing to an account | G1 G2 | CTA click-through by side (family vs caregiver) |
| **01** | Answer "what is this and is it for me?" in under 5 seconds | Current hero leads on "AI Precision" — a phrase no worried family searches for. States neither what we do nor where. | G1 G3 G4 | Bounce rate; % of visitors who scroll past the hero |
| **03** | Remove procedural uncertainty — show the whole transaction before asking for a signup | Audit #4: no "How It Works" exists. Families won't create an account to find out how a thing works. | G1 G3 | Scroll-through rate; drop-off at this section |
| **04** | Prove we are not an agency | Audit #2: the site sells four agency service blocks. The expanded taxonomy — the differentiator the entire TAM argument rests on — appears nowhere. | G1 G4 G5 | Category modal opens; clicks to browse; organic entries to category pages (Phase 2) |
| **05** | Recruit founding supply now; prove liquidity later | Cold start (G2). With 0 listings there is nothing to book, so this slot must *build* supply before it can *display* it. | G2 → G1 | Caregiver signup starts from this section |
| **06** | Make the cost advantage explicit and comparable | Audit #3: the 7.5% fee appears only in the terms of use. We researched competitor rates in our own blog and then declined to state ours. | G1 G4 | Scroll depth to this section; CTA clicks immediately after it |
| **07** | Convert a vague safety claim into a checkable one | Audit #6: "background checks" is exactly what Care.com says. Undifferentiated trust language is worthless. | G3 G4 | Tier modal opens |
| **08** | Answer the objection 07 does not: what happens *during* the visit | Vetting and supervision are two separate fears. 07 covers who we let in; 08 covers what happens once they are inside. | G3 | Section reach; video plays if produced |
| **09** | Sell the caregiver side properly, in its own voice | The current site gives the supply side one nav button. A two-sided marketplace that only markets to one side does not start. | G2 | Caregiver signup starts; scroll depth |
| **10** | Transfer credibility from the team to the platform | A COO with an MBBS, PhD and MPH is the strongest trust asset the company owns, and it is on no page of the live site. Competitors cannot copy it. | G3 | Bio modal opens; time on section |
| **12** | Handle the objections that block conversion, and earn search traffic | Audit #8: no FAQ. Also the cheapest durable SEO on the page via `FAQPage` schema. | G1 G3 G5 | Accordion opens per question; Search Console impressions |
| **13** | Convert, and state where we actually operate | Audit #8: geography appears only as "Oshawa, Ontario" in the footer, while hyper-local Durham launch is the stated strategy | G1 G2 G5 | Signup starts; email captures |
| **14** | Utility, legal compliance, secondary navigation | Required. Also carries the live "Send your qurries" typo fix. | — | Outbound link clicks |
| **15** | Keep the primary action reachable through a long page on the device most visitors use | A 13-section page on mobile buries every CTA. Bottom bar keeps conversion one tap away at all times. | G1 G2 | FAB tap rate; conversion rate mobile vs desktop |

### Making the metrics real

A "Measured by" column is worthless unless it is instrumented at build time. Every section ships with tracking, added during its own build step — not retrofitted.

| What | How |
|---|---|
| Section reach | GA4 scroll-depth events fired from the existing shared `reveal.js` ScrollTrigger — one event per section, fired once |
| CTA clicks | `data-cta` attribute on every button, carrying section id + side (`family` \| `caregiver`) |
| Modal opens | Fancybox `on: reveal` → event with the modal's category or tier id |
| Carousel engagement | Swiper `slideChange`, capped at one event per session per instance |
| FAQ | Accordion open event per question — the ranked list tells us which objection dominates |
| Outcome | Signup starts, split by side and by originating section |

**The review question at 30 and 90 days:** does any section have high reach but near-zero engagement or onward clicks? That section is costing scroll length and returning nothing — cut it or rewrite it. This document should be revised against real data, not defended.

### Sections cut or held during rationale review

Applying the "no section without a meaningful reason" test to this plan's own draft removed two sections.

**❌ `02` Trust bar — cut, absorbed into `01`.**
It duplicated work already done twice. The hero already carries an inline micro-trust row (*Background-checked · GPS-verified visits · Pay through the platform*), and section `07` covers verification in real depth. A separate four-icon band between them added a scroll-length cost and no new information. Its content stays — as the hero row, which is where a scanner sees it soonest anyway. Section `07` remains the place where the claim is substantiated.

**⏸ `11` Testimonials — built, not rendered at launch.**
There are zero reviews, and fabricating them is forbidden (§3, principle 2). The earlier draft filled the slot with a "Why we're building this" founders' story — but that is the same job section `10` does, with the same people. Two founder sections is padding.

Resolution: `10` absorbs the founders' story as its section intro. `11` is built as a component and held out of the page until real reviews exist, at which point it renders between `10` and `12` with no rebuild.

**The general rule this establishes:** a section that can only be filled with placeholder content, and whose placeholder duplicates a neighbouring section, does not ship. An empty slot is more honest than a padded one, and a shorter page converts better than a longer one.

### Removed from the current homepage

| Removed | Reason |
|---|---|
| Stats counter block (`0+ Active members`, `0% Verified care-giver`, `0% AI-Powered matching`, `0% Accuracy of all information`, `0/7 Customer support`) | Every value is zero. "0% Verified care-giver" is worse than showing nothing. Deleted entirely, not repaired. |
| Palliative Care block | Highest-liability service on the homepage of a company with no track record. `final-thoughts.md` sequences it to Phase 2 marketing. |
| Long-Term Care block | B2B copy on a B2C homepage. Nothing to buy — no operator portal, no $240/mo price. Moves to a dedicated page later. |
| Features trio (Seamless Onboarding / Manage everything at one place / AI-Powered Matching) | Describes our operational convenience, not the visitor's benefit. Superseded by section 03. |

### Sections constrained by missing data

There are 0 products in the store and 16 accounts, mostly staff and test. Two sections are affected, and they are handled differently:

- **05 — ships, with a different job.** At launch it is *"Be one of our founding caregivers — Durham Region"*: a supply-acquisition block using the same Swiper shell. It earns its place immediately because cold-start supply (G2) is the higher priority. When listings exist, the same shell displays real gig cards.
- **11 — does not ship.** No reviews exist and its placeholder duplicated section `10`. Built as a component, held out of the page.

Under no circumstances do we invent caregiver cards or testimonials.

---

## 7. Section Specifications

### 00 — Header

| | |
|---|---|
| **Layout** | Logo left · nav centre · dual CTA right. Mobile: logo + hamburger → full-screen drawer. |
| **Nav** | How It Works · Services · Pricing · Trust & Safety · Blog · About |
| **CTAs** | `Find a caregiver` (Heart Red, filled) · `Join as a caregiver` (Trust Blue, outline) |
| **Behaviour** | Transparent over hero → on 80px scroll, condenses: bone background, shadow, reduced height. GSAP ScrollTrigger. |
| **Libraries** | GSAP (ScrollTrigger) |
| **Notes** | Both CTAs visible on mobile — a two-sided marketplace cannot hide one side behind a hamburger. |

### 01 — Hero

| | |
|---|---|
| **Headline** | Outcome + place, not "AI Precision". Working copy: *"Kind, verified help for the people who raised us."* Sub-line names Durham Region and the breadth of services. |
| **Sub-headline** | *"From a hand in the garden to a conversation in her first language to personal care — book trusted local caregivers by the hour. You pay 7.5%, not an agency's 15%."* |
| **Layout** | Asymmetric split, following the Sekare demo-three structure: type panel left on `$bone` (~48%), photograph right bleeding to the viewport edge (~52%). Soft warm radial behind the image. |
| **Eyebrow** | Small Trust Blue label above the headline — e.g. `Senior care in Durham Region` |
| **Rate cards** | Two small cards below the CTA, occupying Sekare's stat-card slot: `Companionship — from $22/hr` · `Tech help — from $25/hr`. Thumbnail + rate + label, soft tinted backgrounds, breaking the grid edge. **Never invented statistics.** |
| **Elements** | Location pill (`Now serving Durham Region, Ontario`) · headline · sub-headline · dual CTA · **absorbed trust row** (below) |
| **Absorbed trust row** | Four items, icon + short label, sitting directly beneath the CTAs: *Certn background checks* · *Identity verified* · *GPS-verified visits* · *Payment held by the platform*. Carries the job of the cut section `02`. **No numbers** — this exists because the all-zero counters were removed, and it sells specificity instead of scale. |
| **Libraries** | GSAP timeline on load |
| **Notes** | **No carousel** — unlike the Sekare reference. A rotating headline splits the single proposition a first-time visitor needs, and autoplay conflicts with §12. No video background. The single most important thing is that the headline is readable within 200ms. |

### ~~02 — Trust bar~~ *(cut — absorbed into 01)*

Removed during rationale review: it duplicated the hero's trust row and pre-empted section `07`. Number retired, not reused.

### 03 — How It Works

| | |
|---|---|
| **Content** | Compressed from the 9-step family journey in `final-thoughts.md`: **1** Tell us who you're caring for · **2** Browse gigs ranked for them · **3** Book the date, time and address · **4** They check in, you're notified, you pay through us |
| **Layout** | Desktop: 4 columns with a drawn connecting line. Mobile: Swiper, 1.15 slides visible, pagination dots. |
| **Libraries** | **Swiper** (mobile) · **GSAP** (line draw on scroll, step stagger) |
| **Notes** | A parallel toggle for the caregiver flow is a Phase 2 addition — do not build it now. |

### 04 — Expanded service taxonomy ⭐

| | |
|---|---|
| **Content** | 10–12 categories from `final-thoughts.md`: Companionship · Storytelling & reading aloud · Conversation in your language · Technology help · Errands & groceries · Transportation · Gardening · Meal prep for dietary needs · Walking companion · Paperwork & admin help · Personal care · Dementia & Alzheimer's care |
| **Layout** | Bento grid — deliberately uneven. Two or three feature tiles carry photography and a short line of copy; the rest are compact tiles with an icon and label. Breaks the 12-col grid on purpose. |
| **Interaction** | Tile click → **Fancybox** modal: category description, typical hourly range, what's included, required verification tier, CTA to browse. |
| **Layout (mobile)** | Grid collapses to 2-up; feature tiles become full width. |
| **Libraries** | **Fancybox** (category detail) · **GSAP** (scroll-triggered stagger) |
| **Notes** | This is the page's centrepiece and gets the largest vertical allowance, the best photography, and the most careful copy. Section heading should name the contrast directly — e.g. *"Agencies offer six services. We offer forty."* |

### 05 — Founding caregivers → Featured caregivers

| | |
|---|---|
| **Job at launch** | **Supply acquisition.** *"Be one of our founding caregivers in Durham Region"* — benefit list, single CTA. Serves G2, the strategy's highest-severity risk. |
| **Job later** | **Liquidity proof.** Swiper carousel of gig cards: caregiver photo, name + initial, headline, verification badge, hourly rate, rating, top 2 skills. Serves G1. |
| **Libraries** | **Swiper** (plain slide, 1.15 / 2.5 / 3.5 per view) |
| **Notes** | Same section shell and Swiper container in both states, so the transition is a data change and not a rebuild. Build the gig card component fully now. **Never populate with fabricated caregivers.** |

### 06 — Price comparison

| | |
|---|---|
| **Content** | Side-by-side, from `final-thoughts.md`: **Traditional agency** — family pays $30–35/hr, caregiver receives $18–22, caregiver keeps 60–70% · **KindredCare** — family pays $25/hr, we take $1.88 (7.5%), caregiver receives $23.12, keeps 92.5% |
| **Layout** | Two panels; ours visually elevated (Heart Red accent, elevation, slight scale). A worked example strip underneath. |
| **Libraries** | **GSAP** (number count-up when scrolled into view, once) |
| **Notes** | Real figures only, sourced from our own blog research. Small print: *"Rates are set by caregivers, not by us."* This is the single highest-value addition on the page — the argument currently exists only in the terms of use. |

### 07 — Verification tiers

| | |
|---|---|
| **Content** | Three cards — **Basic → Verified**, **Enhanced → Trusted**, **Professional → Certified** — each listing requirements and which services it unlocks, exactly per `final-thoughts.md`. |
| **Interaction** | "What's checked?" → **Fancybox** modal with the full checklist (CPIC, Vulnerable Sector Check, eKYC, references, Video KYC, AML). |
| **Layout** | Three cards, ascending visual weight left→right. Leaf Green badges only here and in section 02. |
| **Libraries** | **Fancybox** · **GSAP** (staggered reveal) |
| **Notes** | Converts a vague claim into a checkable one. This is our answer to Care.com. |

### 08 — Safety & visit verification

| | |
|---|---|
| **Content** | GPS check-in and check-out · arrival notification to family · optional live location sharing · tasks logged during the visit · two-way ratings · in-app messaging kept on record · emergency button |
| **Layout** | Split — device mockup or illustrated visit timeline left, feature list right. |
| **Interaction** | Optional 60–90s explainer video in a **Fancybox** lightbox. |
| **Libraries** | **Fancybox** (video) · **GSAP** (timeline draw) |
| **Notes** | Position as family transparency, **not** compliance — EVV is not mandated in any Canadian province (`platform-workflow.md`). Claiming otherwise is inaccurate. |

### 09 — Caregiver recruitment *(the one dark section)*

| | |
|---|---|
| **Content** | *"Set your own rate. Choose your own gigs. Keep 92.5% of what you earn."* Supporting points: work your own schedule, get paid automatically, build a Trust Score, no agency taking 30–40%. |
| **Layout** | Full-bleed `$deep` navy, inverse type, photograph bleeding off one edge. |
| **CTA** | `Join as a caregiver` |
| **Libraries** | GSAP fade + subtle parallax on the photograph |
| **Notes** | The tonal break. It signals *this is a marketplace with two sides*, which is the thing the current site fails to say at all. |

### 10 — Founders

| | |
|---|---|
| **Section intro** | *"Why we're building this"* — 2–3 short paragraphs of founders' story, absorbed from the cut placeholder variant of `11`. Gives the portraits narrative context instead of presenting them as a bare team grid. |
| **Content** | Four portraits: CEO Sarmin Akter (10 yrs HR/social welfare) · COO Dr. Tanzila Rawnuck (MBBS, PhD, MPH) · CFO MD Jahangir Alom (MBA, 13 yrs finance) · CTO (20 yrs technology) |
| **Interaction** | Portrait click → **Fancybox** modal with full bio. |
| **Layout** | Editorial 4-up, generous white space, name + role in display face. |
| **Libraries** | **Fancybox** · **GSAP** |
| **Notes** | A COO with an MBBS and a PhD is the strongest trust signal the company owns and it appears nowhere on the current site. Real photographs — no illustrated avatars, no stock. |

### 11 — Testimonials ⏸ *(built, held out of the page)*

| | |
|---|---|
| **Content** | Swiper of real reviews: quote, first name + last initial, service used, town. |
| **Ships when** | Real reviews exist. Renders between `10` and `12`. |
| **Libraries** | **Swiper** |
| **Notes** | Held rather than placeholder-filled: its draft placeholder duplicated `10`. Zero fabricated reviews. Not one. Build the component and the slot; do not render the section. |

### 12 — FAQ

| | |
|---|---|
| **Content** | Are caregivers background checked? · What if we don't get along? · How does payment work and when is the caregiver paid? · Are you an agency? · Who is responsible if something goes wrong? · Do you serve my area? · Can we book the same person every week? |
| **Layout** | Single column, max 68ch, accordion. |
| **Markup** | Native `<details>`/`<summary>` so it works without JS, height animated with GSAP. |
| **Libraries** | **GSAP** |
| **Notes** | Add `FAQPage` JSON-LD. The "Are you an agency?" answer must align with the terms of use — we are an intermediary and do not employ caregivers. |

### 13 — Final CTA + service area

| | |
|---|---|
| **Content** | *"Now serving Durham Region — Oshawa, Whitby, Ajax, Pickering, Clarington"*, dual CTA, plus a low-commitment "not ready yet" email capture. |
| **Layout** | Centred, sand background, optional simple map or town list. |
| **Notes** | The current site states its geography nowhere except an "Oshawa, Ontario" line in the footer. Hyper-local launch is the stated strategy — say it. |

### 14 — Footer

| | |
|---|---|
| **Content** | Logo + one-line description · nav columns (Company / For Families / For Caregivers / Legal) · contact `+1 (437) 665-8486`, `info@kindredcare.ca`, Oshawa, Ontario · LinkedIn, Facebook · copyright `© 2026 KindredCare Global Inc.` |
| **Fixes carried over** | Correct the live typo "Send your qurries" wherever contact copy is reused. Add Instagram and YouTube placeholders — the adult-daughter audience is on both. |
| **Notes** | Footer body text stays at 16px minimum. |

### 15 — Mobile bottom app bar ⭐ *(below 768px only)*

Below `md`, the page stops behaving like a shrunken desktop site and behaves like an app. The bottom bar is the anchor of that shift.

| | |
|---|---|
| **Layout** | Fixed bottom, `$bottom-bar-h` (64px) + `$safe-bottom`. Five slots: 2 · raised centre action · 2. Bone background, 1px `$clay` top hairline, soft upward shadow. |
| **Slots** | `Services` · `Pricing` · **[ Find care ]** · `Safety` · `Menu` |
| **Centre action** | Heart Red circular FAB, 56px, raised ~14px above the bar with a cut-out shadow. Label `Find care` beneath. Scrolls to section 04 or links to the marketplace once it exists. |
| **Active state** | Scroll-spy — the slot for the section currently in view is highlighted (Trust Blue icon + label, 2px top indicator). Driven by the existing GSAP ScrollTrigger setup. |
| **Appearance** | Hidden while the hero is in view; slides up once the hero scrolls past. The hero carries its own large CTAs, so showing both is redundant and covers content on the shortest screens. |
| **Persistence** | Once shown, it **stays**. No hide-on-scroll-down / reveal-on-scroll-up. Disappearing chrome is disorienting for this audience and makes the primary CTA feel unreliable. |
| **`Menu` slot** | Opens a **bottom sheet**, not a side drawer — a side drawer triggered from a bottom bar is a mismatch. Sheet contains full nav, `Join as a caregiver`, phone number, and language toggle placeholder. |
| **Libraries** | GSAP (reveal + scroll-spy) |

**How the two sides of the marketplace stay visible**

The plan's rule is that a two-sided marketplace cannot hide one side behind a hamburger. The bottom bar resolves this by splitting the audiences across the two chrome bars:

| Chrome | Audience | Persistent CTA |
|---|---|---|
| Top header (condensed) | Caregiver — secondary | `Join as a caregiver` stays as a visible text link |
| Bottom app bar | Family — primary | `Find care` FAB |

Both sides are always one tap away, and neither is buried.

**Body offset:** `body { padding-bottom: calc(#{$bottom-bar-h} + #{$safe-bottom}); }` below `md`, so the footer and final CTA are never obscured.

### Mobile app-style patterns (applies across all sections below `md`)

The bottom bar only works if the rest of the mobile experience matches it. These patterns are what separate "modern app" from "responsive website":

| Pattern | Where | Detail |
|---|---|---|
| **Bottom sheets replace centred modals** | All Fancybox instances | Slide up from bottom, `$sheet-radius` top corners, drag handle, `overscroll-behavior: contain`. Centred dialogs read as web; sheets read as app. |
| **Peeking carousels** | 03, 05, 11 | `slidesPerView: 1.15` so the next card is visibly cut off — signals swipeability without an instruction |
| **Horizontal chip row** | 04 taxonomy | Scrollable category chips above the grid, like an app filter row. Momentum scroll, snap, no scrollbar. |
| **Press feedback, not hover** | All interactive | `:active` scale `0.97` + opacity shift at 120ms. Hover states are desktop-only — never rely on them below `md`. |
| **Safe-area aware** | Bar, sheets, final CTA | `env(safe-area-inset-bottom)` throughout — iPhone home indicator must not overlap the FAB |
| **Edge-to-edge media** | 01, 04, 09 | Images bleed the full viewport width on mobile; only text keeps the gutter |
| **Larger radii** | Cards, sheets | Mobile cards move from `$radius-md` to `$radius-lg` — closer to native card styling |
| **No hover-only content** | Everywhere | Anything revealed on hover must have a tap equivalent |

**What we deliberately do not copy from apps:** no pull-to-refresh, no swipe-back gesture, no splash screen, no fake native transitions between sections. It should feel app-*like*, not pretend to be an app it isn't.

---

## 8. Library Assignment Map

### Swiper — 3 built, 2 rendered at launch

| Instance | Section | Config | Launch |
|---|---|---|---|
| `how-it-works` | 03 | **Mobile only** (`enabled` below 768px), 1.15 slides, dots, free-scroll off | ✅ |
| `caregivers` | 05 | 1.15 / 2.5 / 3.5 per view, arrows + dots, `a11y` on, `grabCursor` | ✅ |
| `testimonials` | 11 | 1 / 1.4 / 2 per view, autoplay **off** by default | ⏸ held |

Autoplay is off everywhere. Auto-advancing content is hostile to older readers and to anyone using a screen reader. If autoplay is ever added it must pause on hover, on focus, and under `prefers-reduced-motion`.

Import only the modules used (`Navigation`, `Pagination`, `A11y`) — not the full bundle.

### Fancybox — 4 instances

| Instance | Section | Content |
|---|---|---|
| Service category detail | 04 | Description, rate range, what's included, required tier |
| Verification checklist | 07 | Full list of checks per tier |
| Safety explainer video | 08 | Video, if produced |
| Founder biography | 10 | Portrait + full bio |

Global config: focus trap on, `Escape` closes, restore focus to trigger on close. Restyle the chrome to match the design system — default Fancybox styling will read as a third-party widget.

**Responsive behaviour — two presentations of the same instance:**

| | Below `md` | `md` and above |
|---|---|---|
| Presentation | **Bottom sheet** — slides up, `$sheet-radius` top corners, drag handle, max-height 88vh | Centred dialog |
| `dragToClose` | `true` (drag the handle down to dismiss — expected app behaviour) | `false` |
| Backdrop | Dim + slight scale-down of the page behind | Dim only |
| Close affordance | Drag handle + close button | Close button |

Same content, same trigger, same markup — only the presentation changes, handled in `components/_fancybox.scss` plus a small `Fancybox.defaults` breakpoint check in `modals.js`.

### GSAP — where it is used

| Use | Section | Plugin |
|---|---|---|
| Hero load timeline (staggered reveal) | 01 | core |
| Sticky header condense | 00 | ScrollTrigger |
| Section reveal on scroll (shared utility) | 03–13 | ScrollTrigger |
| Step connector line draw | 03 | ScrollTrigger |
| Taxonomy tile stagger | 04 | ScrollTrigger |
| Price number count-up (once) | 06 | ScrollTrigger |
| Photo parallax (subtle, ≤40px) | 09 | ScrollTrigger |
| FAQ accordion height | 12 | core |
| Bottom bar reveal after hero (mobile) | 15 | ScrollTrigger |
| Bottom bar scroll-spy active state (mobile) | 15 | ScrollTrigger |

**No ScrollSmoother. No scroll-jacking. No pinned sections.** Native scroll only.

---

## 9. Motion Specification

### Rules

1. **Content is never hidden behind animation.** Every reveal starts at `opacity: 0.001` with a translate no greater than 24px, and CSS guarantees final state if JS fails.
2. **Durations:** 200ms micro-interaction, 400–600ms reveals. Nothing over 800ms.
3. **Easing:** `power2.out` for entrances, `power2.inOut` for state changes. No bounce, no elastic, no overshoot.
4. **Stagger:** 60–80ms between siblings, capped at 6 items.
5. **Triggers fire once** (`once: true`) — nothing re-animates on scroll back up.
6. **Parallax is capped at 40px** of travel and never applies to text.
7. **Two reveal types only** — matching the Sekare reference's restraint (it uses exactly two across 36 elements). No third motion is introduced without removing one.

### The two reveal cues

Reproduced in GSAP rather than adding `scrollCue.js`, since GSAP is already loaded for the header, counters, accordion and parallax.

| Cue | GSAP equivalent | Where |
|---|---|---|
| `cue-up` *(default)* | `{ y: 24, opacity: 0 } → { y: 0, opacity: 1 }`, 600ms, `power2.out` | Everything except the exceptions below |
| `cue-left` *(sparing)* | `{ x: -28, opacity: 0 } → { x: 0, opacity: 1 }`, 600ms, `power2.out` | Hero type block, split section `09` — where it reinforces layout direction |

**Group stagger** replicates Sekare's `data-cues` behaviour: a `data-reveal-group` attribute on a parent staggers its direct children at 70ms, capped at 6.

`start: "top 85%"`, `once: true`. Both cues collapse to an instant final state under `prefers-reduced-motion`.

**Ratio deviation from the reference:** Sekare applies horizontal entry to 24 of 36 elements. We invert it — vertical is the default, horizontal is the exception. Horizontal motion fights the reading line, and at 200% zoom a 28px offset is a large share of the viewport.

### Reduced motion

```js
// Global gate — set before any timeline is created
gsap.matchMedia().add("(prefers-reduced-motion: reduce)", () => {
  // Set all animated elements to final state, register no ScrollTriggers
});
```

Under reduced motion the page is fully static: no reveals, no parallax, no count-up (final numbers render immediately), Swiper transitions drop to 0ms. The bottom bar renders immediately in its final position instead of sliding up, and bottom sheets fade rather than slide. Scroll-spy still updates — it conveys location, not decoration.

### No-JS baseline

The page must be fully readable and navigable with JavaScript disabled. Swiper containers degrade to horizontally scrollable flex rows; Fancybox triggers become anchor links to detail content; all GSAP-animated elements render in their final state via CSS.

---

## 10. Technical Architecture

### Folder structure

```
kindredcare-home/
├── src/
│   ├── index.html
│   ├── scss/
│   │   ├── abstracts/
│   │   │   ├── _variables.scss      // colour, type, spacing tokens
│   │   │   ├── _breakpoints.scss
│   │   │   ├── _mixins.scss
│   │   │   └── _functions.scss
│   │   ├── base/
│   │   │   ├── _reset.scss
│   │   │   ├── _typography.scss
│   │   │   ├── _fonts.scss          // @font-face, self-hosted
│   │   │   └── _utilities.scss
│   │   ├── layout/
│   │   │   ├── _container.scss
│   │   │   ├── _grid.scss
│   │   │   ├── _header.scss
│   │   │   ├── _bottom-bar.scss     // mobile app bar (<768px)
│   │   │   └── _footer.scss
│   │   ├── components/
│   │   │   ├── _button.scss
│   │   │   ├── _card.scss
│   │   │   ├── _badge.scss
│   │   │   ├── _pill.scss
│   │   │   ├── _accordion.scss
│   │   │   ├── _chip-row.scss       // mobile taxonomy filter chips
│   │   │   ├── _swiper.scss         // overrides
│   │   │   └── _fancybox.scss       // overrides + bottom-sheet variant
│   │   ├── sections/
│   │   │   ├── _hero.scss           // incl. absorbed trust row
│   │   │   ├── _how-it-works.scss
│   │   │   ├── _taxonomy.scss
│   │   │   ├── _caregivers.scss
│   │   │   ├── _pricing.scss
│   │   │   ├── _tiers.scss
│   │   │   ├── _safety.scss
│   │   │   ├── _for-caregivers.scss
│   │   │   ├── _founders.scss
│   │   │   ├── _testimonials.scss   // built, section held out of page
│   │   │   ├── _faq.scss
│   │   │   └── _final-cta.scss
│   │   └── main.scss
│   ├── js/
│   │   ├── main.js
│   │   └── modules/
│   │       ├── header.js
│   │       ├── bottom-bar.js        // reveal, scroll-spy, sheet menu
│   │       ├── reveal.js            // shared ScrollTrigger utility
│   │       ├── sliders.js           // all Swiper instances
│   │       ├── modals.js            // all Fancybox bindings
│   │       ├── counters.js
│   │       └── accordion.js
│   ├── fonts/
│   └── img/
├── dist/
├── package.json
└── .gitignore
```

### Dependencies

```jsonc
{
  "devDependencies": {
    "sass": "^1.x",            // Dart Sass
    "postcss": "^8.x",
    "postcss-cli": "^11.x",
    "autoprefixer": "^10.x",
    "cssnano": "^7.x",
    "npm-run-all": "^4.x",
    "browser-sync": "^3.x"
  },
  "dependencies": {
    "gsap": "^3.x",
    "swiper": "^11.x",
    "@fancyapps/ui": "^5.x"
  }
}
```

### Scripts

```jsonc
{
  "scripts": {
    "scss":        "sass src/scss/main.scss dist/css/main.css --style=expanded --source-map",
    "scss:watch":  "npm run scss -- --watch",
    "postcss":     "postcss dist/css/main.css -o dist/css/main.css",
    "serve":       "browser-sync start --server dist --files dist",
    "dev":         "npm-run-all --parallel scss:watch serve",
    "build":       "npm run scss -- --style=compressed && npm run postcss"
  }
}
```

### SCSS conventions

- **`@use` / `@forward` only.** `@import` is deprecated in Dart Sass and must not be used.
- Every partial that needs tokens does `@use '../abstracts' as *;`.
- **BEM** naming: `.c-card`, `.c-card__title`, `.c-card--featured`. Prefixes: `l-` layout, `c-` component, `s-` section, `u-` utility, `is-`/`has-` state.
- Nesting **maximum 3 levels**.
- Design tokens are emitted as CSS custom properties on `:root` so JS and Fancybox/Swiper overrides can read them.
- Mobile-first; `min-width` media queries only.

### Vendor CSS

Import Swiper and Fancybox CSS into `main.scss` so everything compiles to one stylesheet, then override in `components/_swiper.scss` and `components/_fancybox.scss`. Do not ship separate vendor `<link>` tags.

---

## 11. Responsive Strategy

| Breakpoint | Width | Notes |
|---|---|---|
| `sm` | 480px | Large phone |
| `md` | 768px | Tablet — nav drawer → inline nav |
| `lg` | 1024px | Small laptop — grids reach full column count |
| `xl` | 1280px | Large laptop — full column count, container still fluid |
| `2xl` | 1536px | Container reaches its **1440px** max; type scale caps, gutters grow beyond |

**Section-specific behaviour**

| Section | Mobile | Desktop |
|---|---|---|
| 00 Header | Logo + `Join as caregiver` link + sheet trigger | Full nav + dual CTA |
| 01 Hero | Stacked, image below type, edge-to-edge | Asymmetric with overlap |
| 03 How It Works | Swiper, 1.15 peek | 4 columns + connector line |
| 04 Taxonomy | Chip row + 2-up grid, feature tiles full width | Bento, uneven |
| 06 Pricing | Stacked panels, ours first | Side by side |
| 07 Tiers | Stacked | 3 across |
| 09 Caregiver | Stacked, image top, edge-to-edge | Split, image bleeds |
| 10 Founders | 2-up | 4-up |
| 15 Bottom app bar | **Visible** below 768px | Hidden — header handles navigation |

### The `md` boundary is a mode switch, not a reflow

Below 768px the page is an app: bottom app bar, bottom sheets, peeking carousels, chip rows, press feedback, edge-to-edge media. At 768px and above it is an editorial website: sticky header, centred dialogs, full grids, hover states. These are two intentional presentations of one design system — not one layout squeezed down.

The practical consequence: **test both modes as separate designs.** A bug at 767px and a bug at 769px are different bugs.

Test at 320px. Test at 200% browser zoom — a meaningful portion of this audience browses zoomed, and layouts must not break.

---

## 12. Accessibility Specification

This is a product for vulnerable users and their families. Accessibility is a functional requirement, not a checklist item.

**Target: WCAG 2.2 AA, with AAA contrast on body copy.**

| Requirement | Standard |
|---|---|
| Body text contrast | ≥ 7:1 (AAA). `$ink` on `$bone` clears this. |
| Large text / UI contrast | ≥ 4.5:1 |
| Base font size | 18px, never below 16px anywhere |
| Tap targets | ≥ 48×48px with ≥ 8px spacing |
| Focus indicator | 3px Trust Blue outline + 2px offset, visible on every interactive element. **Never `outline: none`.** |
| Keyboard | Full operation. Fancybox traps focus and restores it on close. Swiper `a11y` module on, slides reachable by keyboard. |
| Semantics | One `<h1>`. Landmarks: `header`, `nav`, `main`, `section`, `footer`. Logical heading order — no level skipping. |
| Images | Real `alt` text. Decorative images `alt=""`. |
| Icons | Never icon-only for meaning — always paired with a text label |
| Motion | `prefers-reduced-motion: reduce` fully honoured (see §9) |
| Forms | Visible persistent labels — never placeholder-as-label |
| Language | `lang="en-CA"`. French support is a later phase, but structure markup so it doesn't fight bilingual content. |

**Copy accessibility:** plain language, short sentences, no jargon. "Vulnerable Sector Check" must be explained inline the first time it appears.

### Bottom app bar — additional requirements

| Requirement | Standard |
|---|---|
| Tap targets | 5 slots across a 360px screen = 72px each. Comfortably clears the 48px minimum. |
| Markup | `<nav aria-label="Primary">` with a `<ul>`; active slot carries `aria-current="true"` |
| Icons | Never icon-only — every slot has a visible text label plus an `aria-label` on the control |
| Safe area | `padding-bottom: env(safe-area-inset-bottom)` so the iPhone home indicator never overlaps a target |
| Content occlusion | `body` gains bottom padding equal to bar height + safe area. Verify nothing in the footer or final CTA sits underneath. |
| Landmark duplication | The bar and the header are both navigation — give them distinct `aria-label` values (`Primary` / `Site`) so screen-reader users can tell them apart |
| Sheets | Focus trapped while open, `Escape` closes, focus returns to the trigger, background gets `inert` |
| Zoom | At 200% zoom the bar must not consume more than ~25% of viewport height. If it does, it collapses to a single-row CTA bar without the FAB. |

**One documented exception to the 16px floor.** Bottom-bar slot labels sit at **13px**. Navigation labels in a tab bar are the one place where the type floor works against usability — 16px labels across five slots forces truncation, which is worse than small-but-complete words. The exception is bounded: it applies to the five bar labels only, every slot pairs the label with a redundant icon, tap targets are 72px, contrast stays AAA, and each control carries a full `aria-label`. Nothing else on the page goes below 16px.

---

## 13. Performance Budget

| Metric | Target |
|---|---|
| LCP | < 2.0s on 4G |
| CLS | < 0.05 |
| Total JS (gzipped) | < 120KB — GSAP core + ScrollTrigger + Swiper modules + Fancybox |
| CSS (gzipped) | < 40KB |
| Lighthouse Performance | ≥ 90 |
| Lighthouse Accessibility | **100** |

**Tactics**

- Self-hosted WOFF2, subset to Latin, preload the two faces used above the fold.
- Hero image: `<picture>` with AVIF/WebP, explicit `width`/`height`, `fetchpriority="high"`, not lazy.
- Every other image `loading="lazy"` + `decoding="async"` with explicit dimensions (CLS).
- Defer GSAP/Swiper/Fancybox; initialise on `DOMContentLoaded`.
- Consider lazy-loading Fancybox on first modal trigger rather than on page load.
- Import Swiper modules individually — never the full bundle.

---

## 14. Content & Asset Checklist

Everything below is a blocker on the build and must come from the business side.

### Copy

- [ ] Final hero headline and sub-headline
- [ ] 10–12 service category names, one-line descriptions, and hourly rate ranges
- [ ] Four How-It-Works step descriptions
- [ ] Verification tier requirement lists (must match `final-thoughts.md` exactly)
- [ ] Seven FAQ answers — legally reviewed, consistent with the terms of use
- [ ] Founder bios (4)
- [ ] Confirmed service-area town list

### Photography

- [ ] Hero cluster — 2–3 images
- [ ] Feature tiles for taxonomy — 3–4 images
- [ ] Caregiver section image (dark section)
- [ ] Founder portraits ×4
- [ ] Safety section device mockup or illustration

Brief: real Canadian domestic settings, natural light, warm tones, mixed ages and ethnicities, **no clipboards, no scrubs, no stethoscopes, no clinical white rooms.** Prefer a local shoot over stock; if stock is unavoidable, pick against the healthcare-stock grain.

### Legal / accuracy sign-off

- [ ] Confirm 7.5% is current and that the worked example ($25/hr → $1.88 → $23.12) is correct
- [ ] Confirm agency comparison figures ($30–35/hr family, $18–22/hr caregiver)
- [ ] Confirm which verification checks are actually live via Certn today vs planned
- [ ] Approve intermediary language — "we do not employ caregivers" — for the FAQ

### Blocked / cannot ship real

- [ ] Caregiver listings (0 exist) → placeholder variant
- [ ] Testimonials (0 exist) → placeholder variant

---

## 15. Build Order

| Step | Work | Depends on |
|---|---|---|
| 1 | Project setup, npm scripts, SCSS 7-1 skeleton, token file, font loading | — |
| 2 | Base layer — reset, typography, container, grid, buttons, focus states | 1 |
| 3 | Header + footer + **bottom app bar shell** | 2 |
| 4 | **Section 01 Hero** — the aesthetic proof, in both desktop and mobile-app mode. Review and approve before continuing. | 3 |
| 5 | Sections 03, 06 — static content, high value, no external data | 4 |
| 6 | Section 04 Taxonomy + Fancybox category modals — the centrepiece | 5 |
| 7 | Sections 07, 08 — tiers and safety, with Fancybox | 6 |
| 8 | Sections 09, 10, 13 — caregiver, founders + story, final CTA | 7 |
| 9 | Section 05 recruitment variant; build 11 component and hold it out of the page | 8 |
| 10 | Section 12 FAQ + JSON-LD | 9 |
| 11 | GSAP motion pass across all sections | 10 |
| 12 | Accessibility audit, reduced-motion pass, 200% zoom pass, keyboard pass, real-device pass (iOS safe area + Android) | 11 |
| 13 | Performance pass — images, fonts, bundle trimming, Lighthouse | 12 |

**Gate at step 4.** The hero establishes the entire aesthetic direction. Get it approved before building twelve sections against an unapproved look.

---

## 16. Out of Scope & Open Decisions

### Out of scope

- Other pages (How It Works, Pricing, Trust & Safety, service category pages, For LTC Operators) — they reuse this design system in a later phase.
- WordPress theme integration.
- Real caregiver and review data.
- French localisation.
- The marketplace product itself.

### Decisions needed before the build starts

1. **Does the homepage stay static, or get ported into WordPress?** Affects nothing structurally, but decide before writing markup so class names and partials can be organised for the target.
2. **Where do the CTAs point?** The WordPress `/registration/` flow, or the Laravel app? The current WP caregiver registration collects only email, name, password, address and skills — no rate, no availability, no verification — so it cannot produce a bookable listing. Sending traffic there converts into a dead end.
3. **Display font.** Fraunces is the recommendation; Playfair Display is what the Sekare reference uses and is acceptable if restricted to display sizes only (see §4). Set both in the hero at step 4 and choose from the real thing. Confirm licence and self-hosting before it is wired into the token file.
4. **Photography budget.** A local Durham Region shoot is meaningfully better than stock here, and the taxonomy section depends on it.
5. **Is the safety explainer video being produced?** Determines whether the Fancybox video instance is built now.

### Related work outside this plan

The site audit surfaced three items that this redesign does **not** fix and that should be handled separately and urgently:

1. `https://kindredcare.ca/wp-json/wp/v2/users` publicly exposes all 16 account holders' email addresses. This is a live PIPEDA exposure and unrelated to the homepage.
2. Marketing copy across the site claims "our caregivers… training" while the terms of use states we do not employ caregivers. Needs a site-wide copy pass, not just the homepage.
3. The blog's last post is 30 March 2026 — four months stale. 47 posts is a real SEO asset and it is decaying.

---

*Plan prepared 31 July 2026. Governing strategy: `final-thoughts.md`. Supporting research: `initial-thought.md`. Superseded B2B model: `platform-workflow.md` — reference only for the EVV flow.*
