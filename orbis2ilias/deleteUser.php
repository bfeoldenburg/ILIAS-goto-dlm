<?php 
chdir(dirname(__FILE__));
    chdir('..');
    require_once("./orbis2ilias/ilias.class.php");

    $uLogin = "mktest42";

    $retObj = (object)[
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


    if (!($ILIAS = new ILIASconnection())) {
        $retObj->errNumber = -1;
        $retObj->errMessage = "ILIASconnection fehlgeschlagen";
        echo json_encode($retObj);
        exit(0);
    }
    try {
        $sid = $ILIAS->soap_connect(); //var_dump($sid);
        $uhandle = $ILIAS->finduserhandle($uLogin);
        if ( $uhandle > 0 ) {
            $rv = $ILIAS->removeuser2($uLogin);
            console_log($rv, 'PHP(deleteUser) $rv: ');
            $retObj->errNumber = 0;
            $retObj->errMessage = $rv;
        } else {
            $retObj->errNumber = -5;
            $retObj->errMessage = 'removeuser2 fehlgeschlagen';
        }
        console_log($retObj, 'PHP(deleteUser) $retObj: ');
        echo json_encode($retObj);
        exit(0);
    } catch ( Exception $e ) {
        $retObj->errNumber = -2;
        $retObj->errMessage = 'ILIAS soap_connect fehlgeschlagen';
        console_log($retObj, 'PHP(deleteUser) $retObj: ');
        echo json_encode($retObj);
        exit(0);
    }
?>
