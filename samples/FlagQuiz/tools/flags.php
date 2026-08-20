<?php

/**
 * Shared by the two picture-making tools in this directory: both draw flags,
 * and both need them off the same CDN the app itself draws them from.
 */

/**
 * A country's flag, fetched straight into memory over one kept-open
 * connection. Tried a few times over: asked for two hundred images in a row a
 * CDN will drop one, and a dropped flag is a hole in the picture.
 */
function flagImage(string $code, int $width = 160): ?GdImage
{
    static $curl = null;
    $curl ??= curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://flagcdn.com/w' . $width . '/' . $code . '.png',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'vexi-image-generator',
    ]);

    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $data = curl_exec($curl);
        if ($data !== false && curl_getinfo($curl, CURLINFO_RESPONSE_CODE) === 200) {
            $image = @imagecreatefromstring($data);
            if ($image !== false) {
                return $image;
            }
        }
        usleep(200_000 * $attempt);
    }
    fwrite(STDERR, "could not fetch $code\n");
    return null;
}

/**
 * A font to write country names in. Nothing is bundled, so this takes the
 * first of the usual system faces that exists — override with FONT=/path if
 * yours is somewhere else.
 */
function labelFont(): ?string
{
    $candidates = array_filter([
        getenv('FONT') ?: null,
        '/System/Library/Fonts/Supplemental/Arial Bold.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        'C:/Windows/Fonts/arialbd.ttf',
    ]);

    foreach ($candidates as $font) {
        if (is_file($font)) {
            return $font;
        }
    }
    return null;
}
