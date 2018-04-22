<?php namespace Vooky\network;

use pocketmine\scheduler\Task;

class BufferProcessor extends Task
{
    /**
     * @var BufferReader $bufferReader
     */
    private $bufferReader;

    /**
     * @var ClientConnection $clientConnection
     */
    private $clientConnection;

    /**
     * @var int $lastPacketTime
     */
    private $lastPacketTime = 0;

    /**
     * BufferProcessor constructor.
     * @param BufferReader $bufferReader
     * @param ClientConnection $clientConnection
     */
    public function __construct(BufferReader $bufferReader, ClientConnection $clientConnection)
    {
        $this->bufferReader = $bufferReader;
        $this->clientConnection = $clientConnection;
    }

    /**
     * @param int $currentTick
     */
    public function onRun(int $currentTick)
    {
        $packets = (array)$this->bufferReader->receivedQueue;
        if(count($packets) > 0){
            $this->lastPacketTime = time();
            $buffer = array_shift($packets);
            $this->clientConnection->handleUnknownPacket($buffer);
            unset($this->bufferReader->receivedQueue[array_search($buffer, (array)$this->bufferReader->receivedQueue)]);
        }else{
            //todo: check for timeout
        }
    }

}