# Laravel PHP Framework with Redis PubSub 

[![Latest Stable Version](https://poser.pugx.org/laravel/framework/version.png)](https://packagist.org/packages/laravel/framework) 

for **Laravel 4.2(PSR-4)**

This repository project integrates *Redis Pubsub service* and *Laravel PHP Framework*, supporting websocket protocol.

This project referenced [Laravel-websocket-server sample](https://github.com/ytake/laravel-websocket) by ytake


##Pre-requisite

PHP >=5.4.*

[ext-zmq](http://php.net/manual/en/book.zmq.php) (ZeroMQ PHP extension) 

[ext-phpiredis](https://github.com/nrk/phpiredis) (PHP bindings for Hiredis)

[hiredis](https://github.com/redis/hiredis) (Minimalistic C client for Redis >= 1.2)

[Redis Server](http://redis.io/)

##How To Install

###Installation

Pull this repository, and install laravel framework as usual,

```php composer.phar update``` or ```composer update```

###Start Laravel Server

In root directory of the repository, run 

```php artisan serve```

###Start Websocket

In root directory of the repository, run 

```php artisan websocket:server``` as default port 3000

or

``` php artisan websocket:server --port 3000```

or

```php artisan websocket:server -p 3000```

##How To Use

####Controller/Command (Server Side)

```PHP
app/controllers/EmitController.php

//include before class declaration
use App\Reactive\DataStore;

...
class EmitController extends BaseController{
    ...

    //init DataStore for further use in controller
    protected $store;
    
    public function __construct(DataStore $store){
        $this->store = $store;
    }

    ...

    // Sample for sending (JSON)packet asynchronously to client through websocket.
    public function store(){
        if($this->store->publish(
        
                //package content
                [
                //You may add any data content for the payload, here we use 'body' as payload
                'body' => \Input::get('body', null),
                
                //'topic' indicates packets to be sent to client who subscribe to certain 'topic'
                //if 'topic' is not set, the packet will send to all client subscript to websocket
                'topic'=>....
                ]
            )
        ){
            return \Response::json(['result' => true] ,200);
        }
    }
    ...
}

```


```PHP
app/App/Reactive/Socket/Push.php

...

class Push implements WampServerInterface{
    ...
    //Subscription list
    protected $subscribedTopics = array();
    
    //This function fire when client connect to websocket
    public function onSubscribe(ConnectionInterface $conn, $topic){
    
        //Add topic to subscribedTopics if not exists.
        if (!array_key_exists($topic->getId(), $this->subscribedTopics)) {
            $this->subscribedTopics[$topic->getId()] = $topic;
        }
    }
    ...
    
    //This function fire when DataStore::push is called in controller/commands
    public function push($data){
        $entryData = json_decode($data, true);
        
        if(array_key_exists('topic',$entryData)) {
        
            //If 'topic' is set in payload, send to client subscribed to 'topic'
            $topicTitle = $entryData['topic'];
            $topic = $this->subscribedTopics[$topicTitle];
            $topic->broadcast($entryData);
        }else {
            //otherwise, send to all clients subscribed to websocket.
            
            foreach ($this->subscribedTopics as $topic){
                $topic->broadcast($entryData);
            }
        }
    }
    
    ...

}
```

####View (Client Side)

Include [ AutobahnJS](http://autobahn.s3.amazonaws.com/js/autobahn.min.js)
```HTML
<script src="http://autobahn.s3.amazonaws.com/js/autobahn.min.js"></script>
```
Add following javascript to listen websocket

```javascript
var conn = new ab.Session(

    //Subscribe to websocket
    'ws://127.0.0.1:3000' , function(){
    
        //Specify which 'topic' is going to subscribe, we here use 'news' as example
        conn.subscribe('news', function(topic, data) {
            if(data.body != ''){
                //do something
            }
        });
    }, function() {
        console.warn('WebSocket connection closed');
    }, {
        'skipSubprotocolCheck': true
    }
);
```


## Official Laravel Documentation

Documentation for the entire framework can be found on the [Laravel website](http://laravel.com/docs).
