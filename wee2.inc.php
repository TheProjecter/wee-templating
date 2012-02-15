<?php
/* <wee> 
     simple templating
  
   edit date: 2008.11.11

   Author: Oria Adam

License: GPLv3
http://softov.org/webdevtools
   
   Main function :
  
   wee_process($string,$myTranslationFunction,$weeloop,$weeloopkey)
     process the $string according to explanation below, return the processed string. 
  
   
   Some functions to help you code your translation function:
        
   wee_get_keys($string) 
     Will return all keys found in the $string.
     Currently does not support weeLoad calls. 
   
   wee_val_global($key)
     This function exists for auto retrieving the global arrays POST GET SERVER SESSION ENV FILES and COOKIE
     For example, weeGlobal('server[Shalom]') would return the value of $_SERVER['Shalom']
     If the key does not exists, a value of null is returned.
     If the key does not fit any of the arrays, FALSE is returned.
  
  
      
   <wee KeyName> - will be replaced with the value of MyWeeTranslator('keyname')
   <weeSample>something</weeSample> - the entire part will be removed
  
   <weeIf key1 operator key2>...</weeIf>
        Remember that key1 is case sensitive.
        <> are not allowed in the operator because it looks like a tag.
        The available operators are:
            equal to      : =  e eq
            different from: != ne d dif diff
            bigger than   : > gt
            smaller than  : < lt
            bigger/equal  : >= gte
            smaller/equal : <= lte
            modulu is 0   : % mod 
  
  
   <weeFor key>...</weeFor> - 
      Repeat a section
      On every loop there will be a call to MyWeeTranslator('myForKey')
      Loop will continue as long as MyWeeTranslator('myForKey')===TRUE (using whitehat to avoid endless loops on mistakes)
      When MyWeeTranslator('myForKey') returns non TRUE/FALSE value, a user warning will be raised.
      All calls to $translate will be called like this:
        MyWeeTranslator('myKey',$loop_count,$loop_key) 
        $loop count starts at 1.
         
      Usually when inside a <weeFor> block the key weeFor will return current loop count.
  
   <weeInclude filename or key> 
       Include and process the content of file named filename or MyWeeTranslate("filename")
   
   <weeProcess key>
       Include and Process the value of MyWeeTranslate("val")
                          
  
   You can use {} instead of <>  
   {wee} , {weeIf} , {weeFor} , {weeSample} , {weeLoad} - the same as <wee...> 
     
   Example: 
   $html = "<HTML><title><wee pageTitle><weeSample>i am title</weeSample></title>";
  
   function MyWeeTranslator($key,$weeloop=0,$weeloopkey='') {
     if ($key=="weeSelfTest") return "wee self test ok"; // this line is mandatory for self test
     if ($key=="weeFor") return $weeloop; // this is a default line  
     $return=wee_val_global($key); // this is a default line
     if ($return!==FALSE) return $return;  
         
     $str=strtolower($str); // i prefer case insensitive
       
     if ($str=='pagetitle') {
       return "This is the title from php";
     }
   } 
   
   echo wee_process($html,MyWeeTranslator);  
    // <HTML> <title>This is the title from php</title>
  
   NOTES:
            * wee tags are case sensitive (a <WeeSample> tag is invalid. W should be lower cased)
            * every <weeSample> MUST end with a </weeSample>, or the string will be trancated.
            * <wee keyname> is very strict - do not put extra spaces or values or any other syntax.
            * when writing the Translator() function make sure to put the rapidly occuring values 
              on top of the function, to save run time.   
  
  
*/

define('WEE_ENDLESS_LOOP_LIMIT',9000); // when looping more than this, it is considered endless
define('WEE_SELF_TEST_KEY','wee Self Test key');
define('WEE_SELF_TEST_VALUE','wee Self Test OK');
define('WEE_ERROR_SOURCE_LENGTH',30);
define('WEE_FOR_INIT','weeForInit'); 

function weedbg($msg){
  echo $msg;
}

function countenters($string,$to=-1,$from=0) {
    if ($to<0) $to=strlen($string);
    $count=0;
    for ($i=$from;$i<$to;$i++) if ($string{$i}=="\n") $count++;
    return $count;
}

// default processing wee values
function wee_default_translate($key,$weeloop=null,$weeloopkey=null){
  if ($key==WEE_SELF_TEST_KEY) return WEE_SELF_TEST_VALUE; // this line is mandatory for self test
  if ($key=="weeFor") return $weeloop; // this is a default line  
  if ($key=="weeForKey") return $weeloopkey; // this is a default line
  if ($key=="weeForItem") return isset($GLOBALS[$weeloopkey][$weeloop-1]) ? $GLOBALS[$weeloopkey][$weeloop-1] : 235;  
  if ($weeloopkey!==null) {
    if (isset($GLOBALS[$weeloopkey]) && is_array($GLOBALS[$weeloopkey])) {
      if (array_key_exists($weeloop-1,$GLOBALS[$weeloopkey])) {
        if (array_key_exists($key,$GLOBALS[$weeloopkey][$weeloop-1]) && !is_array($GLOBALS[$weeloopkey][$weeloop-1][$key])) return $GLOBALS[$weeloopkey][$weeloop-1][$key];
      } else {
        if (array_key_exists($key,$GLOBALS[$weeloopkey]) && !is_array($GLOBALS[$weeloopkey][$key])) return $GLOBALS[$weeloopkey][$key];
      }
    }
  }
  
  return $key;
}


// Return a value of a $array[$key]
// When $arrayname is given, the $key should look like this: "arrayname[key]"
// If the key was not found in array return FALSE.
// If the key was not found in array, but the array name fits, return null. 
function wee_from_array($key,$array,$arrayname=''){
    if ($arrayname=='') {
      if (array_key_exists($key,$array)) {
        return $array[$key];
      } else {
        return FALSE;
      }
    } else {
      $low=strtolower($key);
      $arrayname=strtolower($arrayname).'[';  // the '[' is important to match the full array name
      $z=strlen($arrayname)+1;

      if (substr($low,0,$z)==$arrayname) { // match $key and $arrayname
      	$k=substr($key,$z);
      	if ($k{strlen($k)-1}==']') $k=substr($k,0,strlen($k)-1);
      	if (array_low_exists($k,$array)) {
          $array=array_change_key_case($array);
          return $array[$k];
        }
      	return null;
      } else {
        return FALSE;
      }
    }
}


// this function exists for auto retrieving the global arrays POST GET SERVER SESSION ENV FILES and COOKIE
// for example, weeGlobal('server[REMOTE_ADDR]') would return the value of $_SERVER['REMOTE_ADDR']
// if the key does not exists, a value of null is returned.
// if the key is not one of the arrays, a value of false is returned.
function wee_val_global($key){
                      $ret=wee_from_array($key,$_SERVER ,'server' );
    if ($ret===false && !empty($_SESSION)) $ret=wee_from_array($key,$_SESSION,'session');
    if ($ret===false) $ret=wee_from_array($key,$_GET    ,'get'    );
    if ($ret===false) $ret=wee_from_array($key,$_POST   ,'post'   );
    if ($ret===false) $ret=wee_from_array($key,$_FILES  ,'files'  );
    if ($ret===false) $ret=wee_from_array($key,$_COOKIE ,'cookie' );
    if ($ret===false) $ret=wee_from_array($key,$_ENV    ,'env'    );

    return $ret;
}


// return an array of all wee keys found in $string
function wee_get_keys($string){
    return array_merge(
       wee_get_keys_se($string,'<wee ','>')
      ,wee_get_keys_se($string,'<weeFor ','>')
      ,wee_get_keys_se($string,'<weeIf ','>')
      ,wee_get_keys_se($string,'<weeLoad ','>')
      ,wee_get_keys_se($string,'<weeProcess ','>')
      ,wee_get_keys_se($string,'{wee ','}')
      ,wee_get_keys_se($string,'{weeFor ','}')
      ,wee_get_keys_se($string,'{weeIf ','}')
      ,wee_get_keys_se($string,'{weeLoad ','}')
      ,wee_get_keys_se($string,'{weeProcess ','}')
    );
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
      error_log("wee_get_keys: unclosed tag ($s missing $e) line ".countenters($string,$pos)." [[".substr($string,$pos,WEE_ERROR_SOURCE_LENGTH)."]]",E_USER_ERROR);
      $pos2=$pos+$sz;
    }
    $pos = strpos($string,$s,$pos2); // find next $s
  }
  return $found;
}


// wee_find_closing($string,$tags,$tagc,$pos)
//   $tags - starting tag string
//   $tagc - closing tag string
//   $pos  - position of tag inside $string
// Find the corresponding closing tag
// If not found - raise an error and return FALSE
function wee_find_closing($string,$tags,$tagc,$pos) {
  // find corresponding closing tag:

  $strlen=strlen($string);
  $posclosing = strpos($string,$tagc,$pos+1); // find first closing tag after $pos
  $pos1 = $pos;
  $pos2 = strrpos($string,$tags,$posclosing-$strlen); // look for a starting tag between $pos and $posclosing
  while ($pos2>$pos1) { 
    $posclosing =  strpos($string,$tagc,$posclosing+1); // find next closing tag
    $pos1 = $pos2; // narrow the search
    $pos2 = strrpos($string,$tags,$posclosing-$strlen); // look for another starting tag between $pos and $posclosing
  }
  
  if ($posclosing===false) {
    error_log("wee_find_closing error: unclosed tag ($tags missing $tagc) line ".countenters($string,$pos)." [[".substr($string,$pos,WEE_ERROR_SOURCE_LENGTH)."]]", E_USER_ERROR); 
    // closing tag not found! this is an outrage!
  }
  
  return $posclosing;
}


// wee_get_key($string,$tags,$tage,$pos) 
//   $tags - start of starting tag string
//   $tage - end of starting tag string
//   $pos  - positino of tag inside $string
// returns the string between $tags and $tage
// if $tage not found, raise an error an return false
function wee_get_key($string,$tags,$tage,$pos) { 
  $pose = strpos($string,$tage,$pos+1);
  if ($pose===false) { // end string not found! oh no!
    error_log("wee_get_key error: unclosed tag ($tags missing $tage) line ".countenters($string,$pos)." [[".substr($string,$pos,WEE_ERROR_SOURCE_LENGTH)."]]", E_USER_ERROR); 
    // end of tag not found, god forbid
    return false;
  } else {
    return substr($string,$pos+strlen($tags),$pose-$pos-strlen($tags));
  }
}

// wee_syntax_test($string)
// check the syntex of the $string
// make sure every starting tag has a closing one
// on success return true
// on fail return an error message
function wee_syntax_test($string) {
  $tags=array('<weeSample>'=>'</weeSample>','<weeIf '=>'</weeIf>','<weeElse>'=>'</weeElse>','<weeFor '=>'</weeFor>'
             ,'{weeSample}'=>'{/weeSample}','{weeIf '=>'{/weeIf}','{weeElse}'=>'{/weeElse}','{weeFor '=>'{/weeFor}'
             );
  foreach($tags as $s => $e) {
    if (substr_count($string,$s)!=substr_count($string,$e)) {
      return "Missing $e";
    }
  }
  return true;
}

/* Process the $string and replace all keys with myTranslation('key name')
*/
function wee_process($string,$myTranslationFunction,$weeloop=0,$weeloopkey=null){
  if ($weeloop==0) { // when inside weeFor there's no need to retest everything
    // test translation function self test
    $test = $myTranslationFunction(WEE_SELF_TEST_KEY);
    if ($test!=WEE_SELF_TEST_VALUE) {
      error_log("wee translate function ($myTranslationFunction) self test fail! (it returned $test)",E_USER_ERROR);
      return false;
    }
    
    // test tags syntax
    $test=wee_syntax_test($string); // check syntax parse
    if ($test!==true) {
      //weedbg("<textarea style='background:#fee;width:100%;height:200px;display:block;border:2px solid red;'>$string</textarea>");
      error_log("wee_syntax_test failed: $test",E_USER_ERROR);
      return false;
    }
  }
  
  //weedbg("<textarea style='background:#fee;width:52%;height:200px;float:left;'>$string</textarea>");
  $string=wee_process_se($string,$myTranslationFunction,'<','>',$weeloop,$weeloopkey);
  $string=wee_process_se($string,$myTranslationFunction,'{','}',$weeloop,$weeloopkey);
  //weedbg("<textarea style='background:#efe;width:48%;height:200px;float:right;'>$string</textarea>");
  return $string;
}

// Process $string, use $s and $tage as the start/end chars for the wee tags. 
function wee_process_se($string,$translate,$s,$e,$weeloop=0,$weeloopkey=null)
{
/*  private
        $tags,       // str, start of starting tag
        $tagc,       // str, closing tag
        $lents,      // int, length of $tags
        $lentc,      // int, length of $tagc
        $pos,        // int, position of <tag start
        $pose,       // int, position of tag end>
        $posclosing, // int, position of </closing tag>
        $posc1,      // int, position of a tag start, for finding the closing tag 
        $posc2,      // int, position of next tag start, for finding the closing tag 
        $key,        // str, the current processing key
        $key2,       // str, the second processing key in weeIf tags
        $val,        // str, the returned value from $translate function
        $val2,       // str, the returned value from $translate function for $key2
        $outString;  // str, the processed output string inside the block, used for recursive tags (weeFor weeInclude weeProcess)
*/      

    ///////////////////////////////////////////
    ///// weeSample - remove weeSample blocks
    ///////////////////////////////////////////
    $tags = "{$s}weeSample{$e}";
    $tagc = "$s/weeSample{$e}";
    $lents = strlen($tags)+strlen($e);
    $lentc = strlen($tagc);

    $pos = strpos($string,$tags);  // find first starting tag
    while ($pos !== false) {
        // find corresponding closing tag:
        $posclosing = wee_find_closing($string,$tags,$tagc,$pos);
        if ($posclosing===false) { // This should not be false, since $string went through a syntax test
            return false;
        } else {
        
          // Remove the weeSample block from $string
//weedbg("removing weeSample:<br>");
//weedbg("<textarea>$string</textarea>");  
          $string = substr($string, 0, $pos) . substr($string, $posclosing + $lentc); // Remove
//weedbg("<textarea>$string</textarea><br>");
        }
      $pos = strpos($string,$tags,$pos); // find next starting tag
    }


    ///////////////////////////////////////////
    ///// weeFor - repeat weeFor blocks recursivly
    ///////////////////////////////////////////
/*    private
        $originalForBlock, // the original string inside the weeFor 
        $loopCondition,    // the stop condition for the loop - result of $translate('for_key')
        $currentLoop;      // current loop incrementing counter
*/

    $tags = "{$s}weeFor ";
    $tagc = "$s/weeFor{$e}";
    $lents = strlen($tags)+strlen($e);
    $lentc = strlen($tagc);

    $pos = strpos($string,$tags);  // find first starting tag
    while ($pos !== false) {
      $key = wee_get_key($string,$tags,$e,$pos);
      if ($key===false) {
        // If $e not found now, it will never be found. no point in keep processing the $string.
        return false;
      } else {
        // find corresponding closing tag:
        $posclosing = wee_find_closing($string,$tags,$tagc,$pos);
        if ($posclosing===false) { // This should not be false, since $string went through a syntax test
            return false;
        } else {
        
          $outString = ''; // the output string to replace the weeFor block content
          $currentLoop=0;
          
          $translate($key,$weeloop,WEE_FOR_INIT); // call to $translate function, prepare it for the weeFor
          
          $loopCondition=$translate($key,$currentLoop); // test whether to continue the loop
          
          $originalForBlock = substr($string,$pos+$lents+strlen($key),$posclosing-$pos-$lents-strlen($key)); // get string inside of weeFor
          while ($loopCondition===TRUE) {
            $currentLoop++;
            $outString .= wee_process($originalForBlock,$translate,$currentLoop,$key);
            $loopCondition=$translate($key,$currentLoop); // test whether to continue the loop
            if ($currentLoop>WEE_ENDLESS_LOOP_LIMIT) {
              error_log("weeFor($key) error: endless loop line ".countenters($string,$pos),E_USER_WARNING);
              $loopCondition=FALSE;
              return false;
            }
          }
          if ($loopCondition!==FALSE) {
            error_log("weeFor($key) error: translate function return non T/F value ($loopCondition) at loop #$currentLoop line ".countenters($string,$pos),E_USER_WARNING);
            return false;
          }
          
//weedbg("putting loop final result:<br>");
//weedbg("<textarea>$outString</textarea><br>");  
//weedbg("<textarea>$string</textarea>");  
          $string = substr($string,0,$pos) . $outString . substr($string,$posclosing+$lentc);
//weedbg("<textarea>$string</textarea><br>");  
        }
      } 
      $pos = strpos($string, $tags, $pos); // find next starting tag
    }


    ///////////////////////////////////////////
    ///// weeIf - conditionally put values
    ///////////////////////////////////////////
/*    private
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
      $val1,        // value for $key1 from $translate function
      $val2,        // value for $key2 from $translate function
*/    
    // available operators:
    $operators=array(
       '>=' => array('>=','=>',' gte '),
       '<=' => array('<=','=<',' lte '),
       '!=' => array('!=',' ne ',' d ',' dif ',' diff '),
       '='  => array('==','=',' e '),
       '>'  => array('>',' gt '),
       '<'  => array('<',' lt '),
       '%'  => array('%',' mod ')
    );
    
    $tags = "{$s}weeIf ";
    $tagc = "$s/weeIf{$e}";
    $lents = strlen($tags)+strlen($e);
    $lentc = strlen($tagc);

    $pos = strpos($string,$tags);  // find first starting tag
    while ($pos !== false) {
      $key = wee_get_key($string,$tags,$e,$pos);
      if ($key===false) {
        // If $e not found now, it will never be found. no point in keep processing the $string.
        return false; // abort processing
      } else {
        // find corresponding closing tag:
        $posclosing = wee_find_closing($string,$tags,$tagc,$pos);
        if ($posclosing===false) { // This should not be false, since $string went through a syntax test
            return false;
        } else {

        // find the operator
        $operator=''; // the operator that will be found
        $posOp=0;     // the operator position to be found
        $pos1=0; // temp position of operator
        foreach ($operators as $Key=>$Vals) {
          foreach ($Vals as $Val) {
            if ($posOp==0) {
              $pos1=strpos($key,$Val);
              if ($pos1!==FALSE) { // operator found!
                $posOp=$pos1;
                $opLen=strlen($Val);
                $operator=$Key;
              }
            }
          }
        }
        
        if ($posOp==0) { // no operator error
          error_log("weeIf($key) syntax error: operator not found line ".countenters($string,$pos),E_USER_ERROR);
          $IfResult = FALSE;
        } else {
          $key1 = trim(substr($key,0,$posOp));
          $key2 = trim(substr($key,$posOp+$opLen));
          if (is_numeric($key1)) {
            $val1=$key1;
          } else {
            $val1 = $translate($key1,$weeloop,$weeloopkey);
          }
          if (is_numeric($key2)) {
            $val2=$key2;
          } else {
            $val2 = $translate($key2,$weeloop,$weeloopkey);
          }

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
              // unknown operator? that's strange! because it was found using the $operators array
              error_log("weeIf($key) syntax error: operator unknown ($operator) line ".countenters($string,$pos),E_USER_WARNING);
              $IfResult = FALSE;
            break;
          }
        }

        // handle weeIf result
        //weedbg("weeIf($key)�($key1$operator$key2)�($val1$operator$val2)�".var_export($IfResult,1));
//weedbg("weeifing ".var_export($IfResult,1)."<br>");
//weedbg("<textarea>$string</textarea>");  
        if ($IfResult) { // true - remove only the tags
          $string = substr($string, 0, $pos) . substr($string,$pos+$lents+strlen($key), $posclosing-($pos+$lents+strlen($key))) . substr($string,$posclosing+$lentc); // remove tags
        } else { // remove entire block
          $string = substr($string, 0, $pos) . substr($string, $posclosing+$lentc); // remove entire block
        }
//weedbg("<textarea>$string</textarea><br>");  
        
      }
    }
    $pos = strpos($string, $tags, $pos); // find next starting tag
  }

    
    ///////////////////////////////////////////
    ///// wee - replace wee keys with their translated value
    ///////////////////////////////////////////
    $tags = "{$s}wee ";

    $pos = strpos($string,$tags);  // find first starting tag
    while ($pos !== false) {
      $key = wee_get_key($string,$tags,$e,$pos);
      if ($key===false) {
        // If $e not found now, it will never be found. no point in keep processing the $string.
        return false; // abort processing
      } else {

        $val = $translate($key,$weeloop,$weeloopkey);
//weedbg("replacing wee($key) with $val<br>");
//weedbg("<textarea>$string</textarea>");  
        $string = substr($string, 0, $pos) . $val . substr($string, $pos+strlen($tags)+strlen($key)+strlen($e)); // replace
//weedbg("<textarea>$string</textarea><br>");  
      }
      $pos = strpos($string, $tags, $pos); // find next starting tag
    }

    
    ///////////////////////////////////////////
    ///// weeProcess - process a wee string retrieved from $translate function 
    ///////////////////////////////////////////
    $tags = "{$s}weeProcess ";

    $pos = strpos($string,$tags);  // find first starting tag
    while ($pos !== false) {
      $key = wee_get_key($string,$tags,$e,$pos);
      if ($key===false) {
        // If $e not found now, it will never be found. no point in keep processing the $string.
        return false; // abort processing
      } else {

        $val = $translate($key,$weeloop,$weeloopkey);
        $outString = wee_process($val,$translate,$weeloop,$weeloopkey);
        $string = substr($string, 0, $pos) . $outString . substr($string, $pos+strlen($tags)+strlen($key)+strlen($e)); // replace
      }
      $pos = strpos($string, $tags, $pos); // find next starting tag
    }
    
    
    ///////////////////////////////////////////
    ///// weeInclude - include an external file and process it
    ///////////////////////////////////////////
    $tags = "{$s}weeInclude ";

    $pos = strpos($string,$tags);  // find first starting tag
    while ($pos !== false) {
      $key = wee_get_key($string,$tags,$e,$pos);
      if ($key===false) {
        // If $e not found now, it will never be found. no point in keep processing the $string.
        return false; // abort processing
      } else {

        $outString = '';
        if (strpos($key,'.')!==FALSE && file_exists($key)) { // lets try load the file, who knows...
          $outString = file_get_contents($key);
        } else {
          $val = $translate($key,$weeloop,$weeloopkey);
          if (!file_exists($val)) {
            error_log("weeInclude($key) error: file not found $val line ".countenters($string,$pos),E_USER_ERROR);
          } else {
            $outString = file_get_contents($val);
          }
        }
        
        $outString = wee_process($outString,$translate,$weeloop,$weeloopkey);
        $string = substr($string, 0, $pos) . $outString . substr($string, $pos+strlen($tags)+strlen($key)+strlen($e)); // replace
      }
      $pos = strpos($string, $tags, $pos); // find next starting tag
    }
    
    return $string; 

} // end of wee_process()



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
            <wee user_name><weeSample>My Name</weeSample>
        </td>
        <td align=center class=links>
            <wee user_links><weeSample><a href=#>Doggy</a> <a href=#>Catty</a></weeSample>
        </td>
    </tr>
</a>
</weeFor>
</table>
*/


?>
