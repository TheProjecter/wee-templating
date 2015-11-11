### `wee_process($string,$weeArray)` ###
Process `$string` replaces all keys from `$weeArray` with their values and return the new string.<br>
See <a href='wee_syntax.md'>wee_syntax</a>.<br>
<br>
<h3><code>wee_get_keys($string)</code></h3>
Will return all keys found in <code>$string</code> as keys in an array.<br>
Currently does not support weeLoad calls.<br>
<br>
<h3>wee_val($key,$wee,$might_be_string)</h3>
Return a wee value for <code>$key</code> from <code>$wee</code> array<br>
When <code>$might_be_string</code> is true, returns "$key" when <code>$key</code> not found in <code>$wee</code>.<br>
<br>
Supports the following formats:<br>
<br>
$wee[$key]<br>
$wee->$key<br>
If $key is 'foo<a href='bar.md'>bar</a>' returns $wee<a href='foo.md'>foo</a>[wee_val('bar')] or $wee->foo[wee_val('bar')]<br>
If $key has spaces, tabs or enters it will be trimmed.<br>
If $key is a number, and it was not found in $wee, it will be returned.<br>
If $key was not found in $wee,<br>
When $might_be_string==false empty string will be returned and a user_notice error will be logged.<br>
When $might_be_string==true the key will be returned and no error will be logged.<br>

<h3><code>wee_val_global($key)</code></h3>
This function exists for auto retrieving the global arrays <code>POST GET SERVER</code> SESSION ENV FILES COOKIE`<br>
<br>
For example, weeGlobal('server<a href='Shalom.md'>Shalom</a>') would return the value of $<i>SERVER['Shalom']</i><br>
If the key does not exists, a value of null is returned.<br>
If the key does not fit any of the arrays, <code>FALSE</code> is returned.