<?php 

/** 
    Depends on zbar-tools and qrencode wamerican
    To install$ apt-get install zbar-tools qrencode wamerican imagemagick oathool
**/

include('../php/init.php'); /// Settings and globals
include('../php/functions.php'); /// Helpers

if(!isset($OUT['err'])){
    $_SESSION["token"] = getId();

    if(isset($_REQUEST['t']) && trim($_REQUEST['t']) !=='' && !isset($_GET['id'])){
    /** 
        Creating a new QR image
    **/
        $name = ($_REQUEST['n']?$_REQUEST['n']:'Image').'.qr.png';
        $meta = [$name,0,1,2];
        $data = setQrData($_REQUEST['p'],$meta,$_REQUEST['t']);
//                addDatToSalt($data['uid'],'encrypted',$data['content']);
        $OUT = [
            "uid" => $data['uid'],
            "url" => isset($data['url']) ? $data['url'] : '',
            "nme" =>$data['meta'][0],
            "svg" => generateQr($data['content'], (int) $_REQUEST['correction'], (int) $_REQUEST['margin']),
            "rec" => $data['salt']
        ];

    }else if(isset($_FILES["fileToUpload"])){
    /** 
        Reading QR image
    **/
        $raw  = (string) qrOcr(processIncomingFile('fileToUpload','camFile'));

        if($raw === ''){
            $OUT['err'] = 1; // Unale toread uploaded file
        }else{
            $_REQUEST['p'] = isset($_REQUEST['p']) ? $_REQUEST['p'] : '';
            $data = getQrData($_REQUEST['p'],$raw);
            if(!$data['err'] && isset($_REQUEST['action']) && $_REQUEST['action'] === '1')
            {
                unregisterSalt($data['uid']);
            }
            $OUT = [
                "uid" => $data['uid'],
                "txt" => $data['text'],
                "nme" => $data['name'],
                "err" => $data['err'],
                "raw" => $raw,
                "svg" => generateQr($raw),
            ];
        }
    }else if(isset($_REQUEST['rand'])){
    /**
        Generating randomness
    **/
        $rend = trim($_REQUEST['rand']);
        sleep(1);
             if($_REQUEST['rand'] === 'word') $OUT['pwd'] = trim(`shuf -n 1 /usr/share/dict/words`);
        else if($_REQUEST['rand'] === 'hash') $OUT['pwd'] = bin2hex(random_bytes(4));
        else if($_REQUEST['rand'] === 'totp'){
            $seed = bin2hex(random_bytes(15));
            $secret = trim(`oathtool --totp -v $seed | grep Base32 | cut -d ' ' -f3`);
            $OUT['pwd'] = 'TOTP:'.$secret; // Base32 version of the seed.
            $OUT['svg'] = generateQr("otpauth://totp/web@seqr.link?secret={$secret}");
        }
        else if($_REQUEST['rand'] === 'hotp'){
            $seed = bin2hex(random_bytes(15));
            $secret = trim(`oathtool --hotp -v $seed | grep Base32 | cut -d ' ' -f3`);
            $OUT['pwd'] = 'HOTP:'.$seed;
            $OUT['svg'] = generateQr("otpauth://hotp/web@seqr.link?secret={$secret}");
        }
        else if($_REQUEST['rand'] === 'bomb'){
            $OUT['pwd'] = 'BOMB:1';
        }
    }else if(isset($_REQUEST['doc'])){
    /**
        GETTING README.md
    **/
        $OUT['readme'] = file_get_contents('./../README.md');
    }else if(isset($_REQUEST['return']) && $_REQUEST['return'] === 'ip'){
        $OUT['res'] = $_SERVER['REMOTE_ADDR'];
    }else if(isset($_REQUEST['return']) && $_REQUEST['return'] === 'server'){
        $OUT['res'] = getallheaders();
    }else if(isset($_REQUEST['return']) && $_REQUEST['return'] === 'ls'){
        $OUT['readme'] = `ls ../salts | sed 's/.php//g'`;
    }
}

header('Content-type: application/json');
$OUT['csrf'] = $_SESSION['token'];
echo json_encode($OUT);
