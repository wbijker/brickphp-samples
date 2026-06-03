<?php

namespace App;

/**
 * Per-app configuration passed into Brick::run / Brick::watch as the
 * second argument. The framework reads these fields via the injected
 * instance — no static state, no library-owned defaults.
 */
class Config
{
    /**
     * When true the page emits the HMR long-poll, the ctrl+click
     * open-in-editor inspector, the "w" wireframe keybind, and the
     * ?wireframe view. Set false in production.
     */
    public bool $development = true;

    /**
     * Root of hmr.php's source-change walk. Relative paths resolve
     * against the entry script's directory; '..' means the project root.
     */
    public string $sourceDir = '..';

    /**
     * Basenames pruned from that walk — matches directory and file names.
     * @var string[]
     */
    public array $sourceExclude = ['vendor', 'node_modules', '.git'];

    /**
     * Editor jump-to-source URL template for ctrl+click, interpolated
     * with {file}, {line}, {col}. Empty disables it. Examples:
     *   phpstorm://open?file={file}&line={line}
     *   vscode://file/{file}:{line}:{col}
     *   cursor://file/{file}:{line}:{col}
     */
    public string $editorUrl = 'phpstorm://open?file={file}&line={line}';

    /**
     * Host-side absolute path of this project, used to rewrite
     * server-captured paths so the editor link resolves when PHP runs
     * in a container/VM. Null = no rewrite (auto-detected server root).
     */
    public ?string $editorHostRoot = '/Users/willembijker/projects/brickphp-samples';
}
