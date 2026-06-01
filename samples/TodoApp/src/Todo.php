<?php

namespace Samples\TodoApp;

use BrickPHP\State\State;

final class Todo extends State
{
    public function __construct(
        public int $id,
        public string $text,
        public bool $completed,
    ) {}

    public static function deserialize(mixed $raw): static
    {
        if ($raw instanceof self) {
            return $raw;
        }
        if (!is_array($raw)) {
            throw new \TypeError('Todo::format expects array or self, got ' . get_debug_type($raw));
        }
        return new self(
            id: (int)($raw['id'] ?? throw new \InvalidArgumentException('Todo: missing id')),
            text: (string)($raw['text'] ?? ''),
            completed: (bool)($raw['completed'] ?? false),
        );
    }
}
