<?php namespace Vooky\network;

use pocketmine\Thread;

class BufferReader extends Thread {

    /**
     * @var resource $clientConnection
     */
    private $clientConnection;


    /**
     * @var string[] $receivedQueue
     */
    public $receivedQueue = [];

    /**
     * BufferReader constructor.
     * @param resource $socket
     */
    public function __construct($socket)
    {
        $this->clientConnection = $socket;
        $this->start();
    }

    public function run(){
        while(true){
            if(@socket_recvfrom($this->clientConnection, $buffer, 65535, 0, $address, $port) !== false){
                if(strlen($buffer) > 1) {
                    $this->receivedQueue[] = $buffer;
                }
            }
        }
        parent::run();
    }

}