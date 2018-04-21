<?php

namespace Vooky\network;

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

    private $lastPacket = "a";

    /**
     * BufferProcessor constructor.
     * @param BufferReader $bufferReader
     */
    public function __construct(BufferReader $bufferReader, ClientConnection $clientConnection)
    {
        $this->bufferReader = $bufferReader;
        $this->clientConnection = $clientConnection;
    }

    public function onRun(int $currentTick)
    {
        $packets = (array)$this->bufferReader->receivedQueue;
        if(count($packets) > 0){
            $buffer = array_shift($packets);
            if($this->lastPacket == $buffer){
                return;
            }
            $this->lastPacket = $buffer;
            $this->clientConnection->handleUnknownPacket($buffer);
            unset($this->bufferReader->receivedQueue[array_search($buffer, (array)$this->bufferReader->receivedQueue)]);
        }
    }

}