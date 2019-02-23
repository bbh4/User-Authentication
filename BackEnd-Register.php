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
$register_channel = $connection->channel();

$register_channel->exchange_declare('RegisterExchange', 'direct', false, false, false);

list($register_queue_name, ,) = $register_channel->queue_declare('', false, false, true, false);
$register_channel->queue_bind($register_queue_name, 'RegisterExchange', 'register_req');// login_req routing name

$register_callback = function ($req) {
    echo '<h3>', ' [x] Received from RegisterExchange: ', $req->body, '</h3>', "\n";
};

echo "<h3>" , " [*] Waiting for messages. To exit press CTRL+C" , "</h3>\n";
$register_channel->basic_consume($register_queue_name, '', false, true, false, false, $register_callback);
while (true) {
    $register_channel->wait();
}

$register_channel->close();
$connection->close();

?>