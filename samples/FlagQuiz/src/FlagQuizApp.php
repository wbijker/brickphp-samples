<?php

namespace Samples\FlagQuiz;

use BrickPHP\State\SessionStateManager;
use BrickPHP\State\StateManager;
use BrickPHP\VNode\App;
use BrickPHP\VNode\VNode;

/**
 * Flagdle — a "name the flag against the clock" quiz.
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
        return 'Flagdle — Flag Quiz';
    }

    public function state(): StateManager
    {
        return new SessionStateManager();
    }

    protected function registerAssets(App $app): void
    {
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
    }

    protected function view(): VNode
    {
        return new FlagQuiz();
    }
}
