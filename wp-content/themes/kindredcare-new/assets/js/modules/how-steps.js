/**
 * Section 03 — one continuous connecting path between the step capsules.
 *
 * DESKTOP (lg and up) ONLY. Below that the capsules are Swiper slides in a
 * horizontal track and the path is hidden by CSS, so this module stands down.
 *
 * A single SVG path threads the four step capsules. On desktop it draws itself
 * when the track scrolls into view, the four cards fading up alongside it (see
 * `play`). Reduced motion shows everything at once.
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
 *   → down the 3–4 gap through step 4 and on past its bottom → across beneath
 *   step 4 → straight up
 *   its right side → short tick in, finishing at step 4's middle-right edge.
 *
 * Why `d` is generated here instead of authored in the markup: the capsules sit
 * on a responsive wave and change width at every breakpoint above lg, so any
 * hardcoded path would be correct at exactly one viewport width. The route is
 * derived from the measured card rectangles and rebuilt whenever they move.
 */

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
 * Layout offsets, not client rects, so a transform on a card (a hover lift, for
 * one) cannot shift the route away from the card's resting position.
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
  // `step` is the card this ring sits beside. The closing ring omits it: it
  // belongs to the line, not to a capsule.
  const at = (x, y, anchor) => {
    pts.push({ x, y });
    if (anchor) anchors.push({ ...anchor, ring: pts.length - 1 });
  };

  at(a.left, a.midY); //              start: step 1's middle-left edge
  at(laneA, a.midY, { step: 0 }); //  ▸ STEP 1 — tick out to the lane
  at(laneA, overA); //                straight up
  at(laneB, overA); //                across, above card 1
  at(laneB, b.midY, { step: 1 }); //  ▸ STEP 2 — straight down, arriving level with it
  at(laneB, underB); //               the same straight line, continuing below card 2
  at(laneC, underB); //               across, beneath card 2
  at(laneC, c.midY, { step: 2 }); //  ▸ STEP 3 — straight up, arriving level with it
  at(laneC, overC); //                the same straight line, continuing above card 3
  at(laneD, overC); //                across, above card 3
  at(laneD, d.midY, { step: 3 }); //  ▸ STEP 4 — straight down, arriving level with it
  at(laneD, underD); //               the same straight line, continuing below card 4
  at(laneE, underD); //               across, beneath card 4
  at(laneE, d.midY, {}); //           ▸ CLOSE — up card 4's right side, level with it
  at(d.right, d.midY); //             finish: step 4's middle-right edge

  return { pts, anchors };
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

  // One hollow ring per anchor, echoing the reference's connector terminals.
  const nodes = Array.from({ length: count }, () => {
    const node = document.createElementNS(SVG_NS, 'circle');
    node.setAttribute('class', 's-how__path-node');
    node.setAttribute('r', '5.5');
    svg.append(node);
    return node;
  });

  return { svg, line, nodes };
}

// Entrance timing: the line draws and the cards fade up together.
const DRAW_MS = 1600;
const CARD_MS = 520;
const EASE = 'cubic-bezier(0.25, 0.46, 0.45, 0.94)';

/**
 * Where along the route each ring sits, as a 0–1 fraction of its length.
 *
 * Measured on the straight-line waypoints rather than the rounded path, which
 * is a few px shorter at each corner — close enough to time a ring's pop to the
 * moment the drawing line reaches it.
 */
function ringFractions(pts, anchors) {
  const cumulative = [0];
  for (let i = 1; i < pts.length; i += 1) {
    cumulative.push(cumulative[i - 1] + Math.hypot(pts[i].x - pts[i - 1].x, pts[i].y - pts[i - 1].y));
  }
  const total = cumulative[cumulative.length - 1] || 1;
  return anchors.map(({ ring }) => cumulative[ring] / total);
}

export function initHowSteps() {
  const track = document.querySelector('[data-how-timeline]');
  if (!track) return;

  const steps = [...track.querySelectorAll('[data-how-step]')];
  if (steps.length < 4) return;

  // One ring per step, plus the one that closes the line off card 4's right edge.
  const { svg, line, nodes } = buildSvg(steps.length + 1);

  // Desktop only, and the gate is a media query rather than `innerWidth >= LG`
  // so it agrees with the CSS at the boundary — `innerWidth` counts the
  // scrollbar and a media query does not.
  const wide = window.matchMedia(`(min-width: ${LG}px)`);
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /*
   * Entrance, desktop only: when the track scrolls into view the line draws
   * itself from step 1 to step 4 and, at the same time, each ring pops and its
   * card fades up as the line reaches it. Below lg there is no line,
   * so the cards are left as they are. Under reduced motion, or without
   * IntersectionObserver, everything is shown at once.
   */
  const animate = !reduced && 'IntersectionObserver' in window && wide.matches;
  let state = animate ? 'waiting' : 'done'; // waiting → drawing → done
  let fractions = [];
  const timers = [];

  function showAll() {
    timers.forEach(clearTimeout);
    state = 'done';
    line.style.transition = '';
    line.style.strokeDasharray = '';
    line.style.strokeDashoffset = '';
    nodes.forEach((node) => node.classList.add('is-on'));
    steps.forEach((step) => {
      step.style.opacity = '';
      step.style.transform = '';
    });
  }

  if (animate) {
    steps.forEach((step) => {
      step.style.opacity = '0';
      step.style.transform = 'translateY(24px)';
    });
  } else {
    // Lit from the start. Set before the SVG is in the document, so the rings'
    // CSS transition has nothing to animate from.
    nodes.forEach((node) => node.classList.add('is-on'));
  }

  // Hide the line at full length, ready to be drawn by unwinding the dash.
  function primeLine() {
    const length = line.getTotalLength();
    line.style.transition = 'none';
    line.style.strokeDasharray = `${length}`;
    line.style.strokeDashoffset = `${length}`;
  }

  function play() {
    if (state !== 'waiting') return;

    // The line only exists on desktop; if the viewport is narrower now, skip it.
    if (!wide.matches || !svg.isConnected) {
      showAll();
      return;
    }

    state = 'drawing';
    primeLine();
    // Force the hidden state to apply before the transition starts.
    line.getBoundingClientRect();
    // Linear, so each ring pops exactly as the line reaches it (see ringFractions).
    line.style.transition = `stroke-dashoffset ${DRAW_MS}ms linear`;
    line.style.strokeDashoffset = '0';

    fractions.forEach((fraction, i) => {
      timers.push(setTimeout(() => nodes[i]?.classList.add('is-on'), fraction * DRAW_MS));
    });

    // Each card fades up while the line is drawing, as the line reaches its
    // ring — the first ring and fractions[i] belong to step i.
    const cardAt = steps.map((_, i) => (fractions[i] ?? i / steps.length) * DRAW_MS);

    steps.forEach((step, i) => {
      timers.push(setTimeout(() => {
        step.style.transition = `opacity ${CARD_MS}ms ${EASE}, transform ${CARD_MS}ms ${EASE}`;
        step.style.opacity = '1';
        step.style.transform = 'none';
      }, cardAt[i]));
    });

    // Hand the cards and line back to the stylesheet once everything is in, so
    // hover effects and later redraws behave exactly as before.
    timers.push(setTimeout(() => {
      showAll();
      steps.forEach((step) => {
        step.style.transition = '';
      });
    }, Math.max(DRAW_MS, cardAt[cardAt.length - 1] + CARD_MS) + 50));
  }

  let lastW = 0;
  let lastH = 0;

  function build() {
    // Only redraw when the geometry the route depends on has changed.
    const w = track.offsetWidth;
    const h = track.offsetHeight;
    if (w === lastW && h === lastH) return;

    lastW = w;
    lastH = h;

    const route = routeRow(measure(track, steps), w);

    line.setAttribute('d', orthoPath(route.pts));

    route.anchors.forEach(({ ring }, i) => {
      const node = nodes[i];
      if (!node) return;
      node.setAttribute('cx', round(route.pts[ring].x));
      node.setAttribute('cy', round(route.pts[ring].y));
    });

    fractions = ringFractions(route.pts, route.anchors);

    // A new route has a new length: keep it hidden until it plays, and if the
    // layout moves mid-draw, finish at once rather than draw a stale length.
    if (state === 'waiting') primeLine();
    else if (state === 'drawing') showAll();
  }

  function sync() {
    if (wide.matches) {
      if (!svg.isConnected) track.prepend(svg);
      build();
      return;
    }

    // No line below lg, so nothing to draw: never leave the cards hidden.
    if (state !== 'done') showAll();

    if (svg.isConnected) {
      svg.remove();
      // So a return to desktop measures afresh instead of matching the stale
      // size and skipping the build.
      lastW = 0;
      lastH = 0;
    }
  }

  sync();

  if (state === 'waiting') {
    // Start once a good part of the track is on screen, so the draw is seen.
    const io = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        io.disconnect();
        play();
      }
    }, { threshold: 0.35 });
    io.observe(track);
  }

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
