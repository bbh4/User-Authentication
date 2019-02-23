<?php
    require_once __DIR__ . '/vendor/autoload.php';
    use PhpAmqpLib\Connection\AMQPStreamConnection;
    use PhpAmqpLib\Message\AMQPMessage;

    $connection = new AMQPStreamConnection('172.17.0.2', 5672, 'guest', 'guest');
    $channel = $connection->channel();

    $channel->exchange_declare('RegisterExchange', 'direct', false, false, false);

    $msg = new AMQPMessage('bob~pass');

    $channel->basic_publish($msg, 'RegisterExchange', 'login_req');
    
    echo " [x] Sent Msg to Register Exchange\n";
    
    $channel->close();
    $connection->close();
?>