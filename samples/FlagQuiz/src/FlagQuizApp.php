<?php

namespace Samples\FlagQuiz;

use BrickPHP\State\SessionStateManager;
use BrickPHP\State\StateManager;
use BrickPHP\VNode\App;
use BrickPHP\VNode\VNode;
use Samples\FlagQuiz\Components\ScoreBar;
use Samples\News\Leaflet;

/**
 * Vexi — a "name the flag against the clock" quiz.
 *
 * Ported from the "Flag Quiz" Claude Design project. The UI is built entirely
 * from BrickPHP's typed UI constructs (UI::column/row/text/image/…, Color,
 * Unit, FontSize, FontWeight, Shadow, Pseudo). The one bit of hand-written
 * code is a tiny keyboard script: the framework dispatches every keydown to the
 * server, so to keep typing client-side we handle the "pass" keys here and only
 * round-trip on those.
 */
class FlagQuizApp extends App
{
    public function title(): string
    {
        return 'Vexi — Flag Quiz';
    }

    public function state(): StateManager
    {
        return new SessionStateManager();
    }

    protected function registerAssets(App $app): void
    {
        // The handles the ticker script and the scorebar agree on.
        $timerId = ScoreBar::TIMER_ID;
        $secondsAttr = ScoreBar::TIMER_SECONDS_ATTR;

        // A quick scale "pop" used to flag a changed value (the right / wrong
        // tallies). Re-keying the element on change restarts the animation.
        $app->addStyleInline(<<<'CSS'
            @keyframes fq-pop {
                0%   { transform: scale(1); }
                30%  { transform: scale(1.5); }
                100% { transform: scale(1); }
            }
            .fq-pop { display: inline-block; animation: fq-pop .45s ease; }
            CSS);

        // Keyboard is handled on the client; only Enter and the pass keys reach
        // the server. Enter already submits via the field's change event, so
        // typing never round-trips. Escape (anywhere) and Tab (while typing in
        // the input) click the hidden "next" button — a server request fires
        // only on those keys, never per keystroke.
        $app->addScriptInline(<<<'JS'
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape' && e.key !== 'Tab') return;
                var next = document.getElementById('fq-next');
                if (!next) return;
                if (e.key === 'Tab' && !(document.activeElement && document.activeElement.tagName === 'INPUT')) return;
                e.preventDefault();
                next.click();
            });
            JS);

        // The elapsed-time cell only changes when something is sent to the
        // server, which during a quiz can be many seconds apart — long enough
        // for a clock to look stopped. The server stays the authority: it
        // renders the seconds it has counted, and this script counts on from
        // that figure, re-anchoring whenever a render brings a new one. So the
        // display never drifts from the server for more than one round trip,
        // and a paused, throttled or backgrounded tab corrects itself on the
        // next one rather than accumulating error.
        //
        // It polls faster than it displays: at exactly 1s the tick would land
        // an arbitrary fraction of a second after each re-anchor, and the
        // display would sit on a stale number for that fraction every time.
        $app->addScriptInline(<<<JS
            (function () {
                var TIMER_ID = '{$timerId}', SECONDS_ATTR = '{$secondsAttr}';
                var base = null, since = 0;

                function clock(seconds) {
                    return Math.floor(seconds / 60) + ':' + String(seconds % 60).padStart(2, '0');
                }

                setInterval(function () {
                    var el = document.getElementById(TIMER_ID);
                    // No timer on screen (start / results / explore): forget the
                    // anchor so the next game starts from its own figure.
                    if (!el) { base = null; return; }

                    var seconds = parseInt(el.getAttribute(SECONDS_ATTR), 10);
                    if (isNaN(seconds)) return;
                    if (seconds !== base) { base = seconds; since = Date.now(); }

                    var shown = base + Math.floor((Date.now() - since) / 1000);
                    var text = clock(shown);
                    if (el.textContent !== text) el.textContent = text;
                }, 250);
            })();
            JS);

        // Locations mode: Leaflet + a world-country GeoJSON overlay. The Leaflet
        // library assets, the BrickLeaflet runtime and the whole map are now
        // owned by the shared Leaflet component — registerAssets() installs the
        // library + runtime, and WorldMap drives the GeoJSON choropleth through
        // its native PHP API (no hand-written map JS here anymore).
        Leaflet::registerAssets($app);

        // Map flag/name labels (Explore mode). The pill is the whole content of
        // the mode, so it's sized to be read at a glance rather than to stay
        // out of the way — no Leaflet arrow.
        // A tooltip is absolutely positioned, so its width is shrink-to-fit —
        // and a flex image contributes nothing to that intrinsic width, so the
        // pill came out sized to the name alone with the flag hanging over its
        // own edge: the box ended mid-word, and the flag squashed narrower the
        // squarer it was (Switzerland lost 8 of its 26 pixels, Nepal more).
        // max-content measures what's actually inside, and flex: none stops the
        // parts giving ground, so every pill is the width of its own contents.
        $app->addStyleInline(<<<'CSS'
            .leaflet-tooltip.fq-label {
                display: flex; align-items: center; gap: 6px;
                padding: 3px 8px 3px 5px;
                width: max-content; max-width: none; min-width: max-content;
                background: #fff; border: 1px solid #e2e8f0; border-radius: 7px;
                box-shadow: 0 1px 3px rgba(0,0,0,.18);
                font: 600 14px/1.2 'Hanken Grotesk', system-ui, sans-serif; color: #1c1917;
                white-space: nowrap;
            }
            .leaflet-tooltip.fq-label::before { display: none; }
            .leaflet-tooltip.fq-label img {
                flex: none; width: 26px; height: 18px; object-fit: cover; border-radius: 2px;
            }
            .leaflet-tooltip.fq-label span { flex: none; overflow: visible; }
            CSS);
    }

    protected function view(): VNode
    {
        return new FlagQuiz();
    }
}
