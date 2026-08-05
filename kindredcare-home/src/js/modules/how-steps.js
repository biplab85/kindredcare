/**
 * Section 03 motion — one continuous connecting path, drawn by scroll.
 *
 * DESKTOP (lg and up) ONLY. Below that the capsules are Swiper slides in a
 * horizontal track and this module stands down completely — see the `wide` gate
 * in initHowSteps for why it has to, and `teardown` for what standing down
 * restores.
 *
 * A single SVG path threads the four step capsules. It is drawn with
 * stroke-dasharray/stroke-dashoffset against a scrubbed ScrollTrigger, and each
 * card fades in at the moment the line arrives at it. Scrolling back up undraws
 * the line and hides the cards in reverse; step 1 stays visible throughout.
 *
 * GEOMETRY. The route is strictly orthogonal: every run is either exactly
 * vertical or exactly horizontal, and the only curvature is a fixed-radius
 * rounded corner where the two meet. There are no S-curves and no decorative
 * bends — see `orthoPath`, which emits straight `L` segments joined by quadratic
 * corners rather than interpolating a spline through the waypoints.
 *
 * The route, starting at step 1's middle-left edge:
 *
 *   step 1 mid-left → short tick out to the lane → straight up → across above
 *   step 1 → down the 1–2 gap through step 2 and on below it → across beneath
 *   step 2 → up the 2–3 gap through step 3 and on above it → across above step 3
 *   → down the 3–4 gap past step 4's bottom → across beneath step 4 → straight up
 *   its right side → short tick in, finishing at step 4's middle-right edge.
 *
 * Each long vertical is one unbroken straight line that passes *through* a card's
 * mid-height on its way, which is what lets a single segment both arrive at one
 * card and continue past it.
 *
 * Why `d` is generated here instead of authored in the markup: the capsules sit
 * on a responsive wave and change width at every breakpoint above lg, so any
 * hardcoded path would be correct at exactly one viewport width. The route is
 * derived from the measured card rectangles and rebuilt whenever they move.
 */
import { prefersReducedMotion, hasGsap } from './motion.js';

const SVG_NS = 'http://www.w3.org/2000/svg';

// Matches the `lg` breakpoint: at and above it the capsules are a row and this
// module runs; below it they are slides and it does not.
const LG = 1024;

// The lane a vertical run travels in, measured out from a capsule's edge.
const LANE = 24;

// Clearance above a card's top / below its bottom for a horizontal run.
const CLEAR = 34;

// Corner radius where a run changes direction. Clamped per corner to half the
// shorter of the two adjoining runs, so a short run can never over-round.
const RADIUS = 18;

// How far outside the track the outermost lanes may stray. At lg the first card
// starts within a few px of the track's edge, so those lanes have to borrow the
// container's gutter — this bounds how much, keeping the line a predictable
// distance off the window edge at every width instead of whatever is left over.
const OUTER = 20;

// Resolution used to locate each card's anchor point along the drawn path.
const SAMPLES = 800;

// Pulled back off a threshold before re-hiding a card, so a card sitting exactly
// on its trigger point cannot flicker on and off with sub-pixel scrolling.
const HYSTERESIS = 0.015;

// How far the page scrolls while the section is pinned, as a multiple of the
// viewport. Three stages happen in here (steps 2, 3 and 4), so this wants to be
// generous enough that each one is a distinct, unhurried movement.
const PIN_SCREENS = 2.5;

const round = (n) => Math.round(n * 10) / 10;

/**
 * Offset of an element within an ancestor, walking the offsetParent chain.
 */
function offsetWithin(el, root) {
  let x = 0;
  let y = 0;
  let node = el;

  while (node && node !== root) {
    x += node.offsetLeft;
    y += node.offsetTop;
    node = node.offsetParent;
  }

  return { x, y };
}

/**
 * Card rectangles in the track's own coordinate space.
 *
 * Layout offsets, not client rects. The cards waiting for the line carry a
 * `translateY`, and a client rect reports that shifted position — so the route
 * would be built against geometry the cards abandon the moment they reveal, and
 * every anchor would sit a couple of dozen pixels off its card.
 */
function measure(track, steps) {
  return steps.map((el) => {
    const { x: left, y: top } = offsetWithin(el, track);
    const w = el.offsetWidth;
    const h = el.offsetHeight;

    return {
      left,
      top,
      right: left + w,
      bottom: top + h,
      w,
      h,
      midX: left + w / 2,
      midY: top + h / 2,
    };
  });
}

/**
 * Orthogonal polyline with rounded corners.
 *
 * Straight `L` to where the corner begins, one quadratic through the vertex, then
 * straight on. A vertex whose neighbours are collinear rounds to nothing and
 * emits a straight line — which is exactly what the mid-run anchor points need,
 * so they can sit on a long vertical without interrupting it.
 */
function orthoPath(pts, radius = RADIUS) {
  if (pts.length < 2) return '';

  let d = `M ${round(pts[0].x)} ${round(pts[0].y)}`;

  for (let i = 1; i < pts.length - 1; i += 1) {
    const prev = pts[i - 1];
    const v = pts[i];
    const next = pts[i + 1];

    const dIn = Math.hypot(v.x - prev.x, v.y - prev.y);
    const dOut = Math.hypot(next.x - v.x, next.y - v.y);
    if (dIn < 0.5 || dOut < 0.5) continue;

    const r = Math.min(radius, dIn / 2, dOut / 2);
    const ax = v.x + ((prev.x - v.x) / dIn) * r;
    const ay = v.y + ((prev.y - v.y) / dIn) * r;
    const bx = v.x + ((next.x - v.x) / dOut) * r;
    const by = v.y + ((next.y - v.y) / dOut) * r;

    d += ` L ${round(ax)} ${round(ay)} Q ${round(v.x)} ${round(v.y)}, ${round(bx)} ${round(by)}`;
  }

  const last = pts[pts.length - 1];
  d += ` L ${round(last.x)} ${round(last.y)}`;

  return d;
}

/**
 * Row layout (lg and up).
 *
 * Three long verticals, each in the gap to the left of a card, joined by three
 * horizontals that clear the cards they pass. Nothing crosses a capsule.
 */
function routeRow(r, width) {
  const [a, b, c, d] = r;

  const laneA = Math.max(a.left - LANE, -OUTER); // left of card 1
  const laneB = b.left - LANE; //                   gap 1–2
  const laneC = c.left - LANE; //                   gap 2–3
  const laneD = d.left - LANE; //                   gap 3–4
  const laneE = Math.min(d.right + LANE, width + OUTER); // right of card 4

  const overA = a.top - CLEAR;
  const overC = c.top - CLEAR;
  const underB = b.bottom + CLEAR;
  const underD = d.bottom + CLEAR;

  const pts = [];
  const anchors = [];
  const at = (x, y, anchor) => {
    pts.push({ x, y });
    if (anchor) anchors.push({ ...anchor, ring: pts.length - 1 });
  };
  // Marks the waypoint just pushed as where a step's card should fire, when that
  // differs from where its ring sits.
  const triggerHere = () => ({ trigger: pts.length - 1 });

  at(a.left, a.midY); //          start: step 1's middle-left edge
  at(laneA, a.midY, {}); //       ▸ STEP 1 — tick out to the lane
  at(laneA, overA); //            straight up
  at(laneB, overA); //            across, above card 1
  at(laneB, b.midY, {}); //       ▸ STEP 2 — straight down, arriving level with it
  at(laneB, underB); //           the same straight line, continuing below card 2
  at(laneC, underB); //           across, beneath card 2
  at(laneC, c.midY, {}); //       ▸ STEP 3 — straight up, arriving level with it
  at(laneC, overC); //            the same straight line, continuing above card 3
  at(laneD, overC); //            across, above card 3
  at(laneD, underD); //           straight down the 3–4 gap, on past card 4's bottom
  const t4 = triggerHere();
  at(laneE, underD); //           across, beneath card 4
  at(laneE, d.midY, t4); //       ▸ STEP 4 — straight up to its vertical centre
  at(d.right, d.midY); //         finish: step 4's middle-right edge

  return { pts, anchors };
}

/**
 * Where each anchor waypoint falls along the path, as a 0–1 fraction of its
 * length — which is exactly the scroll progress at which the line reaches that
 * card. Sampled rather than computed because the rounded corners shorten the
 * runs they join by an amount that depends on the clamped radius.
 */
function anchorFractions(path, length, pts, anchors) {
  const samples = [];

  for (let i = 0; i <= SAMPLES; i += 1) {
    const pt = path.getPointAtLength((length * i) / SAMPLES);
    samples.push({ x: pt.x, y: pt.y, f: i / SAMPLES });
  }

  return anchors.map(({ ring, trigger }) => {
    // A step fires where its `trigger` waypoint sits, which is only different
    // from its ring when firing at the ring would be too late to read — step 4's
    // ring is the path's own terminus.
    const target = pts[trigger ?? ring];
    let best = 0;
    let bestDist = Infinity;

    samples.forEach((s) => {
      const dist = (s.x - target.x) ** 2 + (s.y - target.y) ** 2;
      if (dist < bestDist) {
        bestDist = dist;
        best = s.f;
      }
    });

    return best;
  });
}

function buildSvg(count) {
  const svg = document.createElementNS(SVG_NS, 'svg');
  svg.setAttribute('class', 's-how__path');
  svg.setAttribute('aria-hidden', 'true');
  svg.setAttribute('focusable', 'false');
  // No viewBox on purpose: user units are then CSS px, so the generated
  // coordinates need no mapping and a resize cannot momentarily scale the line.

  const line = document.createElementNS(SVG_NS, 'path');
  line.setAttribute('class', 's-how__path-line');
  svg.append(line);

  // One hollow ring per card, echoing the reference's connector terminals.
  const nodes = Array.from({ length: count }, () => {
    const node = document.createElementNS(SVG_NS, 'circle');
    node.setAttribute('class', 's-how__path-node');
    node.setAttribute('r', '5.5');
    svg.append(node);
    return node;
  });

  return { svg, line, nodes };
}

export function initHowSteps() {
  const track = document.querySelector('[data-how-timeline]');
  if (!track) return;

  const steps = [...track.querySelectorAll('[data-how-step]')];
  if (steps.length < 4) return;

  const section = track.closest('.s-how');
  if (!section) return;

  // Progressive enhancement. Bailing out here leaves every card visible and no
  // path drawn — the section reads perfectly without the timeline.
  if (prefersReducedMotion() || !hasGsap() || !window.ScrollTrigger) return;

  const { gsap } = window;
  // The header is sticky, so the pin has to start below it.
  const header = document.querySelector('.l-header');
  const { svg, line, nodes } = buildSvg(steps.length);

  // Desktop only, and the gate is a media query rather than `innerWidth >= LG`
  // so it agrees with the CSS at the boundary — `innerWidth` counts the
  // scrollbar and a media query does not.
  //
  // Below lg the capsules are Swiper slides in a horizontal track, and this
  // module is incompatible with that in a way no amount of path geometry fixes:
  // `s-how--timeline` sets every capsule to `opacity: 0` and hands the reveal to
  // scroll progress, but a slide is reached by swiping SIDEWAYS. Steps 2-4 were
  // therefore invisible on arrival and stayed that way — you could swipe the
  // track and land on nothing. Not running here is the fix; it is also the
  // module's own documented fallback, which leaves all four capsules visible.
  const wide = window.matchMedia(`(min-width: ${LG}px)`);

  let thresholds = [];
  let tween = null;
  let building = false;
  // The run from the path's start to step 1's ring is already drawn before the
  // pin begins, so step 1 reads as connected the moment the section arrives.
  // Scroll progress therefore drives the *remaining* length, not all of it.
  let base = 0;
  let lastW = 0;
  let lastH = 0;

  /**
   * Cards and rings on/off against the drawn fraction. Runs on every scroll
   * update in both directions, which is what gives the reverse for free.
   */
  function reveal(progress) {
    const drawn = base + progress * (1 - base);

    thresholds.forEach((t, i) => {
      if (t == null) return;

      const on = drawn >= t;
      const off = drawn < t - HYSTERESIS;
      const node = nodes[i];

      // Step 1 is never hidden; its ring still waits for the line to arrive.
      if (i > 0) {
        if (on) steps[i].classList.add('is-in');
        else if (off) steps[i].classList.remove('is-in');
      }

      if (on) node.classList.add('is-on');
      else if (off) node.classList.remove('is-on');
    });
  }

  function build() {
    if (building || !wide.matches) return;

    // Rebuilding tears down and re-creates a *pinned* trigger, which is enough
    // to make the page jump if it happens mid-pin. The ResizeObserver fires when
    // pinning starts (GSAP writes an explicit width), so bail unless the geometry
    // the route depends on has genuinely changed.
    const w = track.offsetWidth;
    const h = track.offsetHeight;
    if (w === lastW && h === lastH) return;

    building = true;
    lastW = w;
    lastH = h;

    const rects = measure(track, steps);
    const route = routeRow(rects, w);

    line.setAttribute('d', orthoPath(route.pts));

    const length = line.getTotalLength();
    thresholds = anchorFractions(line, length, route.pts, route.anchors);
    base = thresholds[0] ?? 0;

    route.anchors.forEach(({ ring }, i) => {
      const node = nodes[i];
      if (!node) return;
      node.setAttribute('cx', round(route.pts[ring].x));
      node.setAttribute('cy', round(route.pts[ring].y));
    });

    if (tween) {
      // `true` reverts the pin, so the pin-spacer is removed rather than left
      // behind to stack up with the one the next trigger creates.
      tween.scrollTrigger?.kill(true);
      tween.kill();
    }

    const from = length * (1 - base);
    gsap.set(line, { strokeDasharray: length });

    tween = gsap.fromTo(
      line,
      { strokeDashoffset: from },
      {
        strokeDashoffset: 0,
        ease: 'none',
        scrollTrigger: {
          // Unconditional now: build() only runs at lg and above, where the
          // capsules are a row. The narrow branches that used to sit on every
          // one of these options are gone with the narrow layout.
          trigger: section,
          // Pin below the sticky header, not at the viewport top — otherwise the
          // header sits over the section's eyebrow for the whole pin.
          //
          // If the section is too tall to fit under the header, `bottom bottom` is
          // the better failure: it sacrifices the section's top, which is padding
          // and heading, rather than its bottom, which is body copy and dot rows.
          start: () => {
            const offset = header ? header.offsetHeight : 0;
            return section.offsetHeight <= window.innerHeight - offset
              ? `top ${offset}px`
              : 'bottom bottom';
          },
          end: () => `+=${Math.round(window.innerHeight * PIN_SCREENS)}`,
          pin: true,
          // Reserves the pinned distance in the layout, which is what stops the
          // next section moving up until the timeline has finished — and stops
          // the previous one reappearing until it has fully reversed.
          pinSpacing: true,
          anticipatePin: 1,
          scrub: true,
          invalidateOnRefresh: true,
          onUpdate: (self) => reveal(self.progress),
        },
      },
    );

    reveal(tween.scrollTrigger.progress);
    building = false;
  }

  /** Put the section into the state it has when this module is not running. */
  function teardown() {
    if (tween) {
      // `true` reverts the pin, so the pin-spacer goes with it.
      tween.scrollTrigger?.kill(true);
      tween.kill();
      tween = null;
    }

    // The dash properties are inline on the line, so they have to be cleared
    // rather than left behind for the next build to inherit.
    gsap.set(line, { clearProps: 'strokeDasharray,strokeDashoffset' });
    svg.remove();

    // The class is what applies `opacity: 0` to the capsules. Dropping it is
    // what makes all four readable — and swipeable — at once.
    section.classList.remove('s-how--timeline');
    steps.forEach((step) => step.classList.remove('is-in'));
    nodes.forEach((node) => node.classList.remove('is-on'));

    thresholds = [];
    // So a return to desktop measures afresh instead of matching the stale size
    // and bailing out of build().
    lastW = 0;
    lastH = 0;
  }

  function enable() {
    if (svg.isConnected) return;
    track.prepend(svg);
    section.classList.add('s-how--timeline');
    steps[0].classList.add('is-in'); // step 1 is visible from the start, always
  }

  function sync() {
    if (wide.matches) {
      enable();
      build();
    } else if (svg.isConnected) {
      teardown();
    }
  }

  sync();

  // Rebuild whenever the geometry the route was measured from can have moved.
  let frame = 0;
  const schedule = () => {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(sync);
  };

  wide.addEventListener('change', schedule);
  window.addEventListener('resize', schedule);
  // Card heights depend on how the copy wraps, so a late webfont changes them.
  document.fonts?.ready.then(schedule);

  if ('ResizeObserver' in window) {
    // The SVG is absolutely positioned and cannot itself resize the track, so
    // this observer has no feedback loop to fall into.
    new ResizeObserver(schedule).observe(track);
  }
}
