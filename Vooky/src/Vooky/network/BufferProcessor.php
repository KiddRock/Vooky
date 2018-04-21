<?php
/**
 * Created by PhpStorm.
 * User: NewAdmin
 * Date: 21.04.2018
 * Time: 11:58
 */

namespace Vooky\network;


use pocketmine\scheduler\Task;
use raklib\protocol\UnconnectedPong;

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
            $this->clientConnection->handleUnknownPacket($buffer);
            unset($this->bufferReader->receivedQueue[array_search($buffer, (array)$this->bufferReader->receivedQueue)]);
        }
    }

}