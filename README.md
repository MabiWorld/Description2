# Mabinogi World's version of Description2 #

Provides some minor modifications of Description2 plugin to meet our needs:

* #description2 parser hook **shows** the description as well as setting it to the page description.
* #description2hide parser hook **hides** the description and only sets it as the page description.
* Parses out [[SMW::on]] and [[SMW::off]] tags and interprets entities to allow `|sep=,&#32;` and such.
* Converts `<br/>` tags to newlines, particularly for the non-hiding hook.

