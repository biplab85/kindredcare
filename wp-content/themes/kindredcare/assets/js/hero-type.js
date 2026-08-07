/**
 * Early entry point — the hero headline only.
 *
 * Deliberately not part of main.js. That tag sits below four deferred vendor
 * scripts and pulls a fifteen-module graph behind it, and module scripts run in
 * document order with deferred ones, so boot() cannot start until GSAP,
 * ScrollTrigger, Swiper and Fancybox have all landed. Measured on a warm local
 * server that is first paint at 152ms and boot at 307ms; on a cold cache over a
 * real connection the gap is seconds, and the headline is what people read
 * first.
 *
 * From <head>, ahead of those tags, this needs two small files before it can
 * type. The rest of the page keeps booting on its own schedule.
 */
import { initTypewriter } from './modules/typewriter.js';

initTypewriter();
