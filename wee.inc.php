<?php
/* <wee> 
   Templating library for PHP
   Author: Oria Adam
   2012

License: GPLv3
http://softov.org/webdevtools
http://code.google.com/p/wee-templating
      
   Functions:
   
   wee_process($string,$weeArray)
     Return the $string with all the wee keys replaced, as they were found in the $weeArray
     Read wee syntax below. 
          
   wee_get_keys($string) 
     Will return all keys found in $string as keys in an array.
     Currently does not support weeLoad calls.
   
   wee_val($key,$wee,$might_be_string)
     Return a wee value for $key from $wee array
     When $might_be_string is true, returns "$key" when $key not found in $wee.
     Supports:
       $wee[$key]
       $wee->$key
       If $key is 'foo[bar]' returns $wee[foo][wee_val('bar')] or $wee->foo[wee_val('bar')]
       if $key has spaces, tabs or enters it will be trimmed.
       If $key is a number, and it was not found in $wee, it will be returned.
       If $key was not found in $wee, 
          when $might_be_string==false empty string will be returned and a user_notice error will be logged.
          when $might_be_string==true the key will be returned and no error will be logged.

   wee_val_global($key)
     This function exists for auto retrieving the global arrays POST GET SERVER SESSION ENV FILES and COOKIE
     For example, weeGlobal('SERVER[foo]') would return the value of $_SERVER['foo']
     If the key does not exists in the array return null.
     If the key does not fit any of the arrays return false.
     Called from wee_val() when WEE_MAGIC_GLOBALS is true
  
///////////////////////////// wee Syntax ///////////////////////////

      
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

   <weeComment>something</weeComment> 
     Everything inside <weeComment> tags will be removed completely.
     This is usefull for dry data examples, for the designer's usage.
  
   <weeIf key1 operator key2>...</weeIf>
        Remember that the keys are case sensitive.
        Foo!=foo returns true.
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
   {wee}  {weeIf}  {weeFor}  {weeComment}  {weeLoad}
   All {wee tags} will be processed AFTER all <wee tags> are done. 
     
   NOTES:
            * The tags are case sensitive. For example, <WeeComment> is invalid: the W should be lower cased.
            * All {wee tags} will be processed AFTER all <wee tags> are done.
            * The input $string syntax should be correct. Please use wee_syntax($string). 
              For example: <weeComment> must have a corresponding </weeComment>
            * You cannot use <weeIf> inside <weeIf> nor <weeFor> inside <weeFor>.
              To workaround it use both <> and {} tags.
              For example: 
                <weeIf bases gt 0>
                  {weeIf male=false}female{/weeIf}
                  {weeIf male!=false}male{/weeIf}
                </weeIf>
              Another workaround - use external files.
              For example:
                <weeIf bases gt 0>
                  <weeInclude templates/malefemale.htm>
                </weeIf>
              
            * Tags are processed by order of appearance.
            * You can use automagic PHP globals POST/GET/SERVER/ENV/FILES/SESSION/COOKIE like that:
              <wee SESSION[foobar]> will return $_SESSION['foobar']
              (unless WEE_MAGIC_GLOBALS is false)
*/

/* wee.config.php file example:

define('WEE_DEBUG_MODE',false); 
// Currently not in use. Default true.
define('WEE_SHOW_ERRORS',false);
// When debug mode is true, any errors that wee might encounter will be printed to the screen. Default true.
define('WEE_ERROR_SOURCE_LENGTH',30);
// When wee encounters a syntax error in a template it shows an excerpt of the error location. This is the length of the excerpt. Can be 0. Default 30.
define('WEE_ENDLESS_LOOP',8888);
// To avoid endless loops, wee have an internal counter in any loop. When the counter reaches this number wee assumes it hangs on an endless loop and die with an error. Default 8888.
define('WEE_KEY_CI',false); // keys (placeholders) are case insensitive? 
// Are the keys in $wee array case-insensitive? Default false. Note that the <tags> are ALWAYS case sensitive!
define('WEE_MAGIC_GLOBALS',false); // false for security reasons?
// Magic globals are wee key values from POST/GET/SERVER/ENV/FILES/SESSION/COOKIE. For example <wee GET[foo]> returns $_GET['foo']. Default true.
*/

if (file_exists('wee.config.php')) {
	require_once('wee.config.php');
}

// set default settings where not defined
if (!defined('WEE_DEBUG_MODE'         )) 	define('WEE_DEBUG_MODE',true); // currently not in use
if (!defined('WEE_SHOW_ERRORS'        )) 	define('WEE_SHOW_ERRORS',true); // should wee print errors?
if (!defined('WEE_ERROR_SOURCE_LENGTH')) 	define('WEE_ERROR_SOURCE_LENGTH',30); // template source length to show in on-the-fly syntax errors
if (!defined('WEE_ENDLESS_LOOP'       )) 	define('WEE_ENDLESS_LOOP',8888); // when the endless-loop-counter reaches this number, die
if (!defined('WEE_KEY_CI'             )) 	define('WEE_KEY_CI',false); // are the $wee keys (placeholders) case in-sensitive? Note that this does not affect the <tags>, wee tags are ALWAYS case sensitive.
if (!defined('WEE_MAGIC_GLOBALS'      )) 	define('WEE_MAGIC_GLOBALS',true); // allow keys from automagic arrays? such as <wee GET[foo]> keys supported: POST/GET/SERVER/ENV/FILES/SESSION/COOKIE

global $wee_endless_loop,$weeval_endless_loop;
$wee_endless_loop=0;
$weeval_endless_loop=0;

//////// the wee tags 
// all tags MUST start with 'wee'
// all tags are case sensitive
$wee_tags=array(
  array('tag'=>'weeNoProcess','closing'=>true , 'key'=>false),
  array('tag'=>'weeComment'  ,'closing'=>true , 'key'=>false),
  array('tag'=>'weeSample'   ,'closing'=>true , 'key'=>false), // backwards compatibility - weeComment used to be called weeSample
  array('tag'=>'weeFor'      ,'closing'=>true , 'key'=>true),
  array('tag'=>'weeIf'       ,'closing'=>true , 'key'=>true),
  array('tag'=>'weeInclude'  ,'closing'=>false, 'key'=>true),
  array('tag'=>'weeProcess'  ,'closing'=>false, 'key'=>true),
  array('tag'=>'wee'         ,'closing'=>false, 'key'=>true),
  array('tag'=>'weeSet'      ,'closing'=>false, 'key'=>true),
  array('tag'=>'weeVal'      ,'closing'=>false, 'key'=>true)
);

///////////////////////////////////////////////


function weeError($msg,$errorcode=null){
  if (WEE_SHOW_ERRORS)
      print htmlentities($msg);
  if ($errorcode!==null)
    error_log($msg,$errorcode);
}

function countenters($string,$to=null,$from=0) {
	if ($to===null)
		return 1+substr_count ($string ,"\n",$from);
	else
		return 1+substr_count ($string ,"\n",$from, $to-$from);
}

function simplexml_to_array($object) {
  if (!is_object($object)) return $object;
  
  if (count($object->children())==0 && count($object->attributes())==0) return "$object";

  $return=array();
  
  if (count($object->attributes())>0) {

    if (count($object->children())==0)
      $return['value']="$object";
      
    foreach($object->attributes() as $k => $v) 
      $return[$k]="$v";
    
  } 
  
  $children=$object->children();
  $childcount=array();
  for($i=0;$i<count($children);$i++) {
    $v=$children[$i];
    $k=$children[$i]->getName();
    if (!array_key_exists($k,$return)) {
      $return[$k]=simplexml_to_array($v);
      $childcount[$k]=0;
    } else {
      $childcount[$k]++;
      if ($childcount[$k]==1) {
        $tmp=$return[$k];
        $return[$k]=array();
        $return[$k][]=simplexml_to_array($tmp);
      }
      $return[$k][]=simplexml_to_array($v);
    }
  }
  
  return $return;
}

function object_to_array($object) {
  $return=array();
  foreach ($object as $k => $v) {
    $return[$k]=$v;
  }
  return $return;
}

function array_to_object($object) {
  $return=object();
  foreach ($object as $k => $v) {
    $return->$k=$v;
  }
  return $return;
}

// merge two arrays/objects
function wee_array_merge($array,$arr1=null,$arr2=null,$arr3=null,$arr4=null,$arr5=null,$arr6=null,$arr7=null,$arr8=null,$arr9=null)
{
	//print_r($array);
	//print_r($arr1);

	if (is_array($array)) {
		for ($i=1;$i<=9;$i++) {
			$n='arr'.$i;
			$tmp=$$n;
			if (is_array($tmp))
				foreach($tmp as $k => $v)
					//if (!array_key_exists($k,$array))
						$array[$k]=$v;
		}
		return $array;
	} // if first var is an array
	else {
		if (is_object($array)) {
			for ($i=1;$i<=9;$i++) {
				$n='arr'.$i;
				$tmp=$$n;
				if (is_array($tmp) || is_object($tmp))
					foreach($tmp as $k => $v)
						$array->$k=$v;
			}
			return $array;
		} // if first var is an object
		else {
			weeError("Array merge `$array` is not an array/object",E_USER_ERROR);
			die();
			return false;
		}
	}
}

// Return a value of a $array[$key]
// When $arrayname is given, the $key should look like this: "arrayname[key]"
// If the key was not found in array return FALSE.
// If the key was not found in array, but the array name fits, return null. 
function wee_from_array($key,$array,$arrayname='')
{
	if ($arrayname=='') {
		if (array_key_exists($key,$array))
			return $array[$key];
		else
			if (WEE_KEY_CI && $key!=strtolower($key)) {
				$array = array_change_key_case($array,CASE_LOWER);
				$key = strtolower($key);
				if (array_key_exists($key,$array))
					return $array[$key];
			}
			return FALSE;
	} else {//if $arrayname==''
		$low=strtolower($key);
		$arrayname=strtolower($arrayname);
		if (strpos($low,'[')) {
			$karrayname=substr($low,0,strpos($low,'['));
			if ($arrayname==$karrayname) {
				$k=substr($key,strpos($low,'[')+1);
				if ($k{strlen($k)-1}==']')
					$k=substr($k,0,strlen($k)-1);
				if (array_key_exists($k,$array)) {
					return $array[$k];
				} else {
					// try lowercased
					$array=array_change_key_case($array);
					$k=substr($low,strpos($low,'[')-1);
					if (array_key_exists($k,$array)) {
						return $array[$k];
					}
				}//else $k exists in $array
			}//if $arrayname==$karrayname
			return null;
		} else { // if found '['
			return FALSE;
		}
	}//else $arrayname == ''
}


// this function exists for auto retrieving the global arrays POST GET SERVER SESSION ENV FILES and COOKIE
// for example, weeGlobal('SERVER[FooBar]') would return the value of $_SERVER['FooBar']
// if the key does not exists, a value of null is returned.
// if the key is not one of the arrays, a value of false is returned.
function wee_val_global($key){
    if (                !empty($_SESSION)) $ret=wee_from_array($key,$_SESSION,'SESSION');
    if ($ret===false && !empty($_COOKIE )) $ret=wee_from_array($key,$_COOKIE ,'COOKIE' );
    if ($ret===false && !empty($_GET    )) $ret=wee_from_array($key,$_GET    ,'GET'    );
    if ($ret===false && !empty($_POST   )) $ret=wee_from_array($key,$_POST   ,'POST'   );
    if ($ret===false                     ) $ret=wee_from_array($key,$_SERVER ,'SERVER' );
    if ($ret===false                     ) $ret=wee_from_array($key,$_ENV    ,'ENV'    );
    if ($ret===false && !empty($_FILES  )) $ret=wee_from_array($key,$_FILES  ,'FILES'  );
    return $ret;
}

// return an array of all wee keys found in $string
function wee_get_keys($string) {
	/*
	// make sure syntax is ok:
	$syntax=wee_syntax($string);
	if ($syntax!==true) {
		weeError("wee syntax failed $syntax.",E_USER_ERROR);
		exit;
		return $string;
	}*/

    return array_flip(array_merge(
       (array)wee_get_keys_se($string,'<wee ','>')
      ,(array)wee_get_keys_se($string,'<weeVal ','>')
      ,(array)wee_get_keys_se($string,'<weeFor ','>')
      ,(array)wee_get_keys_se($string,'<weeIf ','>')
      ,(array)wee_get_keys_se($string,'<weeLoad ','>')
      ,(array)wee_get_keys_se($string,'<weeProcess ','>')
      ,(array)wee_get_keys_se($string,'<weeSet ','>')
      ,(array)wee_get_keys_se($string,'{wee ','}')
      ,(array)wee_get_keys_se($string,'{weeVal ','}')
      ,(array)wee_get_keys_se($string,'{weeFor ','}')
      ,(array)wee_get_keys_se($string,'{weeIf ','}')
      ,(array)wee_get_keys_se($string,'{weeLoad ','}')
      ,(array)wee_get_keys_se($string,'{weeProcess ','}')
      ,(array)wee_get_keys_se($string,'{weeSet ','}')
    ));
}

function wee_get_keys_se($string,$s,$e)
{
	// $s=start string, $e=end string
	$sz=strlen($s);
	$pos = strpos($string,$s); // find first $s
	while ($pos!=false) {  // as long as we keep finding them
		$pos2=strpos($string,$e,$pos); // find corresponding $e
		if ($pos2) {  // if $e was found
			$key=substr($string,$pos+$sz,$pos2-$pos-$sz);
			$found[]=$key;
		} else {
			weeError("wee_get_keys: unclosed tag ($s missing $e) line ".countenters($string,$pos)." [[".substr($string,$pos,WEE_ERROR_SOURCE_LENGTH)."]]",E_USER_ERROR);
			die();
			$pos2=$pos+$sz;
		}
		$pos = strpos($string,$s,$pos2); // find next $s
	}//while $pos
	return $found;
}


// wee_syntax($string)
// check the syntex of the $string
// make sure every starting tag has a closing one
// on success return true
// on fail return an error message
function wee_syntax($string) {
	global $wee_tags;
	$weetags=array();
	foreach($wee_tags as $t) {
		if ($t['closing']) {
			if ($t['key']) {
				$weetags['<'.$t['tag'].' ']='</'.$t['tag'].'>';
				$weetags['{'.$t['tag'].' ']='{/'.$t['tag'].'}';
			} else {
				$weetags['<'.$t['tag'].'>']='</'.$t['tag'].'>';
				$weetags['{'.$t['tag'].'}']='{/'.$t['tag'].'}';
			}
		}
	}
  
	$return='';
	foreach($weetags as $s=>$e) {
		$scount=substr_count($string,$s);
		$ecount=substr_count($string,$e);
		if ($scount>$ecount) {
			$return.="Missing ".($scount-$ecount)." $e";
		} 
		if ($ecount>$scount) {
			$return.="Extra ".($ecount-$scount)." $e";
		}
	}
	return $return || true;
}

/* Process the $string and replace all keys 
*/
function wee_process($string,$wee){
	// make sure syntax is ok:
	$syntax=wee_syntax($string);
	if ($syntax!==true) {
		weeError("wee syntax failed $syntax.",E_USER_ERROR);
		exit;
		return $string;
	}

	if (WEE_KEY_CI) {
		$wee=array_change_key_case($wee,CASE_LOWER);
	}

	$string=wee_process_se($string,$wee,'<','>');
	$string=wee_process_se($string,$wee,'{','}');
	return $string;
}

function substring($str,$start,$end) {
	return substr($str,$start,$end-$start);
}

/* Return the value of a wee key 
*/
function wee_val($key,$wee,$key_or_string=false){
	$val=null;
	if (is_array($key)) {
		weeError("wee_val key should be a string, Array given. here it is: ".var_export($key,1),E_USER_ERROR);
		exit;
	}

	if (is_bool($key)) {
		weeError("wee_val key should be a string, Boolean ".($key? 'true':'false')." given",E_USER_ERROR);
		exit;
	}

	// $wee[$key]
	if (is_array($wee))
		if (array_key_exists($key,$wee)) 
			return $wee[$key];
		else
			if (WEE_KEY_CI && $key!=strtolower($key)) {
				$lowkey=strtolower($key);
				$lowwee=array_change_key_case($wee,CASE_LOWER);
				if (array_key_exists($lowkey,$lowwee))
					return $lowwee[$lowkey];
			}

	// $wee->$key
	if (is_object($wee)) 
		if (property_exists($wee,$key)) 
			return $wee->$key;
		else
			if (WEE_KEY_CI && $key!=strtolower($key)) {
				$lowkey=strtolower($key);
				foreach ($wee as $k=>$v)
					if (strtolower($k)==$lowkey)
						return $v;
			}
    
	/* $wee->$key->value() ? 
	if ($val!=null && is_object($val) && method_exists($val,'value'))
		$val=$val->value();
	*/
    
	// 98 - not found but it's number anyways
	if (is_numeric($key))
		return $key;
    
	// look for weearray[weekey][weekey2] / weearray[weekey[weekey2]]
	if (($pos=strpos($key,'['))>1) {
		if (($arr=wee_val_global($key))!==false) {
			if (WEE_MAGIC_GLOBALS)
				return $arr;
			else {
				weeError("wee magic globals are disabled. found: $key",E_USER_WARNING);
				return ''; // not allowing magic globals
			}
		} else {
			$arr=wee_val_array(substr($key,9,$pos),$wee);
			return wee_val(trim(substr($key,$pos)," []\t\n\r"),wee_array_merge($arr,$wee));
		}
	}


	// $key not found in $wee - try a trimmed $key
	if ($key!=trim($key," []\r\n\t")) 
		return wee_val(trim($key," \r\n\t"),$wee);

	if ($key_or_string) {
		return $key;
	} else {
		weeError("wee[$key] not defined",E_USER_NOTICE);
		return '';
	}
}

function wee_val_array($key,$wee) {
	return wee_val_array_by_value(wee_val($key,$wee),$wee);

}
function wee_val_array_by_value($val,$wee) {
	if (is_array($val) || is_numeric($val)) return $val;
	if ($val!=null && is_object($val)) {
		if (is_a($val,'SimpleXMLElement')) {
			if (method_exists($val,'children') && count($val->children())>0 )
				return simplexml_to_array($val->children());
			else
				return simplexml_to_array($val);
		}
		$return=object_to_array($val);
		if (count($return)) 
			return $return;
	}

	weeError("$val not an array",E_USER_WARNING);
	return $val;
}

// receives the inner string inside a weeFor tag, process and return the output string 
function wee_process_weeFor($string,$key,$wee) 
{
	$return='';
	$val = wee_val($key,$wee,true);
	if (!is_numeric($val))
		$val=wee_val_array_by_value($val,$wee);

	if (is_array($val)) {
		$current=0;
		$total=count($val);
		foreach ($val as $key=>$value) {
			$current++;
			$tmpwee = wee_array_merge(
						array('weeFor' => $current,
							'weeForLast'=>(1*($current==$total)),
							'weeForTotal'=>$total,
							'weeForKey'=>$key,
							'weeForValue'=>$value),$wee,(is_array($value) ? $value:array()));
			$return.=wee_process($string,$tmpwee);
		}
		return $return;
	}

	if (is_numeric("$val")) {
		$total=1*("$val");
		for ($current=1;$current<=$total;$current++) {
			$return.=wee_process($string,wee_array_merge(array('weeFor' => $current,'weeForTotal'=>$total,'weeForLast'=>(1*($current==$total))),$wee));
		}
		return $return;
	}

	weeError("wee[$key]=$val is not an array nor number",E_USER_ERROR);
	die();
	return false;
}

function wee_process_weeComment($string,$key,$wee)
{
	return '';
}

function wee_process_weeSample($string,$key,$wee) // backwards compatibility - weeComment used to be called weeSample
{
	return '';
}

function wee_process_weeNoProcess($string,$key,$wee)
{
	return $string;
}

function wee_process_weeVal($key,$wee)
{
	return "<weeNoProcess>".wee_process_wee($key,$wee)."</weeNoProcess>";
}

function wee_process_wee($key,$wee)
{
	$val = wee_val($key,$wee,false);
	if (is_object($val))
		return "$val";
	if (is_array($val))
		return count($val);
	if (is_bool($val))
		return $val ? 1:0;
	if (is_numeric($val))
		return $val;
	// <wee> is not recursive anymore -- use weeProcess if you want re-processing of wee results
	//    if (strpos($val,'wee'))  // avoid unnecessary processing...
	//        return wee_process($val,$wee);
	return $val;
}

function wee_process_weeIf($string,$key,$wee)
{
	///////////////////////////////////////////
	///// weeIf - conditionally put values
	///////////////////////////////////////////
	/*
	$IfResult,    // the result of the weeIf condition: TRUE or FALSE
	$operator,    // the operator between the two keys
	$posOp,       // position the of the operator string
	$posElse,     // position of weeElse tag 
	$opLen,       // length of operator code in the condition string 
	$operators,   // available operators array
	$opName,      // operator code name - for operator search loop
	$opVals,      // operator optional values - for operator search loop
	$opVal,       // current operator value - for operator search loop
	$key1,        // first key in the condition
	$key2,        // second key in the condition
	$val1,        // wee value for $key1 
	$val2;        // wee value for $key2 
	*/
	// available operators:
	$operators=array(
		'>=' => array('>=','=>',' gte '),
		'<=' => array('<=','=<',' lte '),
		'!=' => array('!=',' ne ',' d ',' dif ',' diff '),
		'='  => array('==','=',' e ',' eq '),
		'>'  => array('>',' gt '),
		'<'  => array('<',' lt '),
		'%'  => array('%',' mod ')
	);
    
	// find the operator
	$operator=''; // the operator that will be found
	$posOp=0;     // the operator position to be found
	$tmp=0;       // temp position of operator
	foreach ($operators as $opk=>$oparray) {
		foreach ($oparray as $op) {
			if ($posOp==0) {
				$tmp=strpos($key,$op);
				if ($tmp!==FALSE) { // operator found!
					$posOp=$tmp;
					$opLen=strlen($op);
					$operator=$opk;
				}
			}
		}
	}
        
	if ($posOp==0) { // no operator found
		$val=wee_val($key,$wee,false);
		if (is_array($val)) {
			$IfResult = count($val)>0;
		} else {
			$IfResult = !empty($val);
		}
	} else {

		$key1 = trim(substr($key,0,$posOp));
		$val1 = wee_val($key1,$wee,false);
		if (is_object($val1)) $val1="$val1";
		if (is_array ($val1)) $val1=count($val1);

		$key2 = trim(substr($key,$posOp+$opLen));
		$val2 = wee_val($key2,$wee,true);
		if (is_object($val2)) $val2="$val2";
		if (is_array ($val2)) $val1=count($val2);

//echo "(( k1=$key1 v1=$val1 $operator k2=$key2 v2=$val2 ))";

		switch ($operator) {
			case "=" : 
				$IfResult = $val1 == $val2;
			break;
			case "!=": 
				$IfResult = $val1 != $val2;
			break;
			case ">" : 
				$IfResult = $val1 >  $val2;
			break;
			case "<" : 
				$IfResult = $val1 <  $val2;
			break;
			case ">=": 
				$IfResult = $val1 >= $val2;
			break;
			case "<=": 
				$IfResult = $val1 <= $val2;
			break;
			case "%" : 
				$IfResult = ($val1 % $val2) == 0;
			break;
			default:
				// unknown operator? that's impossible! outragious
				weeError("weeIf($key) syntax error: operator unknown ($operator) line ".countenters($string,$pos),E_USER_ERROR);
				$IfResult = FALSE;
			break;
		}
	}

	// handle weeIf result
	if ($IfResult) {
		return wee_process($string,$wee);
	} else {
		return '';
	}
}

// TBD: not working, $wee is not passed by reference
function wee_process_weeSet($keyval,$wee)
{
	$key = trim(substring($keyval,0,strpos($keyval,'=')));
	$val = trim(substring($keyval,strpos($keyval,'=')+1,999));
	$wee[$key]=$val;
}

function wee_process_weeProcess($key,$wee)
{
	$val = wee_val($key,$wee,true);

	//if ($val==$key) return wee_process($val,$wee);
	if (is_object($val)) $val="$val";
	if (is_array($val)) {
		$return ='';
		foreach ($val as $v) {
			$return.=wee_process_weeProcess($v,$wee);
		}
		return $return;
	} else {
		return wee_process($val,$wee);
		//return $val;
	}
} 

function wee_process_weeInclude($key,$wee)
{
	if (strpos($key,'.')!==FALSE && file_exists($key)) { // lets try load the file
		return wee_process(file_get_contents($key),$wee);
	} else {
		$val = wee_val($key,$wee,true);
		if (is_object($val)) $val="$val";
		if (is_array($val)) {
			$return = '';
			foreach($val as $v) {
				$return.=wee_process_weeInclude($v,$wee);
			}
			return $return;
		} else {
			if (!file_exists($val)) {
				weeError("weeInclude($key) error: file $val not found",E_USER_ERROR);
			} else {
				return wee_process(file_get_contents($val),$wee);
			}
		}
	}
}


// Process $string, use $s and $tage as the start/end chars for the wee tags. 
function wee_process_se($string,$wee,$s,$e)
{
	global $wee_endless_loop;
	global $wee_tags;
	$weetags=$wee_tags; // I cannot use the global $wee_tags array because of the recursive calls to wee_process_se

	////////// Set up the wee tags parameters
	foreach($weetags as $k => $t) {
		// set start and ending tag
		if ($t['key']) {
			$weetags[$k]['tags']=$s.$t['tag'].' '; // '<wee '
		} else {
			$weetags[$k]['tags']=$s.$t['tag'].$e;  // '<weeComment>'
		}
		if ($t['closing']) {
			$weetags[$k]['tage']="$s/$t[tag]$e";  // '</weeComment>'
		}
		$weetags[$k]['function']="wee_process_$t[tag]";  // 'wee_process_weeIf'
	}

	$return=''; // output string
	$lastpos=0;
	$pos = strpos($string,$s.'wee');  // find first wee tag
	while ($pos !== false) {
		////// protect against endless loops
		$wee_endless_loop+=1;
		if ($wee_endless_loop>WEE_ENDLESS_LOOP) {
			weeError("wee_process endless loop (pos=$pos)",E_USER_ERROR);
			die();
		}

		// add tag-free string to $return
		$return.=substr($string,$lastpos,$pos-$lastpos);

		///// identify current tag
		$tag=false;
		foreach($weetags as $t) {
			if (!empty($t['tags'])) {
				if (substr($string,$pos,strlen($t['tags']))==$t['tags']) {
					$tag=$t;
				}
			}
		}
	    
		///// process current tag
		if ($tag==false) { // not really a tag
			$lastpos=$pos;
		} else {
			$lentags = strlen($tag['tags']); // length of tag start string

			// get key
			if ($tag['key']) {
				// extract the key (parameter) from the tag
				$pose = strpos($string,$e,$pos+$lentags);
				if ($pose===false) {
					// If $e not found, it will not be found for other tags as well. So let's get outa here.
					weeError("wee_process syntax error: $e not found after $tag[tags] line ".countenters($string,$pos),E_USER_ERROR);
					return false;
				}
				$key = substr($string,$pos+$lentags,$pose-$lentags-$pos);
				if (WEE_KEY_CI) $key = strtolower($key);
				if (strpos($key,"\n")!==false) {
					// It looks like $key has enters inside, its probably a mistake.
					weeError("wee_process syntax error: $e not in the same line as $tag[tags] line ".countenters($string,$pos),E_USER_WARNING);
				}

				$lastpos=$pose+strlen($e); // copy string starting the end of the tag

			} else {
				$key='';
				$lastpos = $pos+strlen($tag['tags']); // copy string starting the end of the tag
			}

			// find closure tag
			if ($tag['closing']) {
				// find corresponding closing tag:
				// @@@@@@@@@@ hierarchy multilevel for/if support
				$posclosing = strpos($string,$tag['tage'],$lastpos);
				if ($posclosing===false) {
					// This is not happening! the $string should have gone through a syntax check!
					weeError("wee_process syntax error: Missing $tag[tage] line ".countenters($string,$pos),E_USER_ERROR);
					return false;
				}
		
				// are there any inner tags? if so, ignore their ending tags
				$innertagpos=strpos($string,$tag['tags'],$lastpos);
				$countendless=0;
				while ($innertagpos!==false && $innertagpos<$posclosing) {
					$countendless++;
					if ($countendless>WEE_ENDLESS_LOOP) {
						weeError("wee_process syntax error: Mislocated $tag[tage] (posclosing=$posclosing,lastpos=$lastpos,innertagpos=$innertagpos) line ".countenters($string,$pos),E_USER_ERROR);
						return false;
					}
					$posclosing = strpos($string,$tag['tage'],$posclosing+1); // find next closing
					$innertagpos = strpos($string,$tag['tags'],$innertagpos+1); // find next inner opening tag
					if ($posclosing===false) {
						weeError("wee_process syntax error: Missing $tag[tage] line ".countenters($string,$pos),E_USER_ERROR);
						return false;
					}
				}

				$return.=$tag['function'](substr($string,$lastpos,$posclosing-$lastpos),$key,$wee);
				$lastpos=$posclosing+strlen($tag['tage']);

			} else {
				// process key and continue
				$return.=$tag['function']($key,$wee);
			}//else tag[closing]
		}//else tag==false

		///// find next wee tag
		if (strlen($string)>$lastpos+1 && $pos<$lastpos) {
			$pos = strpos($string,$s.'wee',$lastpos);
		} else {
			$pos=false;
		}
	}//while $pos
	// add tag-free string to $return
	$return.=substr($string,$lastpos);

	return $return;
}

/* Example: weeIf
forgotpassmail.html:
Dear
<weeIf name!=>
    <weeIf gender=m>Mr.</weeIf>
    <weeElse>Ms.</weeElse>
    <wee name>,
</weeIf>
<weeElse>User</weeElse>
Your password is: <wee password>

*/

/* Example: weeFor
results.html:
<table dir=rtl class=results color="{wee tablecolor}" border=0 cellpadding=0 cellspacing=0>
<weeFor results>
<a class=card href="{wee user_link}" target=_blank>
    <tr style="background:{wee tr color};" onmouseover="this.style.background='{wee tr up color}';" onmouseout="this.style.background='{wee tr color}';">
        <td align=center width=36>
            <img src="{wee user_img}" border=0 height=36></a>
        </td>
        <td align=right>
            <wee user_name><weeComment>My Name</weeComment>
        </td>
        <td align=center class=links>
            <wee user_links><weeComment><a href=#>Doggy</a> <a href=#>Catty</a></weeComment>
        </td>
    </tr>
</a>
</weeFor>
</table>
*/

