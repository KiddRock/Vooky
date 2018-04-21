<?php namespace Vooky;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use Vooky\listeners\DefaultEventListener;
use Vooky\network\SideConnection;
use Vooky\player\ProxiedPlayer;

class Loader extends PluginBase
{

    /**
     * @var SideConnection[] $downstreamConnections
     */
    private $downstreamConnections = [];


    public function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents(new DefaultEventListener($this), $this);
    }

    /**
     * @param ProxiedPlayer $player
     * @param string $address
     * @param int $port
     */
    public function addSideConnection(ProxiedPlayer $player, string $address, int $port) : void{
        $connection = new SideConnection($player, $address, $port);
        $this->downstreamConnections[] = $connection;
    }

    public function onDisable() : void{
        foreach($this->downstreamConnections as $connection){
            $connection->close();
            $connection->player->close(TextFormat::YELLOW . "Proxy Stopped");
        }
    }

}