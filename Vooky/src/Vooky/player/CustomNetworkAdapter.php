<?php

namespace Vooky\player;


use pocketmine\network\mcpe\PlayerNetworkSessionAdapter;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\Server;

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
        if($packet instanceof LoginPacket){ //cant encode
            $this->player->loginPacket = $packet->buffer;
        }
        parent::handleDataPacket($packet);
    }


}