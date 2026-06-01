<?php

namespace App\Components;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use BrickPHP\Html\Div;
use BrickPHP\Html\TextArea;
use BrickPHP\Js\JS;
use BrickPHP\Nodes\Component;
use BrickPHP\Nodes\HtmlText;
use BrickPHP\Nodes\Node;


function convertToComponent(DOMNode $node, int $indentLevel = 0): string
{
    if ($node instanceof DOMText) {
        $text = trim($node->textContent);
        return $text !== '' ? 'new HtmlText(' . var_export($text, true) . ')' : '';
    }

    if (!$node instanceof DOMElement) {
        return '';
    }

    $tag = ucfirst($node->nodeName);
    $attributes = [];
    $children = [];

    // Collect attributes
    foreach ($node->attributes as $attr) {
        $attributes[] = "{$attr->name}: " . var_export($attr->value, true);
    }

    // Collect children
    foreach ($node->childNodes as $child) {
        $childComponent = convertToComponent($child, $indentLevel + 1);
        if ($childComponent) {
            $children[] = $childComponent;
        }
    }

    $indent = str_repeat("    ", $indentLevel); // 4 spaces per indent level
    $childIndent = str_repeat("    ", $indentLevel + 1);

    // Format children properly with indentation
    $childrenCode = '';
    if (!empty($children)) {
        $childrenCode = "children: [\n" . implode(",\n", array_map(fn($c) => $childIndent . $c, $children)) . "\n" . $indent . "]";
    }

    // Ensure children is the last parameter
    $params = array_filter([...$attributes, $childrenCode]);

    return "{$indent}new {$tag}(" . implode(", ", $params) . ")";
}

function convert(string $html): string
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $fragment = $dom->createDocumentFragment();
    $fragment->appendXML($html);

    $components = [];

    foreach ($fragment->childNodes as $node) {
        if ($node instanceof DOMElement) {
            $components[] = convertToComponent($node, 1);
        }
    }

    return implode(",\n", $components) . ";";
}

class ConvertComponent extends Component
{
    private static string $code = "";

    function render(): Node
    {
        return new Div(class: "w-screen h-screen flex", children: [
            new Div(class: "m-auto bg-red-200 p-2", children: [
                new Div(children: [new HtmlText("Converter")]),
                new Div(class: "flex", children: [
                    new TextArea(rows: 10, cols: 50, onChange: fn($value) => self::$code = convert($value)),
                    new Div(class: "m-2 cursor-pointer", children: [new HtmlText("=>")]),

                    new TextArea(rows: 10, cols: 50, value: self::$code),
                ]),
            ])
        ]);
    }

}

