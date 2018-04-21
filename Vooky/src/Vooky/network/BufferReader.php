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
            echo '1';
            if(@socket_recvfrom($this->clientConnection, $buffer, 65535, 0, $address, $port) !== false){
                $this->receivedQueue[] = $buffer;
            }
        }
        parent::run();
    }

}