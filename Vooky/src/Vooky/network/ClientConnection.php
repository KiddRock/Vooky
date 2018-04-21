<?php namespace Vooky\network;


use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\utils\TextFormat;
use raklib\protocol\{
    ConnectionRequest, ConnectionRequestAccepted, Datagram, EncapsulatedPacket, NewIncomingConnection, OpenConnectionReply2, OpenConnectionRequest2, PacketReliability, UnconnectedPing, UnconnectedPong, OpenConnectionReply1, OpenConnectionRequest1, IncompatibleProtocolVersion
};
use raklib\utils\InternetAddress;

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

    public function handleUnknownPacket(string $buffer) : bool {
        switch(ord($buffer{0})){
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
            echo 'handling' . PHP_EOL;
            return true;
            case ConnectionRequestAccepted::$ID;
            echo 'respond';
            $pk = new ConnectionRequestAccepted();
            $pk->buffer = $buffer;
            $pk->decode();
            $this->handleConnectionAccepted($pk);
            break;
            case IncompatibleProtocolVersion::$ID;
            $this->handleInvalidProtocol($buffer);
            return true;
        }
        return false;
    }

    public function sendLogin() : void{
        $packet = $this->sideConnection->player->loginPacket;
        $loginPacket = new LoginPacket();
        $loginPacket->setBuffer($packet);
        $datagram = new Datagram();
        $datagram->setBuffer($packet);
        $datagram->encode();
        $datagram->decode();
        $this->sideConnection->writeBuffer($datagram->buffer);
    }

    public function handleConnectionAccepted(ConnectionRequestAccepted $packet) : void{
         $this->clientAddress = $packet->address;
         $packet = new NewIncomingConnection();
         $packet->sendPingTime = $this->sendPingTime;
         $packet->sendPongTime = $this->sendPongTime;
         $packet->address = $this->clientAddress;
         $packet->encode();
         $sendPacket = new EncapsulatedPacket();
         $sendPacket->reliability = 0;
         $sendPacket->buffer = $packet->buffer;
         $this->sideConnection->writeBuffer($sendPacket->buffer);
    }


    public function handleConnectionReply2() : void{
        $pk = new ConnectionRequest();
        $pk->clientID = $this->clientID;
        $pk->sendPingTime = $this->sendPingTime;
        $pk->encode();
        $sendPacket = new EncapsulatedPacket();
        $sendPacket->reliability = 0;
        $sendPacket->buffer = $pk->buffer;
        $dataPacket = new Datagram();
        $dataPacket->seqNumber = 0;
        $dataPacket->packets = [$sendPacket];
        $dataPacket->encode();
        $this->sideConnection->writeBuffer($dataPacket->buffer); //:(
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
        $mtuSize = min(abs($pk->mtuSize), 1492);
        $opc->mtuSize =  1464;
        $opc->serverAddress = $this->sideConnection->serverAddress;
        $opc->clientID = $this->clientID = mt_rand(1,100);
        $opc->encode();
        $this->sideConnection->send($opc);
    }


}