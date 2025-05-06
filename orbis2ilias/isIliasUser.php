<?php 
    chdir(dirname(__FILE__));
    chdir('..');
    require_once("./orbis2ilias/ilias.class.php");

    $retObj = (object)[
        'isIliasUser' => false,
        'errNumber' => 0,
        'errMessage' => ""
    ];

    function console_log($data, $s = "") {
        if ($s === "")
            $consoleOutput = '<script>console.log('.json_encode(gettype($data),JSON_HEX_TAG).','.json_encode($data,JSON_HEX_TAG).');</script>';
        else
            $consoleOutput = '<script>console.log('.json_encode($s,JSON_HEX_TAG).','.json_encode(gettype($data),JSON_HEX_TAG).','.json_encode($data,JSON_HEX_TAG).');</script>';
        echo $consoleOutput;
    }

    $jsonData = file_get_contents("php://input");
    if (!empty($jsonData)) {
        $pdata = json_decode($jsonData,false);
        console_log($pdata, 'PHP(bfe) $pdata: ');

        if (!($ILIAS = new ILIASconnection())) {
            $retObj->errNumber = -1;
            $retObj->errMessage = "ILIASconnection fehlgeschlagen";
            echo json_encode($retObj);
            exit(0);
        }
        try {
            $sid = $ILIAS->soap_connect(); //var_dump($sid);
            $uhandle = $ILIAS->finduserhandle($pdata->username);
            if ( $uhandle < 1 ) {
                $retObj->isIliasUser = false;
            } else {
                $retObj->isIliasUser = true;
            }

            $retObj->errNumber = 0;
            $retObj->errMessage = "";
            console_log($retObj, 'PHP(bfe) $retObj: ');
            echo json_encode($retObj);
            exit(0);
        } catch ( Exception $e ) {
            $retObj->errNumber = -2;
            $retObj->errMessage = "ILIAS soap_connect fehlgeschlagen";
            console_log($retObj, 'PHP(bfe) $retObj: ');
            echo json_encode($retObj);
            exit(0);
        }
    } else {
        $retObj->errNumber = -4;
        $retObj->errMessage = "PostData leer";
        console_log($retObj, 'PHP(bfe) $retObj: ');
        echo json_encode($retObj);
        exit(0);
    }
?>
