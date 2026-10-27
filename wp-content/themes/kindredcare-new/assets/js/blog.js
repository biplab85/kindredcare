/**
 * Single-article behaviour: reading progress, contents highlighting, copy link.
 *
 * A classic script rather than a module — it imports nothing, and keeping it
 * out of the module graph means it does not wait on GSAP, Swiper and Fancybox
 * to download before a progress bar can move.
 *
 * Everything here is an enhancement. With the script blocked, the bar stays at
 * zero width, the contents list is still a working set of anchors, and the copy
 * button falls back to selecting the URL.
 */
(function () {
  'use strict';

  var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* -- Reading progress ---------------------------------------------------- */
  /**
   * Width of the bar tracks how far through the article body you are — not the
   * document. Measuring the document would count the footer and the related
   * posts, so the bar would read about 70% at the last paragraph.
   */
  function initProgress() {
    var bar = document.querySelector('[data-progress-bar]');
    var article = document.querySelector('[data-article]');
    if (!bar || !article) return;

    var top = 0;
    var span = 1;
    var ticking = false;

    function measure() {
      var box = article.getBoundingClientRect();
      top = box.top + window.scrollY;
      // The article is "finished" when its end reaches the bottom of the
      // viewport, not when it reaches the top.
      span = Math.max(1, box.height - window.innerHeight);
    }

    function paint() {
      var progress = (window.scrollY - top) / span;
      bar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, progress)) + ')';
      ticking = false;
    }

    function onScroll() {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(paint);
    }

    measure();
    paint();

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', function () {
      measure();
      paint();
    });

    // Images land after first paint and change the article's height.
    window.addEventListener('load', function () {
      measure();
      paint();
    });
  }

  /* -- Contents highlighting ----------------------------------------------- */
  /**
   * Marks the section currently being read.
   *
   * rootMargin pins the trigger line near the top of the viewport, so a heading
   * becomes current as it arrives rather than when it is halfway down. The
   * lowest intersecting heading wins, which keeps the highlight from jumping
   * back up when two short sections are on screen at once.
   */
  function initToc() {
    var toc = document.querySelector('[data-toc]');
    if (!toc) return;

    var links = Array.prototype.slice.call(toc.querySelectorAll('.c-toc__link'));
    if (!links.length) return;

    var targets = links
      .map(function (link) {
        var id = decodeURIComponent(link.getAttribute('href') || '').slice(1);
        return id ? document.getElementById(id) : null;
      })
      .filter(Boolean);

    if (!targets.length) return;

    var visible = new Set();

    function highlight() {
      var current = null;

      targets.forEach(function (target) {
        if (visible.has(target)) current = target;
      });

      // Nothing on screen — keep the last heading scrolled past.
      if (!current) {
        for (var i = targets.length - 1; i >= 0; i -= 1) {
          if (targets[i].getBoundingClientRect().top < 120) {
            current = targets[i];
            break;
          }
        }
      }

      links.forEach(function (link, index) {
        var isCurrent = targets[index] === current;
        link.classList.toggle('is-current', isCurrent);
        if (isCurrent) {
          link.setAttribute('aria-current', 'true');
        } else {
          link.removeAttribute('aria-current');
        }
      });
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            visible.add(entry.target);
          } else {
            visible.delete(entry.target);
          }
        });
        highlight();
      },
      { rootMargin: '-100px 0px -70% 0px', threshold: 0 },
    );

    targets.forEach(function (target) {
      observer.observe(target);
    });

    highlight();
  }

  /* -- Copy link ----------------------------------------------------------- */
  function initCopy() {
    var buttons = document.querySelectorAll('[data-copy-link]');
    if (!buttons.length) return;

    Array.prototype.forEach.call(buttons, function (button) {
      button.addEventListener('click', function () {
        var url = button.getAttribute('data-copy-link') || window.location.href;

        function done() {
          button.classList.add('is-copied');
          window.setTimeout(function () {
            button.classList.remove('is-copied');
          }, 2000);
        }

        // The async Clipboard API needs a secure context; a local install over
        // plain http is not one, so the textarea path is a real fallback here
        // and not just old-browser cover.
        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(url).then(done, fallback);
          return;
        }

        fallback();

        function fallback() {
          var field = document.createElement('textarea');
          field.value = url;
          field.setAttribute('readonly', '');
          field.style.cssText = 'position:fixed;top:0;left:-9999px;';
          document.body.appendChild(field);
          field.select();
          try {
            document.execCommand('copy');
            done();
          } catch (error) {
            window.prompt('Copy this link', url);
          }
          document.body.removeChild(field);
        }
      });
    });
  }

  /* -- Smooth anchors ------------------------------------------------------ */
  /**
   * The header is sticky, so a native jump puts the heading underneath it.
   * scroll-margin-top in CSS handles that for keyboard and no-JS use; this only
   * adds the easing, and steps aside entirely under reduced motion.
   */
  function initAnchors() {
    if (reduced) return;

    document.addEventListener('click', function (event) {
      var link = event.target.closest && event.target.closest('.c-toc__link');
      if (!link) return;

      var id = decodeURIComponent(link.getAttribute('href') || '').slice(1);
      var target = id && document.getElementById(id);
      if (!target) return;

      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });

      // Keep the URL and the focus honest — smooth scrolling does neither.
      history.pushState(null, '', '#' + id);
      target.setAttribute('tabindex', '-1');
      target.focus({ preventScroll: true });
    });
  }

  function boot() {
    initProgress();
    initToc();
    initCopy();
    initAnchors();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
