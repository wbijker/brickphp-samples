import os
import re
import zipfile
import requests
import xml.etree.ElementTree as ET

# Constants
ZIP_URL = "https://github.com/tailwindlabs/heroicons/archive/refs/heads/master.zip"
ZIP_PATH = "heroicons.zip"
EXTRACT_DIR = "heroicons"
SVG_DIR = os.path.join(EXTRACT_DIR, "heroicons-master", "src", "24", "outline")
OUTPUT_FILE = "Heroicons.php"

# Download the zip file
print("Downloading archive...")
response = requests.get(ZIP_URL, stream=True)
with open(ZIP_PATH, "wb") as f:
    for chunk in response.iter_content(chunk_size=8192):
        f.write(chunk)

# Extract the zip file
print("Extracting archive...")
with zipfile.ZipFile(ZIP_PATH, "r") as zip_ref:
    zip_ref.extractall(EXTRACT_DIR)

# Read SVG files and generate PHP class
print("Generating PHP class...")

def sanitize_name(filename):
    """Convert filenames to PascalCase for method names."""
    return re.sub(r'(^|-)([a-z])', lambda m: m.group(2).upper(), filename.replace(".svg", ""))

def extract_paths(svg_file):
    """Extract path 'd' attributes from an SVG file."""
    tree = ET.parse(svg_file)
    root = tree.getroot()
    return [elem.attrib.get("d", "") for elem in root.findall(".//{http://www.w3.org/2000/svg}path")]

php_methods = []
for file in os.listdir(SVG_DIR):
    if file.endswith(".svg"):
        method_name = sanitize_name(file)
        paths = extract_paths(os.path.join(SVG_DIR, file))
        paths_array = ", ".join(f"'{d}'" for d in paths if d)

        method = f"""
    static function {method_name}(string $fill = 'none', float $strokeWidth = 1.5, string $stroke = 'currentColor', string $class = 'size-6'): Svg
    {{
        return self::build($fill, $strokeWidth, $stroke, $class, [{paths_array}]);
    }}"""
        php_methods.append(method)

# Generate the full PHP class
php_code = f"""<?php


namespace HeroIcons;

use Spwa\\UI\\Svg;
use Spwa\\UI\\SvgPath;

class HeroIcons
{{

    private static function build(string $fill, float $strokeWidth, string $stroke, string $class, array $paths): Svg
    {{
        $svg = new Svg();
        $svg->viewBox(0, 0, 24, 24)
            ->fill($fill)
            ->strokeWidth((string)$strokeWidth)
            ->stroke($stroke)
            ->class($class)
            ->content(
                ...array_map(fn($p) => Svg::path($p)
                    ->strokeLinecap('round')
                    ->strokeLinejoin('round'),
                $paths)
            );
        return $svg;
    }}
{"".join(php_methods)}
}}
"""

# Save to file
with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
    f.write(php_code)

print(f"PHP class generated: {OUTPUT_FILE}")