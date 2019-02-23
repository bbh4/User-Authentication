<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = 'http://127.0.1.1:8080'; // where the rabbitmq server is
$port = 5672; // port number of service
$user = 'guest'; // username to connect to service
$pass = 'guest'; // pass to connect to service
$login_exchange = 'LoginExchange';
$register_exchange = 'RegisterExchange';
$vhost = ''

$connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
$channel  = $connection->channel();

$channel->exchange_declare('testExchange', 'fanout', false, false, false);
$channel->queue_declare($queue, passive:true, durable:true, exclusive:false, auto_delete:false);
$channel->queue_bind($exchange, 'testExchange');

$servername = "server name";
$dbaseUser = "dbase user";
$dbasePass = "dbase password";
$dbasename = "dbase name";

$pdo = new PDO("mysql:host=$servername;dbname=$dbasename", $dbaseUser, $dbasePass);

function getData(int $customerNumber) {
    try {
        $pdo = new PDO("mysql:host=$host; dbname = $dbname", $username, $password);
 
        // calling stored procedure command
        $sql = 'CALL GetData( ? , ? )';
 
        // prepare for execution of the stored procedure
        $stmt = $pdo->prepare($sql);
 
        // pass value to the command
        $stmt->bindParam('1', $customerNumber, PDO::PARAM_INT);
 
        // execute the stored procedure
        $stmt->execute();
 
        $stmt->closeCursor();
 
        // execute the second query to get customer's level
        $row = $pdo->query("SELECT @level AS level")->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row !== false ? $row['level'] : null;
        }
    } catch (PDOException $e) {
        die("Error occurred:" . $e->getMessage());
    }
    return null;
}

//register
$callback1 = function ($req) {
    $result = explode('~', $req->body);
    $user = $result[0];
    $pass = $result[1];
    
    $regUsername = "";
    $errors = array();
    
    $regUsername = mysqli_real_escape_string($dbase, $user);
    $regPassword = mysqli_real_escape_string($dbase, $pass]);
    
    $msg = new AMQPMessage( , array('correlation_id' => $req->get('correlation_id')));

    if (empty($regUsername)) { array_push($errors, "Username is required"); }
    if (empty($regPassword)) { array_push($errors, "Password is required"); }

    $userCheck = "SELECT * FROM TABLE_NAME_REQUIRED WHERE USERNAME_FIELD = '$regUsername' LIMIT 1";
    $result = mysqli_query($dbase, $$userCheck);
    $userFound = mysqli_fetch_assoc($result);

    if ($userFound['username'] == $regUsername) {array_push($errors, "Username already exists"); }

    if (count($errors) == 0) {
        //$regPassword = password_hash($regPassword, PASSWORD_DEFAULT);
        //password_verify($user_password, $hash_from_db);
        $newReg = "INSERT INTO TABLE_NAME_REQUIRED (username, password) VALUES ('$regUsername', $regPassword')";
        mysqli_query($dbase, $newReg);
        $msg = new AMQPMessage( "0", array('correlation_id' => $req->get('correlation_id')));
        $req->delivery_info['channel']->basic_publish( $msg, '', $req->get('reply_to')
    }else{
        $msg = new AMQPMessage( "1", array('correlation_id' => $req->get('correlation_id')));
        $req->delivery_info['channel']->basic_publish( $msg, '', $req->get('reply_to')
    }
};

//login
$callback2 = function ($req) {
    $result = explode('~', $req->body);
    $user = $result[0];
    $pass = $result[1];

    $regUsername = "";
    $errors = array();
    
    $logUsername = mysqli_real_escape_string($dbase, $user);
    $logPassword = mysqli_real_escape_string($dbase, $pass);
    if (empty($logUsername)) {array_push($errors, "Username is required");}
    if (empty($logPassword)) {array_push($errors, "Password is required");}
    $query = "SELECT * FROM TABLE_NAME_REQUIRED WHERE Insert_Username_Field='$regUsername' AND Insert_Password_Field='$regPassword'";
    $results = mysqli_query($dbase, $query);
        if (mysqli_num_rows($results) == 1) {
        $_SESSION['username'] = $regUsername;
        }
    
    if (count($errors) == 0) {
        $msg = new AMQPMessage("0", array('correlation_id' => $req->get('correlation_id'))
        );
        $req->delivery_info['channel']->basic_publish( $msg, '', $req->get('reply_to')
        }else {
        array_push($errors, "Error: Wrong username/password combination.");
        $msg = new AMQPMessage("1", array('correlation_id' => $req->get('correlation_id')));
        $req->delivery_info['channel']->basic_publish( $msg, '', $req->get('reply_to')
        }
}

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queue, '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}  

$channel->close();
$connection->close();
?>