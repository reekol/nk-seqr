<?php

class secure{
    private $alg 	= 'AES-128-ECB';
    public  function encode($o,$p){ return openssl_encrypt( $o , $this->alg, $p, false); }
    public  function decode($o,$p){ return openssl_decrypt( $o , $this->alg, $p, false); }
}

function filter(string $type,string $var){
    if($type === 'alphaNum') return preg_replace('/[^a-zA-Z0-9]/', '_', $var);
    return false;
}

function getRegisteredSalt($uid,$full = false){
    $file = './../salts/uid-'.$uid.'.php';
    if(file_exists($file)){
        $res = include($file);
        return ($full?$res:$res['token']);
    }else{
        return false;
    }
}

function getDatForUid($uid,$key){
    $file = './../salts/uid-'.$uid.'.php';
    if(file_exists($file)){
        $res = include($file);
        return $res[$key];
    }else{
        return false;
    }
}

function registerSalt($uid,$token = null){
    if($token === null) $token = bin2hex(random_bytes(16));
    file_put_contents('./../salts/uid-'.$uid.'.php','<?php return '.var_export(['token' => $token,'stats' => []],true).';');
    return $token;
}

function addDatToSalt($uid,$key,$val){
    $token = '';
    if(file_exists('./../salts/uid-'.$uid.'.php')){
        $res = require('./../salts/uid-'.$uid.'.php');
        $res[$key] = $val;
        file_put_contents('./../salts/uid-'.$uid.'.php','<?php return '.var_export($res,true).';');
        $token = $res['token'];
    }
    return $token;
}

function unregisterSalt($uid){
    $file = './../salts/uid-'.$uid.'.php';
    return unlink($file);
}


function setQrData(string $pwd,array $meta,string $text,$writeCode = false){
    // Anatomy of seqr
    // A- UUID - Uncrypted
    // B- Encrypted block  -\========================== 
    //   1).................| Hash of -\
    //   2).................|          |-> Meta [name,limit,remote,etc]
    //   3).................|          |-> Text content
    //                      \==========================
    // Separator between A-B blocks is space.
    // Separator between 1-2-3 blocks is space.
    // Note. Block B is encrypted, so no separator exists before decryption
    // Hash of the meta+content is inside encrypted block for a securtity reaons. ( should ot be available before decryption )

    $secure = new secure;
    $text = substr($text,0,TXT_LIMIT);
    foreach($meta as $k=>$v) $meta[$k] = preg_replace("/[^\w.]+/", "_", $v);
    $meta = implode(',',$meta);
    $uc   = $meta.' '.$text;
    $hash = md5($uc);
    $toEnrypt = $hash.' '.$uc; 
    $uid = getId();
    $securedUid = $secure->encode($uid,$pwd);
    $tag = current(explode(':',$pwd));

    if( $tag === 'RAW'  ){
        $salt = '';
        $secret = $pwd.$salt;
        return [
                'uid'       => $uid,
                'meta'      => explode(',',$meta),
                'content'   => $securedUid . ' ' . $secure->encode($toEnrypt,$secret),
                'salt'      => $salt
            ];
    }else if( $tag === 'TOTP' ){
        $salt = registerSalt($uid);
        addDatToSalt($uid,'type','totp');
        $seed = explode(':',$pwd);
        $seed = filter('alphaNum',end($seed));
        addDatToSalt($uid,'seed',$seed);
        $secret = $pwd.$salt;
        return [
                'uid'       => $uid,
                'meta'      => explode(',',$meta),
                'content'   => "$tag:$uid" . ' ' . $secure->encode($toEnrypt,$secret),
                'salt'      => $salt
            ];
    }else if( $tag === 'BOMB' ){
        $salt = registerSalt($uid);
        addDatToSalt($uid,'type','bomb');
        $seed = explode(':',$pwd);
        $secret = $pwd.$salt;
        $encrypted = $secure->encode($toEnrypt,$secret);
        $url = 'http://seqr.link/#'.$uid;
        addDatToSalt($uid,'times',(int) (end($seed)));
        addDatToSalt($uid,'content',$encrypted);
        return [
                'uid'       => $uid,
                'url'       => $url,
                'meta'      => explode(',',$meta),
                'content'   => $url,
                'salt'      => $salt
            ];
    }else{
        $salt = registerSalt($uid);
        addDatToSalt($uid,'type','regular');
        $secret = $pwd.$salt;
        return [
                'uid'       => $uid,
                'meta'      => explode(',',$meta),
                'content'   => $securedUid . ' ' . $secure->encode($toEnrypt,$secret),
                'salt'      => $salt
            ];
    }
}

function getQrData(string $pwd,string $raw){
        $secure = new secure;
        $err = 0;
        $name = $text = $content = null;
        $qrtext = explode(' ',$raw);
        $uid = $qrtext[0];
        $idType = current(explode(":",$qrtext[0]));
        if($idType === 'TOTP'){
            $uid = explode(":",$uid);
            $uid = end($uid);
            $salt = getRegisteredSalt($uid);
            $seed = getDatForUid($uid,'seed');
            $seed = filter('alphaNum',$seed);
            $currentPass = trim(`oathtool --totp -b $seed`); // Point of injection
            if($currentPass === $pwd){
                $pwd = $idType.':'.$seed;
            }
        }else{
             $uid = $secure->decode($qrtext[0],$pwd);
        }
        $tag = current(explode(":",$pwd));
        $salt = ($tag === 'RAW' ? '' : getRegisteredSalt($uid));
        if($salt === false){
            $err = 2; // Unrekognized QR for this system.
        }else{
            $content = $secure->decode(end($qrtext),$pwd.$salt);
            $hash = strstr($content,' ',true);
            $content = substr($content,strlen($hash)+1);
            if($hash === md5($content)){ // validate decryption ( else passord is wrong )
                $meta = strstr($content,' ',true);
                $content = substr($content,strlen($meta)+1);
                $meta = explode(',',$meta);
                $name = $meta[0];
            }else{
                $err = 3; // wrong password ( hash does not mach the content )
            }
        }
        return ['name' => $name,'uid' => $uid,'text' => $content,'err'  => $err];
}

function processIncomingFile(string $uploadField,string $alternateField){
    if(isset($_POST[$alternateField]) && !empty($_POST[$alternateField])){
        $upload_dir = '/tmp';
        $img = $_POST[$alternateField];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $file = $upload_dir . '/php-file-' . microtime(true);
        $_FILES[$uploadField]["tmp_name"] = $file;
        file_put_contents($file, $data);
    }
    return $_FILES[$uploadField]["tmp_name"];
}


function zbarImg($file){
    $cmd = 'zbarimg --raw -q '.$file;
    return trim(exec($cmd,$resArr));
}

function qrOcr($file){
    `mogrify -rotate "360" -resize 500x500 $file`; //recreate ( rotate 360 degree ) and resize image ( prevent code injection )
    $check = getimagesize($file);
    if(!$check) return false;
    return zbarImg($file);
}

function generateQr($content,int $correction=3,int $margin = 2){
    $correctionLevels=['L','M','Q','H'];
    $correction = (isset($correctionLevels[$correction]) ? $correctionLevels[$correction] : end($correctionLevels));
    return `qrencode -l $correction -m $margin -t svg "$content"`;
}

function short($s){ 
    return base64_encode(pack('H*', md5($s))); 
}
function long($s){ 
    return current(unpack('H*', base64_decode($s))); 
}
