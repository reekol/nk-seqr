<?php 

/** 
    Depends on zbar-tools qrencode
    To install$ apt-get install zbar-tools qrencode
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
            "nme"=>$data['meta'][0],
            "svg" => generateQr($data['content'], (int) $_REQUEST['correction'], (int) $_REQUEST['margin']),
//            "rec" => $data['salt']
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
                "svg" => generateQr($raw),
            ];
        }
    }else if(isset($_REQUEST['rand'])){
    /**
        Generating randomness
    **/
        sleep(1);
        $OUT['pwd'] = trim($_REQUEST['rand'] === 'word' ? `shuf -n 1 assets/words.txt` : bin2hex(random_bytes(4)));
    }
}

header('Content-type: application/json');
$OUT['csrf'] = $_SESSION['token'];
echo json_encode($OUT);
