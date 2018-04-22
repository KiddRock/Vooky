<?php namespace Vooky\network;


use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\Server;
use Vooky\Loader;
use Vooky\player\ProxiedPlayer;
use raklib\protocol\Packet;
use Vooky\utils\ServerAddress;

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
     * @var ServerAddress $serverAddress
     */
    public $serverAddress;

    public $isAlive = true;

    /**
     * SideConnection constructor.
     * @param ProxiedPlayer $player
     * @param string $ip
     * @param int $port
     */
    public function __construct(ProxiedPlayer $player, string $ip, int $port)
    {
          $this->serverAddress = new ServerAddress($ip, $port, 4);
          $this->player = $player;
          $this->ip = $ip;
          $this->port = $port;
          $this->connection = new ClientConnection($this);
          $this->connection->startConnection();
          $reader = new BufferReader($this->connection->getSocket());
          Server::getInstance()->getScheduler()->scheduleRepeatingTask(new BufferProcessor($reader, $this->connection), 0);
    }

    /**
     * @return ClientConnection
     */
    public function getClientConnection() : ClientConnection{
        return $this->connection;
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


    public function close() : void{
        socket_close($this->connection->getSocket());
    }

}