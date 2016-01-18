<?php

$Module = array( 'name' => 'ocmaps' );
$ViewList = array();
 
// new View list with 2 fixed parameters and 
// 2 parameters in order 
// http://.../modul1/list/ $Params['ParamOne'] /
// $Params['ParamTwo']/ param4/$Params['4Param'] /param3/$Params['3Param'] 
 
$ViewList['list'] = array( 'script' => 'list.php');

?>