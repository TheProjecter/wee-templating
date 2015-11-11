
```

   <wee KeyName> 
     Will be replaced with the value of $weeArray['KeyName'].
     If KeyName was not found in $weeArray, the KeyName will be used.
     If $weeArray['KeyName'] is an array, the item count of this array will be used.
     * This is not recursive! To process a wee value recursively see <weeProcess>

   <weeVal KeyName> TBD
     Same as <wee KeyName> but the value will not be parsed.

   <wee KeyArray[Key]> 
     Will be replaced with the value of $weeArray['KeyArray'][$wee['Key']||'Key']
     For example:
      $wee=array('selected'=1,'names'=array('foo','bar'));
      echo wee_process('<wee names[0]>',$wee); // foo
      echo wee_process('<wee names[selected]>',$wee); // bar

   <weeSample>something</weeSample> 
     Everything inside <weeSample> tags will be removed completely.
     This is usefull for dry data examples, for the designer's usage.
  
   <weeIf key1 operator key2>...</weeIf>
        Remember that the keys are case sensitive.
        Shalom!=shalom returns true.
        The '>' operator is not allowed inside a <weeIf> because it looks like the end of the tag. Use {weeIf} tag or 'gt' operator.
        The available operators are:
            equal to       : =  e eq
            different from : != ne d dif diff
            greater than   : > gt
            less than      : < lt
            bigger/equal   : >= gte
            smaller/equal  : <= lte
            modulu is 0    : % mod 
  
  
   <weeFor Key>...</weeFor> - 
      Repeat a section using an inner wee array.
      $wee['Key'] must be an array.
      When $wee['Key'] is an array, the inside string will be repeatedly processed using wee values from the $wee['Key'] array.
      Using inner weeFor keys:
        <wee weeFor> and <weeIf weeFor...> tags will retrieve the current loop index (starting 1)
        <wee weeForKey> and <weeIf weeForKey...> tags will retrieve the current index in the array
        <wee weeForTotal> is the total number of loops
        <wee weeForValue> and <weeIf weeForValue...> tags will retrieve the current item value (for single dimention arrays)
        <weeIf weeForLast=1> will only run on the last loop iteration (same as <wee weeFor=weeForTotal> )
        <weeIf weeForLast=0> will not run on the last loop iteration (same as <wee weeFor!=weeForTotal> )

   <weeFor X>...</weeFor> - 
      Repeat a section X (or $wee[X]) times
      $wee['X'] must be a number, unless X is a number.
      Using weeFor: <wee weeFor> and <weeIf weeFor...> tags will retrieve the current loop index (starting 0)
  
   <weeInclude filename.ext> 
       Include and process the content of file named 'filename.ext'.
       
   <weeInclude key> 
       Include and process the content of file named $wee['key'].
       If $wee['key'] is an array, I assume that the array contains multiple file names - so I include them all one by one.
       
   <weeProcess key>
       Include and Process the value string of $wee['key'].
       If $wee['key'] is an array, I assume that the array contains multiple strings - so I process them all one by one.

   <weeNoProcess>...</weeNoProcess> TBD
       Anything inside this section will NOT be parsed.

   <weeSet key=value>
       Experimental - add a value to the wee values array, for use later.
       Usually only works on the same template, and not recursive.
  
   You can use {} instead of <>  
   {wee}  {weeIf}  {weeFor}  {weeSample}  {weeLoad}
   All {wee tags} will be processed AFTER all <wee tags> are done. 

```
## NOTES ##
  * The tags are case sensitive. For example, `<WeeSample>` is invalid: the W should be lower cased.
  * All {wee tags} will be processed AFTER all `<wee tags>` are done.
  * The input $string syntax should be correct. Please use `wee_syntax($string). `<br>For example: <code>&lt;weeSample&gt;</code> must have a corresponding <code>&lt;/weeSample&gt;</code>
<ul><li>You can put <code>&lt;weeIf&gt;</code> inside <code>&lt;weeIf&gt;</code> and <code>&lt;weeFor&gt;</code> inside <code>&lt;weeFor&gt;</code>.<br>
</li><li>Tags are processed by order of appearance.<br>
</li><li>Magic globals (like <code>&lt;wee get['bla']&gt;</code>) are off by default for security reasons.