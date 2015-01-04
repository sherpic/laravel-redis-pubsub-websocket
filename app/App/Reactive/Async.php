<?php
namespace App\Reactive;

use Predis\Async\Client as AsyncClient;

/**
 * Class Async
 * @package App\Reactive
 * @author yuuki.takezawa<yuuki.takezawa@comnect.jp.net>
 */
class Async implements AsyncInterface
{

    /** @var array */
    protected $connection = [];
    /** @var \ZMQContext */
    protected $context;

    /**
     * @param \ZMQContext $context
     */
    public function __construct(\ZMQContext $context)
    {
        $this->connection = \Config::get('database.redis.default');
        $this->context = $context;
    }

    /**
     * ReactPHP and Predis/AsyncAsync.php
     * @return \React\EventLoop\LoopInterface
     * @throws \Exception
     */
    public function async()
    {
        $client = new AsyncClient($this->connection);
        $client->connect(function ($client) {
            //
            $redis = new AsyncClient($this->connection, $client->getEventLoop());
            // subscribe channel
            $client->pubsub(\Config::get('pubsub.basic_channel'), function ($event) use ($redis) {

                $socket = $this->context->getSocket(\ZMQ::SOCKET_PUSH, 'push');
                $socket->connect(\Config::get('app.socket_connection'));
                $socket->send($event->payload);
            });
        });

        if(!$client->isConnected()) {
            throw new \Exception("redis not connect", 500);
        }
        return $client->getEventLoop();
    }
}