<?php
if (count($argv) < 2) {
    echo "Database Name not specified. Specify the DatabaseName and credentials or the DSN alias to which the program needs to connect\n";
    die();
}

$databaseName = $argv[1];
$conn_string = "";
$uNameArg    = "";
$pwdArg      = "";

if (count($argv) == 6) {
    $hostName = $argv[2];
    $port = $argv[3];
    $userName = $argv[4];
    $password = $argv[5];
    
    $conn_string = "DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$databaseName;HOSTNAME=$hostName;PORT=$port;PROTOCOL=TCPIP;UID=$userName;PWD=$password;";
    $pdo_conn_string = "ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE=$databaseName;HOSTNAME=$hostName;PORT=$port;PROTOCOL=TCPIP;UID=$userName;PWD=$password;";
} elseif (count($argv) == 4) {
    $conn_string = $databaseName;
    $pdo_conn_string = "ibm:DSN=$databaseName";
    $uNameArg    = $argv[2];
    $pwdArg      = $argv[3];
} elseif(count($argv) == 2){
    $conn_string = $databaseName;
    $pdo_conn_string = "ibm:DSN=$databaseName";
} else {
    echo "Wrong number of arguments. Specify the DatabaseName and credentials or the DSN alias to which the program needs to connect\n";
    die();
}

$conn = db2_connect($conn_string, $uNameArg, $pwdArg);
if ($conn) {
    echo "Connection to database $databaseName, using ibm_db2 module, is successful\n";
    db2_close($conn);
} else {
    echo "Connection to database $databaseName, using ibm_db2 module, failed with message: " . db2_conn_errormsg() . "\n";
}
try{
    $pdo_conn = new PDO($pdo_conn_string, $uNameArg, $pwdArg);
    echo "Connection to database $databaseName, using pdo_ibm module, is successful \n";
} catch (PDOException $e) {
    echo "Connection to database $databaseName, using pdo_ibm module, failed with message: " . $e->getMessage() . "\n";
}
?>
