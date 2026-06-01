<?php

namespace Samples\Docs\Components;

use BrickPHP\UI\FontSize;
use BrickPHP\UI\UI;
use BrickPHP\UI\UIElement;
use BrickPHP\UI\Unit;
use BrickPHP\VNode\Component;
use BrickPHP\VNode\VNode;
use PhpToken;

/**
 * Renders a PHP source string with syntax highlighting. Uses PHP's own
 * `PhpToken::tokenize()` so the highlight always matches the language —
 * no external dependency, no regex approximation.
 *
 * Usage:
 *   new PHPCode("<?php\necho 'hello';\n");
 *
 * The `<?php` opening tag is added automatically if missing so users can
 * paste plain snippets like `echo 'hi';`.
 *
 * Colour palette lives in a strongly typed {@see SyntaxTheme} —
 * {@see AtomOneDark} is the concrete derived class used here. The
 * build chain references `(new AtomOneDark())` directly so the
 * BrickPHP CSS extractor can follow `new Class(...)` into the ctor
 * body and register every theme Color literal as a CSS rule at build
 * time.
 */
class PHPCode extends Component
{
    public function __construct(private string $code) {}

    protected function build(): VNode
    {
        return UI::pre()
            ->padding(Unit::px(24))
            ->background((new AtomOneDark())->background)
            ->color((new AtomOneDark())->defaultColor)
            ->fontSize(FontSize::Small)
            ->overflow()
            ->content(...$this->tokenize($this->code));
    }

    // ============================================================
    // Tokenizer
    // ============================================================

    /**
     * @return array<int, UIElement>
     */
    private function tokenize(string $source): array
    {
        $prepended = false;
        $trimmedHead = ltrim($source);
        if (!str_starts_with($trimmedHead, '<?php') && !str_starts_with($trimmedHead, '<?=')) {
            $source = "<?php\n" . $source;
            $prepended = true;
        }

        $tokens = PhpToken::tokenize($source);

        // Strip the synthetic `<?php\n` we added so the rendered output
        // exactly matches what the caller passed in.
        if ($prepended && $tokens !== [] && $tokens[0]->id === T_OPEN_TAG) {
            array_shift($tokens);
        }

        $spans = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            // `->color((new AtomOneDark())->colorFor($this->kindOf(...)))`
            // is the deliberate shape: the CSS extractor follows the
            // `new Class(...)` into AtomOneDark's ctor body, harvests
            // every Color literal it finds there, and (because the
            // `$this->kindOf(...)` arg is opaque at scan time) runs
            // synthesis with each Color as a candidate — registering
            // one CSS rule per theme colour.
            $spans[] = UI::span($tokens[$i]->text)
                ->color((new AtomOneDark())->colorFor(
                    $this->kindOf($tokens[$i], $tokens, $i),
                ));
        }

        return $spans;
    }

    // ============================================================
    // Token classifier
    // ============================================================

    /**
     * Bucket a token into one of the {@see SyntaxTheme} kinds. Some
     * tokens (notably `T_STRING`) need a peek at neighbours to
     * disambiguate class names, function calls, and bare identifiers.
     *
     * @param array<int, PhpToken> $tokens
     */
    private function kindOf(PhpToken $token, array $tokens, int $i): string
    {
        $id   = $token->id;
        $text = $token->text;

        if ($id === T_WHITESPACE) {
            return 'default';
        }
        if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
            return 'comment';
        }
        if (
            $id === T_CONSTANT_ENCAPSED_STRING
            || $id === T_ENCAPSED_AND_WHITESPACE
            || $id === T_INLINE_HTML
        ) {
            return 'string';
        }
        if ($text === '"' || $text === "'" || $text === '`') {
            return 'string';
        }
        if ($id === T_LNUMBER || $id === T_DNUMBER) {
            return 'number';
        }
        if ($id === T_VARIABLE) {
            return 'variable';
        }
        if ($id === T_OPEN_TAG || $id === T_OPEN_TAG_WITH_ECHO || $id === T_CLOSE_TAG) {
            return 'tag';
        }
        if (self::isKeyword($id)) {
            return 'keyword';
        }
        if (in_array($id, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_DOUBLE_ARROW], true)) {
            return 'operator';
        }

        if ($id === T_STRING) {
            $lower = strtolower($text);
            if ($lower === 'true' || $lower === 'false' || $lower === 'null') {
                return 'constant';
            }

            $prev = $this->previousMeaningful($tokens, $i);
            $prevId = $prev?->id;

            // `function foo(` — declaration name.
            if ($prevId === T_FUNCTION) {
                return 'function';
            }

            // Class refs after `new`, `use`, `\`, `::`, `extends`,
            // `implements`, `instanceof`.
            if ($prevId === T_NEW
                || $prevId === T_USE
                || $prevId === T_NAMESPACE
                || $prevId === T_DOUBLE_COLON
                || $prevId === T_EXTENDS
                || $prevId === T_IMPLEMENTS
                || $prevId === T_INSTANCEOF
                || ($prev !== null && $prev->text === '\\')
            ) {
                return 'class';
            }

            // Followed by `(` — function/method call.
            $next = $this->nextMeaningful($tokens, $i);
            if ($next !== null && $next->text === '(') {
                return 'function';
            }

            // Pascal-cased bare word — likely a class name.
            if (ctype_upper($text[0] ?? '')) {
                return 'class';
            }

            return 'default';
        }

        if ($id === T_NAME_QUALIFIED || $id === T_NAME_FULLY_QUALIFIED || $id === T_NAME_RELATIVE) {
            return 'class';
        }

        return 'default';
    }

    private function previousMeaningful(array $tokens, int $i): ?PhpToken
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            if ($tokens[$j]->id === T_WHITESPACE) {
                continue;
            }
            return $tokens[$j];
        }
        return null;
    }

    private function nextMeaningful(array $tokens, int $i): ?PhpToken
    {
        $count = count($tokens);
        for ($j = $i + 1; $j < $count; $j++) {
            if ($tokens[$j]->id === T_WHITESPACE) {
                continue;
            }
            return $tokens[$j];
        }
        return null;
    }

    /**
     * Common PHP language keywords that should highlight as the
     * theme's `keyword` kind. Looked up by token id so we don't have
     * to string-match the spelling.
     */
    private static function isKeyword(int $id): bool
    {
        static $set = null;
        if ($set === null) {
            $names = [
                'T_ABSTRACT', 'T_ARRAY', 'T_AS', 'T_BREAK', 'T_CALLABLE',
                'T_CASE', 'T_CATCH', 'T_CLASS', 'T_CLONE', 'T_CONST',
                'T_CONTINUE', 'T_DECLARE', 'T_DEFAULT', 'T_DO', 'T_ECHO',
                'T_ELSE', 'T_ELSEIF', 'T_EMPTY', 'T_ENDDECLARE', 'T_ENDFOR',
                'T_ENDFOREACH', 'T_ENDIF', 'T_ENDSWITCH', 'T_ENDWHILE',
                'T_ENUM', 'T_EXIT', 'T_EXTENDS', 'T_FINAL', 'T_FINALLY',
                'T_FN', 'T_FOR', 'T_FOREACH', 'T_FUNCTION', 'T_GLOBAL',
                'T_GOTO', 'T_IF', 'T_IMPLEMENTS', 'T_INCLUDE',
                'T_INCLUDE_ONCE', 'T_INSTANCEOF', 'T_INSTEADOF',
                'T_INTERFACE', 'T_ISSET', 'T_LIST', 'T_LOGICAL_AND',
                'T_LOGICAL_OR', 'T_LOGICAL_XOR', 'T_MATCH', 'T_NAMESPACE',
                'T_NEW', 'T_PRINT', 'T_PRIVATE', 'T_PROTECTED', 'T_PUBLIC',
                'T_READONLY', 'T_REQUIRE', 'T_REQUIRE_ONCE', 'T_RETURN',
                'T_STATIC', 'T_SWITCH', 'T_THROW', 'T_TRAIT', 'T_TRY',
                'T_UNSET', 'T_USE', 'T_VAR', 'T_WHILE', 'T_YIELD',
                'T_YIELD_FROM',
            ];
            $set = [];
            foreach ($names as $name) {
                if (defined($name)) {
                    $set[constant($name)] = true;
                }
            }
        }
        return isset($set[$id]);
    }
}
