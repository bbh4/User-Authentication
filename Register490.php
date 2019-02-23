<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = 'http://127.0.1.1:8080'; // where the rabbitmq server is
$port = 5672; // port number of service
$user = 'guest'; // username to connect to service
$pass = 'guest'; // pass to connect to service
$exchange = 'testExchange';
$queue = 'testQueue';
$vhost = ''
$servername = "server name";
$dbaseUser = "dbase user";
$dbasePass = "dbase password";
$dbasename = "dbase name";

$connection = new AMQPStreamConnection($host, $port, $user, $pass, $vhost);
$channel  = $connection->channel();

$channel->exchange_declare('RegisterExchange', 'direct', false, false, false);
list($queue_name, ,) = $channel->queue_declare('', false, false, true, false);
$channel->queue_bind($queue_name, 'RegisterExchange', 'register_req');

//register
$register_callback = function ($req) {
    $result = explode('~', $req->body);
    $user = $result[0];
    $pass = $result[1];
    $error = "1";
    $success = "0";
    $passhash = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $pdo = new PDO("mysql:host=$servername; dbname = $dbasename", $dbaseUser, $dbasePass);
 
        // calling stored procedure command
        $sql = 'CALL insertUser(?,?)';
 
        // prepare for execution of the stored procedure
        $stmt = $pdo->prepare($sql);
 
        // pass value to the command
        $stmt->bindParam(1, $user, PDO::PARAM_STR);
        $stmt->bindParam(2, $pass, PDO::PARAM_STR);
 
        // execute the stored procedure
        $isSuccessful = $stmt->execute();
 
        $stmt->closeCursor();
        
        if ($isSuccesful)
        {
            $msg = new AMQPMessage ( 
                $success,
                array('correlation_id' => $req->get('correlation_id'))
            );
        }else
        {
            $msg = new AMQPMessage ( 
                $error,
                array('correlation_id' => $req->get('correlation_id'))   
            );
        }

 
    } catch (PDOException $e) {
        echo "Error occurred:" . $e->getMessage();
    }

    $req->delivery_info['channel']->basic_publish( $msg, '', $req->get('reply_to'));

};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queue_name, '', false, false, false, false, $register_callback);

while (true) {
    $channel->wait();
}  

$channel->close();
$connection->close();
?>
