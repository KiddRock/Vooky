<?php namespace Vooky\network;


use pocketmine\network\mcpe\protocol\DataPacket;
use raklib\protocol\Packet;

abstract class Connection
{

    /**
     * Prepare packet sniffer and handler
     */
    public function prepare() : void{

    }

    /**
     * @param Packet $packet
     *
     * Send package to the server
     */
    public function send(Packet $packet) : void{

    }

}