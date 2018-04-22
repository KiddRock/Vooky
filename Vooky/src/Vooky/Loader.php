<?php namespace Vooky;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use Vooky\listeners\DefaultEventListener;
use Vooky\network\ClientConnection;
use Vooky\network\SideConnection;
use Vooky\player\ProxiedPlayer;

class Loader extends PluginBase
{

    /**
     * @var int $batchCompressionLevel
     */
    private $batchCompressionLevel;

    /**
     * @var int $onlineMode
     */
    private $onlineMode;

    /**
     * @var bool $replaceTransfer
     */
    private $replaceTransfer;

    /**
     * @var SideConnection[] $downstreamConnections
     */
    private $downstreamConnections = [];

    /**
     * @var Loader $instance
     */
    private static $instance;

    public function onLoad() : void
    {
        self::$instance = $this;
    }

    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents(new DefaultEventListener($this), $this);
        $this->saveDefaultConfig();

        $this->batchCompressionLevel = $this->getConfig()->getNested("batch-compression-level", 7);
        $this->onlineMode = $this->getConfig()->getNested("online-mode", -1);
        $this->replaceTransfer = $this->getConfig()->getNested("replace-transfer", true);
    }

    /**
     * @param ProxiedPlayer $player
     * @param string $address
     * @param int $port
     */
    public function addSideConnection(ProxiedPlayer $player, string $address, int $port) : void{
        $connection = new SideConnection($player, $address, $port);
        $this->downstreamConnections[] = $connection;
        $player->setConnection($connection);
    }

    public function onDisable() : void{
        foreach($this->downstreamConnections as $connection){
            $connection->close();
            $connection->player->close("", TextFormat::YELLOW . "Proxy Stopped");
        }
    }

    /**
     * @return int
     */
    public function getBatchCompressionLevel() : int{
        return $this->batchCompressionLevel;
    }

    /**
     * @return bool
     */
    public function getOnlineMode() : bool{
        return $this->onlineMode > -1;
    }

    /**
     * @return bool
     */
    public function isReplaceTransfer() : bool{
        return $this->replaceTransfer;
    }
    /**
     * @return Loader
     */
    public static function getInstance() : Loader{
        return self::$instance;
    }

}