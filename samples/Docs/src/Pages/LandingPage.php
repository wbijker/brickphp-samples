<?php

namespace Samples\Docs\Pages;

use BrickPHP\UI\Color;
use BrickPHP\UI\UI;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use Samples\Docs\Sections\FeaturesSection;
use Samples\Docs\Sections\HeroSection;
use Samples\Docs\Sections\PreviewSection;
use Samples\Docs\Sections\SummarySection;

/**
 * Landing page — composes four section components in order. The page
 * itself owns nothing visual beyond the white background and section
 * order; each section is its own StatelessComponent so it can be
 * reused or rearranged without touching this file.
 */
class LandingPage extends Component
{
    protected function build(): VNode
    {
        return UI::column()
            ->background(Color::white())
            ->content(
                new HeroSection(),
                new SummarySection(),
                new FeaturesSection(),
                new PreviewSection(),
            );
    }
}
