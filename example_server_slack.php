<?php
    require_once __DIR__ . '/vendor/autoload.php';
    use PhpAmqpLib\Connection\AMQPStreamConnection;

    $connection = new AMQPStreamConnection('172.17.0.2', 5672, 'guest', 'guest');
    $login_channel = $connection->channel();
    $register_channel = $connection->channel();

    $login_channel->exchange_declare('LoginExchange', 'direct', false, false, false);
    $register_channel->exchange_declare('RegisterExchange', 'direct', false, false, false);

    list($login_queue_name, ,) = $login_channel->queue_declare('', false, false, true, false);
    $login_channel->queue_bind($login_queue_name, 'LoginExchange', 'login_req');// login_req routing name

    list($register_queue_name, ,) = $register_channel->queue_declare('', false, false, true, false);
    $register_channel->queue_bind($register_queue_name, 'RegisterExchange', 'register_req');// login_req routing name

    $login_callback = function ($req) {
        echo '<h3>', ' [x] Received from LoginExchange: ', $req->body, '</h3>', "\n";
    };
    
    $register_callback = function ($req) {
        echo '<h3>', ' [x] Received from RegisterExchange: ', $req->body, '</h3>', "\n";
    };

    echo "<h3>" , " [*] Waiting for messages. To exit press CTRL+C" , "</h3>\n";
    $login_channel->basic_consume($login_queue_name, '', false, true, false, false, $login_callback);
    $register_channel->basic_consume($register_queue_name, '', false, true, false, false, $register_callback);
    while (true) {
        $login_channel->wait();
        $register_channel->wait();
    }

    $login_channel->close();
    $register_channel->close();
    $connection->close();
?>