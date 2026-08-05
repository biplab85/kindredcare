# KindredCare

Marketing site and strategy documentation for **KindredCare** — a gig marketplace
for senior care in Durham Region, Ontario. Families book verified local
caregivers by the hour at a 7.5% platform fee instead of an agency's 15%.

This repository holds two things:

1. **`kindredcare-home/`** — the static marketing homepage (SCSS + GSAP + Swiper + Fancybox).
2. **Strategy documents** at the root — research, positioning, and platform workflow.

---

## Quick start

```bash
cd kindredcare-home
npm install
npm run dev
```

`npm run dev` copies vendor assets, starts the Sass watcher, and serves the site
with live reload:

- Site — <http://localhost:3000> (Browsersync picks the next free port if 3000 is taken)
- Browsersync UI — <http://localhost:3001>

Edit anything under `src/` and the browser reloads automatically.

---

## The homepage

A hand-built static site. **No bundler** — `index.html` loads plain
`<script>` and `<link>` tags, so the markup can be lifted into a WordPress
theme later without unwinding an ES-module graph.

### Stack

| Concern | Choice |
| --- | --- |
| Styles | Sass (7-1-ish structure), autoprefixer + cssnano on build |
| Animation | GSAP + ScrollTrigger |
| Sliders | Swiper |
| Lightbox | Fancybox (`@fancyapps/ui`) |
| Dev server | Browsersync |

### Scripts

| Command | What it does |
| --- | --- |
| `npm run dev` | Vendor copy, then Sass watch + Browsersync in parallel |
| `npm run vendor` | Copies browser-ready vendor files into `src/vendor/` |
| `npm run scss` | Compiles `src/scss/main.scss` → `src/css/main.css` (expanded + source map) |
| `npm run scss:watch` | Same, in watch mode |
| `npm run serve` | Browsersync only, serving `src/` |
| `npm run build` | Vendor copy → compressed CSS + postcss → copy `src/` to `dist/` |

### Layout

```
kindredcare-home/
├── scripts/
│   ├── vendor.mjs        # node_modules -> src/vendor/ (only the files we use)
│   └── build.mjs         # src/ -> dist/, minus the SCSS sources
└── src/
    ├── index.html
    ├── img/              # hero, services, benefits, about
    ├── js/
    │   ├── main.js
    │   └── modules/      # header, offcanvas, sliders, reveal, counters, accordion, …
    └── scss/
        ├── abstracts/    # variables, mixins, breakpoints
        ├── base/         # root, fonts, reset, typography, utilities
        ├── components/   # button, pill, section-head, stat-card, search, offcanvas, …
        ├── layout/       # topbar, header, bottom-bar, footer, container
        ├── sections/     # hero, founded, service-groups, how-it-works, taxonomy, …
        └── main.scss     # the single entry point
```

`src/css/` and `src/vendor/` are generated — they are gitignored. Run
`npm run vendor && npm run scss` after a fresh clone (or just `npm run dev`).

### Section order

`main.scss` is the map. Components load **before** layout and sections: both
carry single-class selectors, so source order decides ties, and layout must be
able to place or hide a component.

Sections are numbered to match the redesign plan: hero (01), founded (01b),
service-groups (01c), how-it-works (03), taxonomy (04), benefits (04b),
caregivers (05), tiers (07), safety (08), for-caregivers (09), faq (12),
final-cta (13).

### Deploying

```bash
cd kindredcare-home
npm run build      # produces dist/
```

`dist/` is a plain static folder — upload it anywhere.

---

## Strategy documents

| File | Contents |
| --- | --- |
| `initial-thought.md` | Business plan overview, competitor deep dive, initial research |
| `final-thoughts.md` | Final strategic direction — the gig marketplace positioning |
| `platform-workflow.md` | End-to-end workflow: agencies, caregivers, seniors, and tool integration |
| `homepage-redesign-plan.md` | The spec this homepage was built from — section-by-section |
| `reference.txt` | Design reference links (Sekare, Healsoul, Seniornest, and others) |

`initial-thought.bn.md` and `final-thoughts.bn.md` are Bengali translations of
their English counterparts.
