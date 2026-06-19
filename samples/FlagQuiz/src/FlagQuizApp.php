<?php

namespace Samples\FlagQuiz;

use BrickPHP\State\SessionStateManager;
use BrickPHP\State\StateManager;
use BrickPHP\VNode\App;
use BrickPHP\VNode\VNode;

/**
 * Flagdle — a "name the flag against the clock" quiz.
 *
 * Ported from the "Flag Quiz" Claude Design project. The UI is built from
 * BrickPHP's typed UI constructs (UI::column/row/text/image/…, Color, Unit,
 * FontSize, FontWeight, Shadow, Pseudo). The one exception is the answer
 * feedback: a green check / red cross that pops in and fades out on its own
 * needs a CSS @keyframes (a server round-trip has no client timer, and the
 * construct transitions only animate on a second render), so a single keyframe
 * is registered here and applied via the `fq-feedback` class.
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
        // Pop in, hold briefly, then fade away — ends hidden (fill: forwards).
        $app->addStyleInline(<<<'CSS'
            @keyframes fq-feedback {
                0%   { opacity: 0; transform: scale(0.4); }
                15%  { opacity: 1; transform: scale(1.12); }
                35%  { opacity: 1; transform: scale(1); }
                65%  { opacity: 1; transform: scale(1); }
                100% { opacity: 0; transform: scale(1.06); }
            }
            .fq-feedback { animation: fq-feedback 1s ease-out forwards; pointer-events: none; }
            CSS);

        // Keyboard is handled on the client; only Enter and Escape reach the
        // server. Enter already submits via the field's change event — typing
        // never round-trips. Escape clicks the hidden "next" button so a server
        // request only fires on that key (no per-keystroke traffic).
        $app->addScriptInline(<<<'JS'
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                var next = document.getElementById('fq-next');
                if (next) { e.preventDefault(); next.click(); }
            });
            JS);
    }

    protected function view(): VNode
    {
        return new FlagQuiz();
    }
}
