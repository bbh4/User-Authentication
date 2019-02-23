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
$vhost = '';
$servername = "server name";
$dbaseUser = "dbase user";
$dbasePass = "dbase password";
$dbasename = "dbase name";

//$pdo = new PDO("mysql:host=$servername;dbname=$dbasename", $dbaseUser, $dbasePass);


$connection = new AMQPStreamConnection('172.17.0.2', 5672, 'guest', 'guest');
$login_channel = $connection->channel();

$login_channel->exchange_declare('LoginExchange', 'direct', false, false, false);

list($login_queue_name, ,) = $login_channel->queue_declare('', false, false, true, false);
$login_channel->queue_bind($login_queue_name, 'LoginExchange', 'login_req');// login_req routing name

$login_callback = function ($req) {
    echo '<h3>', ' [x] Received from LoginExchange: ', $req->body, '</h3>', "\n";
};

echo "<h3>" , " [*] Waiting for messages. To exit press CTRL+C" , "</h3>\n";
$login_channel->basic_consume($login_queue_name, '', false, true, false, false, $login_callback);
while (true) {
    $login_channel->wait();
}

$login_channel->close();
$connection->close();

?>