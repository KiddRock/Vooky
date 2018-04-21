<?php namespace Vooky\listeners;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerTransferEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use Vooky\Loader;
use Vooky\network\SideConnection;
use Vooky\player\ProxiedPlayer;
use Vooky\utils\ServerAddress;

class DefaultEventListener implements Listener
{
    /**
     * @var Loader $loader
     */
    private $loader;

    /**
     * DefaultEventListener constructor.
     * @param Loader $loader
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @param PlayerCreationEvent $event
     */
    public function onCreation(PlayerCreationEvent $event){
        $event->setPlayerClass(ProxiedPlayer::class);
    }

    /**
     * @param DataPacketSendEvent $event
     */
    public function onPackageSend(DataPacketSendEvent $event) : void{
        $packet = $event->getPacket();
        $player = $event->getPlayer();
        if(!$player instanceof ProxiedPlayer){
            return;
        }

        if($packet instanceof LoginPacket){
            $packet->encode();
            $player->loginPacket = $packet;
        }
    }


    public function onTransfer(PlayerTransferEvent $event){
        $player = $event->getPlayer();
        if(!$player instanceof ProxiedPlayer){
            return;
        }
        $event->setCancelled();
        $this->loader->addSideConnection($player, $event->getAddress(), $event->getPort());
    }

}