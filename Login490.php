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

$pdo = new PDO("mysql:host=$servername;dbname=$dbasename", $dbaseUser, $dbasePass);

$connection = new AMQPStreamConnection($host, $port, $user, $pass, vhost);
$channel  = $connection->channel();

$channel->exchange_declare('LoginExchange', 'direct', false, false, false);
list($queue_name, ,) = $channel->queue_declare('', false, false, true, false);
$channel->queue_bind($queue_name, 'LoginExchange', 'login_req');

//login
$login_callback = function ($req) {
    $result = explode('~', $req->body);
    $user = $result[0];
    $pass = $result[1];
    $error = "1";
    $success = "0";
    
    try {
        $pdo = new PDO("mysql:host=$host; dbname = $dbname", $username, $password);
 
        // calling stored procedure command
        $sql = 'CALL getPassword(?)';
 
        // prepare for execution of the stored procedure
        $stmt = $pdo->prepare($sql);
 
        // pass value to the command
        $stmt->bindParam(1, $user, PDO::PARAM_STR);
 
        // execute the stored procedure
        $isSuccessful = $stmt->execute();
        $stmt->closeCursor();
        
        $db_response = $stmt->fetch();
        
        if ($isSuccessful) {
            if (empty($db_response))
            {
                $msg = new AMQPMessage ( 
                    $error,
                    array('correlation_id' => $req->get('correlation_id'))
                );
            }else
            {
                $passver = password_verify($pass, $hash_from_db);  
                if($passver){
                    $msg = new AMQPMessage ( 
                        $success,
                        array('correlation_id' => $req->get('correlation_id'))
                    );
                }else{
                    $msg = new AMQPMessage ( 
                        $error,
                        array('correlation_id' => $req->get('correlation_id'))
                    );
                 }
            }
        } else {
            $msg = new AMQPMessage ( 
                $error,
                array('correlation_id' => $req->get('correlation_id'))
            );
        }
 
    } catch (PDOException $e) {
        die("Error occurred:" . $e->getMessage());
    }

    $req->delivery_info['channel']->basic_publish( $msg, '', $req->get('reply_to'));

};

$channel->basic_qos(null, 1, null);
$channel->basic_consume($queue, '', false, false, false, false, $login_callback);

while (true) {
    $channel->wait();
}  

$channel->close();
$connection->close();
?>
