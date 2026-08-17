# Working in this repo

## Build the UI with UI constructs, not CSS

Do not hand-write CSS. No `addStyleInline()`, no `<style>` blocks, no raw CSS
strings — build the thing out of BrickPHP's typed UI elements and their
helpers instead.

Almost everything reachable from CSS has a construct:

| Instead of                                    | Use                                                          |
| --------------------------------------------- | ------------------------------------------------------------ |
| `background-image` + `background-size: cover`  | `UI::layers()` with a `UI::image(...)->objectCover()` layer   |
| `position: absolute; inset: 0`                 | `->absolute()->inset(Unit::none())`, or `Layers::layer()`     |
| `display: flex; flex-direction: row`           | `UI::row()` / `UI::column()`                                  |
| padding / gap / rounding / shadow / colour     | `->padding()`, `->gap()`, `->rounded()`, `->shadow()`, `->background(Palette::…)` |
| media queries                                  | the `Pseudo::sm()` / `lg()` argument on the styling helpers   |

Colours come from `Palette`, sizes from `Unit`, never from literals in a
stylesheet.

If a construct genuinely does not exist for what you need, say so and ask
before reaching for CSS — the answer is usually a missing helper on
`UIElement`, which is worth adding once rather than working around each time.

Same instinct for behaviour: prefer the framework's event and state APIs over
hand-written JS.
