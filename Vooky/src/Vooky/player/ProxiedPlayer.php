<?php namespace Vooky\player;


use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\FullChunkDataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\SourceInterface;
use pocketmine\Player;
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
     * @var LoginPacket $loginPacket
     */
    public $loginPacket;

    /**
     * ProxiedPlayer constructor.
     * @param SourceInterface $interface
     * @param string $ip
     * @param int $port
     */
    public function __construct(SourceInterface $interface, string $ip, int $port)
    {
        parent::__construct($interface, $ip, $port);
    }

    public function disconnect() : void{
        $this->server = null;
    }

    public function forceSendEmptyChunks() {
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