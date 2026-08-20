<?php

/**
 * Draws the flag globe and writes it to www/assets/images/flag-globe.png.
 *
 *     php samples/FlagQuiz/tools/generate-globe.php
 *
 * The globe used to be built out of 157 absolutely-positioned elements on
 * every render of the start screen — a lot of DOM, a lot of stylesheet, and
 * 157 requests to the flag CDN, for a picture that never changes. It is a
 * picture, so it is a picture now: this composes it once, here, and the page
 * loads one file.
 *
 * The geometry is the same orthographic projection the elements used, so the
 * result is the same globe: every flag on its own country ({@see CountryPoints}),
 * shrinking towards the rim where the surface turns away, the near ones drawn
 * over the far ones, and the whole world tilted on its axis.
 *
 * Re-run it after changing the catalogue, the view, or any of the numbers
 * below. It needs the flag images, so it needs the network.
 */

require __DIR__ . '/../../../www/vendor/autoload.php';
require __DIR__ . '/flags.php';

use Samples\FlagQuiz\Country;
use Samples\FlagQuiz\CountryPoints;

/** Which point on Earth faces the viewer, and how far the axis leans. */
const VIEW_LATITUDE = 18.0;
const VIEW_LONGITUDE = 14.0;
const AXIAL_TILT = 23.0;

/** Composed at twice the size it is saved at, so the rim comes out smooth. */
const WORK = 1280;
const OUTPUT = 640;

/**
 * How wide a flag is at the point facing you, as a fraction of the globe.
 * Said as a fraction rather than in pixels so it means the same thing whatever
 * size the globe is composed or drawn at — 0.095 is a flag about a tenth of
 * the world across, which is small enough to fit Europe in and big enough to
 * still be a flag.
 */
const TILE = WORK * 0.095;

/** How far round the curve a flag is still drawn — see FlagGlobe::HORIZON. */
const HORIZON = 0.06;

/** Ocean blue: the same blue-100 the ball used to be filled with. */
const OCEAN = [219, 234, 254];

$canvas = imagecreatetruecolor(WORK, WORK);
imagealphablending($canvas, false);
imagesavealpha($canvas, true);
imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
imagealphablending($canvas, true);

$radius = WORK / 2;
$viewLat = deg2rad(VIEW_LATITUDE);
$viewLon = deg2rad(VIEW_LONGITUDE);

// Ocean first, then the flags on top of it.
imagefilledellipse(
    $canvas,
    (int)$radius,
    (int)$radius,
    WORK,
    WORK,
    imagecolorallocate($canvas, OCEAN[0], OCEAN[1], OCEAN[2]),
);

// Far side first so the near ones land on top, which is the same thing the
// z-index did when this was elements.
$placed = [];
foreach (Country::all() as $country) {
    $point = CountryPoints::for($country->code);
    if ($point === null) {
        continue;
    }
    $lat = deg2rad($point->latitude);
    $lon = deg2rad($point->longitude);

    $facing = sin($viewLat) * sin($lat) + cos($viewLat) * cos($lat) * cos($lon - $viewLon);
    if ($facing <= HORIZON) {
        continue;
    }
    $placed[] = [
        'code' => $country->code,
        'facing' => $facing,
        'x' => cos($lat) * sin($lon - $viewLon),
        'y' => cos($viewLat) * sin($lat) - sin($viewLat) * cos($lat) * cos($lon - $viewLon),
    ];
}
usort($placed, fn(array $a, array $b) => $a['facing'] <=> $b['facing']);

foreach ($placed as $flag) {
    $image = flagImage($flag['code']);
    if ($image === null) {
        continue;
    }
    $width = (int)round(TILE * (0.45 + 0.55 * $flag['facing']));
    $height = (int)round($width * 2 / 3);
    imagecopyresampled(
        $canvas,
        $image,
        (int)round($radius + $flag['x'] * $radius - $width / 2),
        (int)round($radius - $flag['y'] * $radius - $height / 2),
        0,
        0,
        $width,
        $height,
        imagesx($image),
        imagesy($image),
    );
    imagedestroy($image);
}

// The tilt. GD turns anticlockwise where CSS turned clockwise, so the angle is
// negated to keep the world leaning the way it did.
$tilted = imagerotate($canvas, -AXIAL_TILT, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
imagealphablending($tilted, false);
imagesavealpha($tilted, true);

// Rotating grew the canvas; take the middle back out of it.
$offset = (int)round((imagesx($tilted) - WORK) / 2);
$ball = imagecreatetruecolor(WORK, WORK);
imagealphablending($ball, false);
imagesavealpha($ball, true);
imagecopy($ball, $tilted, 0, 0, $offset, $offset, WORK, WORK);

// Cut it to the circle: the corners of the tilted square, and any flag hanging
// over the edge, become nothing.
$clear = imagecolorallocatealpha($ball, 0, 0, 0, 127);
for ($y = 0; $y < WORK; $y++) {
    for ($x = 0; $x < WORK; $x++) {
        $dx = $x - $radius + 0.5;
        $dy = $y - $radius + 0.5;
        if ($dx * $dx + $dy * $dy > $radius * $radius) {
            imagesetpixel($ball, $x, $y, $clear);
        }
    }
}

$out = imagecreatetruecolor(OUTPUT, OUTPUT);
imagealphablending($out, false);
imagesavealpha($out, true);
imagecopyresampled($out, $ball, 0, 0, 0, 0, OUTPUT, OUTPUT, WORK, WORK);

$target = __DIR__ . '/../../../www/assets/images/flag-globe.png';
imagepng($out, $target, 9);
printf("%s: %d flags, %d px, %d KB\n", basename($target), count($placed), OUTPUT, (int)round(filesize($target) / 1024));
