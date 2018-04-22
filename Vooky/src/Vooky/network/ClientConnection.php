<?php namespace Vooky\network;


use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\FullChunkDataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\utils\TextFormat;
use raklib\protocol\{
    ConnectionRequest, ConnectionRequestAccepted, Datagram, EncapsulatedPacket, OpenConnectionReply2, OpenConnectionRequest2, PacketReliability, UnconnectedPing, UnconnectedPong, OpenConnectionReply1, OpenConnectionRequest1, IncompatibleProtocolVersion
};
use raklib\server\Session;
use Vooky\Loader;
use Vooky\network\packet\NewIncommingConnection;

class ClientConnection
{

    /**
     * @var resource $socket
     */
    private $socket;

    /**
     * @var SideConnection $serverConnection
     */
    private $sideConnection;

    /**
     * @var bool
     */
    private $hasToConnect = false;

    /**
     * @var int $raknetID
     */
    private $raknetID;

    /**
     * @var int $clientID
     */
    private $clientID;

    /**
     * @var bool
     */
    private $isAccepted = false;

    /**
     * @var int $sendPingTime
     */
    private $sendPingTime;

    /**
     * @var int $sendPongTime
     */
    private $sendPongTime;

    /**
     * @var array $splitPackets
     */
    private $splitPackets = [];

    /**
     * @var Loader $plugin
     */
    private $plugin;

    /**
     * @var int $sendSeqNumber
     */
    private $sendSeqNumber = 2;


    /**
     * ClientConnection constructor.
     * @param SideConnection $sideConnection
     */
    public function __construct(SideConnection $sideConnection)
    {
        $this->sideConnection = $sideConnection;
        $this->plugin = Loader::getInstance();

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, 1024 * 1024 * 8);
        socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, 1024 * 1024 * 8);
    }

    /**
     * @param string $buffer
     * @return bool
     */
    public function handleUnknownPacket(string $buffer) : bool {
        $pid = ord($buffer{0});
        switch($pid){
            case UnconnectedPong::$ID;
            $this->handleUnconnectedPong($buffer);
            return true;
            case OpenConnectionReply1::$ID;
            $this->sendConnectionRequest2();
            $this->isAccepted = true;
            return true;
            case OpenConnectionReply2::$ID;
            $this->handleConnectionReply2();
            return true;
            case IncompatibleProtocolVersion::$ID;
            $this->handleInvalidProtocol($buffer);
            return true;
        }
        if((ord($buffer{0}) & Datagram::BITFLAG_VALID) !== 0){
            if($pid & Datagram::BITFLAG_ACK){
                 //todo: send
            }elseif($pid & Datagram::BITFLAG_NAK){
                //todo: send
            }else{
               $this->handleDatagram(new Datagram($buffer));
            }
        }
        return false;
    }

    /**
     * @param Datagram $datagram
     */
    public function handleDatagram(Datagram $datagram) : void{
         $datagram->decode();
         foreach($datagram->packets as $packet){
             if($packet instanceof EncapsulatedPacket){
                 $this->handleEncapsulatedPacketRoute($packet);
             }
         }
    }

    /**
     * @param EncapsulatedPacket $packet
     */
    private function handleSplit(EncapsulatedPacket $packet) : void{
        if($packet->splitCount >= Session::MAX_SPLIT_SIZE or $packet->splitIndex >= Session::MAX_SPLIT_SIZE or $packet->splitIndex < 0){
            return;
        }
        if(!isset($this->splitPackets[$packet->splitID])){

            $this->splitPackets[$packet->splitID] = [$packet->splitIndex => $packet];
        }else{
            $this->splitPackets[$packet->splitID][$packet->splitIndex] = $packet;
        }
        if(count($this->splitPackets[$packet->splitID]) === $packet->splitCount){
            $pk = new EncapsulatedPacket();
            $pk->buffer = "";
            for($i = 0; $i < $packet->splitCount; ++$i){
                $pk->buffer .= $this->splitPackets[$packet->splitID][$i]->buffer;
            }
            $pk->length = strlen($pk->buffer);
            unset($this->splitPackets[$packet->splitID]);
            $this->handleEncapsulatedPacketRoute($packet);
        }
    }

    /**
     * @param EncapsulatedPacket $packet
     * @return bool
     */
    public function handleEncapsulatedPacketRoute(EncapsulatedPacket $packet) : bool {
        if($packet->hasSplit){
            $this->handleSplit($packet);
            return true;
        }
        $buffer = $packet->buffer;
        $pid = ord($buffer{0});
        switch($pid){
            case ConnectionRequestAccepted::$ID;
            $this->handleConnectionAccepted();
            return true;
        }
        $this->processBatch($packet);
    }

    /**
     * @param EncapsulatedPacket $encapsulatedPacket
     */
    public function processBatch(EncapsulatedPacket $encapsulatedPacket) : void{
        $packet = PacketPool::getPacket($encapsulatedPacket->buffer);
        if($packet instanceof BatchPacket){
            @$packet->decode();
            if($packet->payload == ""){
                return;
            }
            foreach($packet->getPackets() as $buf){
                $packet = PacketPool::getPacket($buf);
                if(is_null($packet)){
                    return;
                }

                if(!$packet->canBeBatched()){
                    throw new \InvalidArgumentException("Received invalid " . get_class($packet) . " inside BatchPacket");
                }

                $this->sideConnection->player->dataPacket($packet);
                $this->handlePacket($packet);

            }
        }
    }

    /**
     * @param DataPacket $packet
     */
    public function handlePacket(DataPacket $packet) : void{
        $player = $this->sideConnection->player;
        switch($packet::NETWORK_ID){
            case SetPlayerGameTypePacket::NETWORK_ID;
            $packet->decode();
            $player->setGamemode($packet->gamemode);
            case DisconnectPacket::NETWORK_ID;
            $this->sideConnection->close();
            $player->setConnection(null);
            break;
            case TransferPacket::NETWORK_ID;
            $this->sideConnection->close();
            $player->setConnection(null);
            $packet->decode();
            $this->plugin->addSideConnection($player, $packet->address, $packet->port);
            //this may be bit longer than normal transfer
            break;
        }
    }

    /**
     * @param DataPacket $packet
     */
    public function sendClientPacket(DataPacket $packet) : void{
        if(!$packet->isEncoded){
            $packet->encode();
        }
        $batch = new BatchPacket();
        $batch->addPacket($packet);
        $batch->setCompressionLevel($this->plugin->getBatchCompressionLevel());
        $batch->encode();
        $encapsulated = new EncapsulatedPacket();
        $encapsulated->buffer = $batch->buffer;
        $encapsulated->reliability = PacketReliability::UNRELIABLE;
        $datagram = new Datagram();
        $datagram->setBuffer($packet);
        $datagram->packets[] = $encapsulated;
        $datagram->seqNumber = $this->sendSeqNumber++;
        $datagram->encode();
        $this->sideConnection->writeBuffer($datagram->buffer);
    }

    public function handleConnectionAccepted() : void{
      $pk = new NewIncommingConnection();
      $pk->sendPingTime = $this->sendPingTime;
      $pk->sendPongTime = $this->sendPongTime;
      $serverAddress = $this->sideConnection->serverAddress;
      $pk->address = $serverAddress;
      $pk->encode();
      $encapsulated = new EncapsulatedPacket();
      $encapsulated->hasSplit = true;
      $encapsulated->buffer = $pk->buffer;
      $encapsulated->reliability = 0;
      $datagram = new Datagram();
      $datagram->seqNumber = 1;
      $datagram->packets[] = $encapsulated;
      $datagram->encode();
      $this->sideConnection->writeBuffer($datagram->buffer);
    }


    public function handleConnectionReply2() : void{
        $pk = new ConnectionRequest();
        $pk->clientID = $this->clientID;
        $pk->sendPingTime = $this->sendPingTime;
        $pk->encode();
        $encapsulated = new EncapsulatedPacket();
        $encapsulated->hasSplit = false;
        $encapsulated->buffer = $pk->buffer;
        $encapsulated->reliability = 0;
        $datagram = new Datagram();
        $datagram->seqNumber = 0;
        $datagram->packets[] = $encapsulated;
        $datagram->encode();
        $this->sideConnection->writeBuffer($datagram->buffer);
    }

    /**
     * @return SideConnection
     */
    public function getSideDownstream() : SideConnection{
        return $this->sideConnection;
    }


    public function startConnection() : void{
        $this->hasToConnect = true;
        $this->sendUnconnectedPing();
    }

    /**
     * @return resource
     */
    public function getSocket(){
        return $this->socket;
    }

    /**
     * Ping the server we want to connect on
     */
    public function sendUnconnectedPing() : void{
        $packet = new UnconnectedPing();
        $packet->pingID = time();
        $this->sideConnection->send($packet);
        $this->sendPingTime = time();
    }

    /**
     * @param string $buffer
     * @return bool
     */
    public function handleUnconnectedPong(string $buffer) : bool {
        if($this->isAccepted){
            return false;
        }
        $packet = new UnconnectedPong();
        $packet->setBuffer($buffer);
        $packet->decode();
        $this->raknetID = $packet->serverID;
        $this->sendConnectionRequest1a(1024);
        $this->sendPongTime = time();
        return true;
    }

    /**
     * @param string $buffer
     */
    public function handleInvalidProtocol(string $buffer) : void{
        $packet = new IncompatibleProtocolVersion();
        $packet->setBuffer($buffer);
        $packet->decode();
        $this->getSideDownstream()->player->close("Incompatible protocol", TextFormat::RED . "Incompatible RakNet protocol sent to target server (required): " . TextFormat::YELLOW .  $packet->protocolVersion);
    }

    /**
     * @param int $mtuSize
     */
    public function sendConnectionRequest1a(int $mtuSize) : void{
        $packet = new OpenConnectionRequest1();
        $packet->mtuSize = 576;
        $packet->protocol = 8;
        $this->sideConnection->send($packet);
    }


    public function sendConnectionRequest2() : void{
        $opc = new OpenConnectionRequest2();
        $opc->mtuSize = 576;
        $opc->serverAddress = $this->sideConnection->serverAddress;
        $opc->clientID = $this->clientID = mt_rand(1,100);
        $opc->encode();
        $this->sideConnection->send($opc);
    }


}
