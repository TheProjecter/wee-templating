### wee\_process\_se($string,$wee,$s,$e) ###
The real magic.
In use by wee\_process 2 times - once with '<>' and once with '{}'.

### wee\_syntax($string) ###
Syntax checking of $string.
Return true/false.

### wee\_get\_keys($string) ###
Return an array of all wee keys found in $string

### wee\_get\_keys\_se($string,$s,$e) ###
In use by wee\_get\_keys and does the same, only this time it receives a string to look for before the key (such as '<wee ') and the end of the key (mostly '>').

### weeError($msg,$errorcode=null) ###
When WEE\_SHOW\_ERRORS and WEE\_DEBUG\_MODE are true - display an error on the screen.
When $errorcode is given - call error\_log.

### countenters($string,$to=null,$from=0) ###
Return the number of \n found in a string.
Used by syntax checker to calculate error line number.

### simplexml\_to\_array($object) ###
$object is of type simplexml. This function will return an associative array representing $object.

### array\_to\_object($object) ###
Get an array, return an object.

### object\_to\_array($object) ###
Get an object, return an array.

### wee\_array\_merge($array1,$array2,$array3...) ###
Merge arrays. Get several arrays, return a single array with all keys-values from all arrays. Later arrays overrun previous ones.

### wee\_from\_array($key,$array,$arrayname='') ###
Return a value of a `$array[$key]`
When $arrayname is given, the $key should look like this: "arrayname[key](key.md)"
If the key was not found in array return FALSE.
If the key was not found in array, but the array name fits, return null.

### wee\_val\_global($key) ###
This function exists for auto retrieving the global arrays `POST GET SERVER SESSION ENV FILES and COOKIE`<br>
For example, <code>weeGlobal('server[ShalomHaver]')</code> would return the value of <code>$_SERVER['ShalomHaver']</code>

<BR>

<br>
if the key does not exists, a value of null is returned.<br>
if the key is not one of the arrays, a value of false is returned.<br>
<br>
<h3>wee_val_array($key,$wee)</h3>
?<br>
<br>
<h3>wee_val_array_by_value($val,$wee)</h3>
?<br>
<br>
<h1>wee_process<b><i>($string,$key,$wee)</h1>
These functions are called by weeProcess()</i>

<h3>wee_process_weeFor($string,$key,$wee)</h3>
Receives the inner string inside a weeFor tag, process and return the output string</b>

<h3>wee_process_weeSample($string,$key,$wee)</h3>
returns an empty string<br>
<br>
<h3>wee_process_weeNoProcess($string,$key,$wee)</h3>
returns string as it is<br>
<br>
<h3>wee_process_weeVal($key,$wee)</h3>
?<br>
<br>
<h3>wee_process_wee($key,$wee)</h3>
return the value of key<br>
<br>
<h3>wee_process_weeIf($string,$key,$wee)</h3>
$string is the inner string. $key is the complete condition.<br>
When $key turns out to be true (or non-zero) it returns a wee_process of $string.<br>
<br>
<h3>wee_process_weeSet</h3>
Should add a var to the $wee array, for later use in the template.<br>
Currently not working.<br>
<br>
<h3>wee_process_weeProcess</h3>
?<br>
<br>
<h3>wee_process_weeInclude</h3>
Include and process a template file