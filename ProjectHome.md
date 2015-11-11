Like smarty, flexy etc - `<wee>` is another library to process html (and other) templates for php, like an advanced str\_replace.
The basic function is to replace a list of placeholders, passed on as keys in an array, with their preset values.

`<wee>` currently supports simple vars, for loops, foreach loops, and if statements.
It supports 2-pass processing, to allow passing a placeholder as a variable and loops in loops.
The first pass process `<wee>` style tags, the second pass process `{wee}` style tags.

`<wee>` does not allow access to general php variables nor php code. All placeholders must be predefined and passed to the wee\_process() function in one big array.