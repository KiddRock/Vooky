<?php namespace Vooky\player;


use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\FullChunkDataPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
use pocketmine\Server;
use Vooky\network\SideConnection;
use Vooky\utils\ServerAddress;

class ProxiedPlayer extends Player
{

    /**
     * @var ServerAddress $serverConnected
     */
    private $serverConnected;

    /**
     * @var bool $acceptDefaultPackages
     */
    private $acceptDefaultPackages = true;

    /**
     * @var string $loginPacket
     */
    public $loginPacket;

    /**
     * @var SideConnection $connection
     */
    private $connection;

    /**
     * ProxiedPlayer constructor.
     * @param SourceInterface $interface
     * @param string $ip
     * @param int $port
     */
    public function __construct(SourceInterface $interface, string $ip, int $port)
    {
        parent::__construct($interface, $ip, $port);
        $this->sessionAdapter = new CustomNetworkAdapter(Server::getInstance(), $this);
    }

    public function disconnect() : void{
        $this->server = null;
    }

    /**
     * @param SideConnection $sideConnection
     */
    public function setConnection(SideConnection $sideConnection){
        $this->connection = $sideConnection;
    }

    /**
     * @return SideConnection
     */
    public function getConnection() : SideConnection{
        return $this->connection;
    }

    public function finishLogin() : void{
        foreach ($this->usedChunks as $index => $true) {
            Level::getXZ($index, $chunkX, $chunkZ);
            $pk = new FullChunkDataPacket();
            $pk->chunkX = $chunkX;
            $pk->chunkZ = $chunkZ;
            $pk->data = '';
            $this->dataPacket($pk);
        }
    }


    /**
     * @param ServerAddress $serverAddress
     */
    public function connect(ServerAddress $serverAddress) : void{
          new SideConnection($this, $serverAddress->ip, $serverAddress->port);
          $this->serverConnected = $serverAddress;
          $this->acceptDefaultPackages = false;
    }

    public function isProxyConnected() : bool{

    }

    /**
     * @return bool
     */
    public function isAcceptDefaultPackages() : bool{
        return $this->acceptDefaultPackages;
    }

}