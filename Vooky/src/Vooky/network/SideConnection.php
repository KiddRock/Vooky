<?php namespace Vooky\network;


use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\Server;
use Vooky\player\ProxiedPlayer;
use raklib\protocol\ConnectionRequestAccepted;
use raklib\protocol\IncompatibleProtocolVersion;
use raklib\protocol\OpenConnectionReply1;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\Packet;
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;

class SideConnection extends Connection
{

    /**
     * @var string $ip
     */
    private $ip;

    /**
     * @var int $port
     */
    private $port;

    /**
     * @var ProxiedPlayer $player
     */
    public $player;

    /**
     * @var ClientConnection $connection
     */
    private $connection;

    /**
     * @var bool $connected
     */
    private $connected = true;

    /**
     * @var resource $socket
     */
    private $socket;

    /**
     * SideConnection constructor.
     * @param ProxiedPlayer $player
     * @param string $ip
     * @param int $port
     */
    public function __construct(ProxiedPlayer $player, string $ip, int $port)
    {
          $player->forceSendEmptyChunks();
          $this->player = $player;
          $this->ip = $ip;
          $this->port = $port;
          $this->connection = new ClientConnection($this);
          $this->connection->startConnection();
          $reader = new BufferReader($this->connection->getSocket());
          Server::getInstance()->getScheduler()->scheduleRepeatingTask(new BufferProcessor($reader, $this->connection), 0);
    }


    /**
     * @param string $buffer
     */
    public function writeBuffer(string $buffer) : void{
        socket_sendto($this->connection->getSocket(), $buffer, strlen($buffer), 0, $this->ip, $this->port);
    }

    /**
     * @param DataPacket $packet
     */
    public function handlePacket(DataPacket $packet){
        //todo: handle & save data
        $this->player->dataPacket($packet);
    }

    /**
     * @param Packet $packet
     */
    public function send(Packet $packet): void
    {
        $packet->encode();
        $this->writeBuffer($packet->buffer);
    }

    public function sendDataPacket(DataPacket $packet): void
    {

    }

    public function close() : void{
        socket_close($this->connection->getSocket());
    }

}