<?php

namespace Samples\FlagQuiz;

/**
 * The two questions there are to ask about a place, and the only two.
 *
 * A landmark, a river, a range and a lake are things that are somewhere, so
 * either the map shows it and you say what it is, or you are told what it is
 * and you go and find it. Everything else — which country it belongs to, which
 * flag flies over it — is a question about a country that happens to contain
 * it, and those aren't asked (see {@see Attribute::compatible()}).
 *
 * Held as a direction rather than as a pair of attributes because that is what
 * the tab offers: two rows, one of which is ticked.
 *
 * String-backed so it round-trips through the start screen's choice.
 */
enum PlaceQuestion: string
{
    /** Drawn on the map with nothing naming it; the answer is typed. */
    case Name = 'name';

    /** Named; the answer is a click on where it is. */
    case Find = 'find';

    /** Which of the two a question is, or null if it isn't about a place at all. */
    public static function of(Quiz $quiz): ?self
    {
        return match (true) {
            $quiz->namesPlace() => self::Name,
            $quiz->locatesPlace() => self::Find,
            default => null,
        };
    }

    /** The question this direction makes of a place. */
    public function quizFor(Attribute $place): Quiz
    {
        return $this === self::Name
            ? new Quiz([Attribute::Location], $place)
            : new Quiz([$place], Attribute::Location);
    }

    /** The row's title: the pairing, read left to right. */
    public function label(Attribute $place): string
    {
        $where = $place->locationLabel();
        return $this === self::Name
            ? $where . ' → Name'
            : 'Name → ' . $where;
    }

    /** The row's second line: what actually happens. */
    public function hint(): string
    {
        return $this === self::Name
            ? 'It is drawn on the map, unnamed — type what it is'
            : 'It is named — click where it is on the map';
    }
}
