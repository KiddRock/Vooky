<?php namespace Vooky\network;


use pocketmine\scheduler\Task;
use raklib\protocol\IncompatibleProtocolVersion;
use raklib\protocol\OpenConnectionReply1;
use raklib\protocol\UnconnectedPong;

class BufferReader extends Task
{

    /**
     * @var ClientConnection $clientConnection
     */
    private $clientConnection;

    /**
     * @var resource $socket
     */
    private $socket;

    /**
     * BufferReader constructor.
     * @param ClientConnection $clientConnection
     */
    public function __construct(ClientConnection $clientConnection)
    {
        $this->clientConnection = $clientConnection;
        $this->socket = $clientConnection->getSocket();
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        if(@socket_recvfrom($this->socket, $buffer, 65535, 0, $address, $port) !== false){
              if(!$this->handlePacket($buffer)){
                  //todo
              }
        }
    }

    /**
     * @param string $buffer
     * @return bool
     */
    public function handlePacket(string $buffer) : bool {
        switch(ord($buffer[0])){
            case UnconnectedPong::$ID;
            $this->clientConnection->handleUnconnectedPong($buffer);
            return true;
            case OpenConnectionReply1::$ID;
            //50% accepted
            return true;
            case IncompatibleProtocolVersion::$ID;
            //not accepted
            $this->clientConnection->handleInvalidProtocol($buffer);
            return true;
        }
        return false;
    }

}