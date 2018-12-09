<?php

error_reporting( E_ALL );
ini_set('display_errors', 1);

ini_set('memory_limit','2048M');
header("strict-transport-security: max-age=600");
session_start();

define('CSRF',true);
define('TXT_LIMIT',250);

$OUT['lmt'] = TXT_LIMIT;

function getId(){ return md5(uniqid(mt_rand(), true));  }

if(!isset($_SESSION['token'])) $_SESSION["token"] = getId();

if(CSRF && !empty($_POST)){
    if( !isset($_REQUEST['CSRF']) || $_REQUEST['CSRF'] !== $_SESSION['token']){
        $_SESSION["token"] = getId();
        $OUT['err'] = 10;
    }
}
