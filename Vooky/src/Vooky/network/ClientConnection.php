<?php namespace Vooky\network;


use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\TextFormat;
use raklib\protocol\{
    ConnectionRequest, ConnectionRequestAccepted, Datagram, EncapsulatedPacket, NewIncomingConnection, OpenConnectionReply2, OpenConnectionRequest2, PacketReliability, UnconnectedPing, UnconnectedPong, OpenConnectionReply1, OpenConnectionRequest1, IncompatibleProtocolVersion
};
use raklib\server\Session;
use raklib\utils\InternetAddress;
use Vooky\network\packet\NewIncommingConnection;
use Vooky\utils\ServerAddress;

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
     * @var InternetAddress $clientAddress
     */
    private $clientAddress;

    private $splitPackets = [];


    /**
     * ClientConnection constructor.
     * @param SideConnection $scon
     */
    public function __construct(SideConnection $scon)
    {
        $this->sideConnection = $scon;
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
            $pk = new OpenConnectionReply1();
            $pk->setBuffer($buffer);
            $pk->decode();
            $this->sendConnectionRequest2($pk);
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
     */
    public function handleEncapsulatedPacketRoute(EncapsulatedPacket $packet) : void{
        if($packet->hasSplit){
            $this->handleSplit($packet);
            return;
        }
        $buffer = $packet->buffer;
        $pid = ord($buffer{0});
        switch($pid){
            case ConnectionRequestAccepted::$ID;
            $this->handleConnectionAccepted();
            break;
        }

    }

    public function sendLogin() : void{
        $packet = $this->sideConnection->player->loginPacket;
        $loginPacket = new LoginPacket();
        $loginPacket->setBuffer($packet);
        $loginPacket->encode();//just do some magic
        $encapsulated = new EncapsulatedPacket();
        $encapsulated->hasSplit = false;
        $encapsulated->buffer = $loginPacket->buffer;
        $encapsulated->reliability = 0;
        $datagram = new Datagram();
        $datagram->setBuffer($packet);
        $datagram->packets[] = $encapsulated;
        $datagram->seqNumber = 2;
        $datagram->encode();
        $this->sideConnection->writeBuffer($datagram->buffer);
        echo 'Started login...' . PHP_EOL;
    }


    public function handleConnectionAccepted() : void{
      $pk = new NewIncommingConnection();
      $pk->sendPingTime = $this->sendPingTime;
      $pk->sendPongTime = $this->sendPongTime;
      $serverAddress = $this->sideConnection->serverAddress;
      $pk->address = $serverAddress;
      $pk->encode();
      $encapsulated = new EncapsulatedPacket();
      $encapsulated->hasSplit = false;
      $encapsulated->buffer = $pk->buffer;
      $encapsulated->reliability = 0;
      $datagram = new Datagram();
      $datagram->seqNumber = 1;
      $datagram->packets[] = $encapsulated;
      $datagram->encode();
      $this->sideConnection->writeBuffer($datagram->buffer);
      $this->sendLogin();
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
        $packet->mtuSize = 1464;
        $packet->protocol = 8;
        $this->sideConnection->send($packet);
    }

    /**
     * @param OpenConnectionReply1 $pk
     */
    public function sendConnectionRequest2(OpenConnectionReply1 $pk) : void{
        $opc = new OpenConnectionRequest2();
        $opc->mtuSize =  1464;
        $opc->serverAddress = $this->sideConnection->serverAddress;
        $opc->clientID = $this->clientID = mt_rand(1,100);
        $opc->encode();
        $this->sideConnection->send($opc);
    }


}