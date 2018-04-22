<?php namespace Vooky\player;


use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\Server;
use Vooky\Loader;
use Vooky\network\SideConnection;

class CustomNetworkAdapter extends PlayerNetworkSessionAdapter
{

    /**
     * @var Player $player
     */
    private $player;

    /**
     * CustomNetworkAdapter constructor.
     * @param Server $server
     * @param ProxiedPlayer $player
     */
    public function __construct(Server $server, ProxiedPlayer $player)
    {
        $this->player = $player;
        parent::__construct($server, $player);
    }

    /**
     * @param DataPacket $packet
     */
    public function handleDataPacket(DataPacket $packet)
    {
        $player = $this->player;
        if($packet instanceof LoginPacket){
            $player->loginPacket = $packet->buffer;
        }

        if($player->getConnection() instanceof SideConnection){
            if($player->getConnection()->isAlive){
                Loader::getInstance()->getLogger()->debug("Sending packet ID " . $packet::NETWORK_ID . " from " . $player->getName() . " [XUID:" . $player->getXuid() . "]");
                $player->getConnection()->getClientConnection()->sendClientPacket($packet);
            }
        }

        parent::handleDataPacket($packet);
    }


}