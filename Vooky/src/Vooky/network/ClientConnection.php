<?php namespace Vooky\network;


use pocketmine\utils\TextFormat;
use raklib\protocol\IncompatibleProtocolVersion;
use raklib\protocol\OpenConnectionReply1;
use raklib\protocol\OpenConnectionRequest1;
use raklib\protocol\UnconnectedPing;
use raklib\protocol\UnconnectedPong;

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
     * ClientConnection constructor.
     * @param SideConnection $scon
     */
    public function __construct(SideConnection $scon)
    {
        $this->sideConnection = $scon;
        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    public function handleUnknownPacket(string $buffer) : bool {
        switch(ord($buffer{0})){
            case UnconnectedPong::$ID;
            $this->handleUnconnectedPong($buffer);
            return true;
            case OpenConnectionReply1::$ID;
            //send opcr2
            return true;
            case IncompatibleProtocolVersion::$ID;
            $this->handleInvalidProtocol($buffer);
            return true;
        }
        return false;
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
        echo 'Sending ping' . PHP_EOL;
    }

    /**
     * @param string $buffer
     * @return bool
     */
    public function handleUnconnectedPong(string $buffer) : bool {
        $packet = new UnconnectedPong();
        $packet->setBuffer($buffer);
        $packet->decode();

        $serverInfo = explode(";",substr($buffer, 40));
        if(is_null($serverInfo[3])){
            return false;
        }
        if($this->hasToConnect){
            $this->sendConnectionRequest1(1024);
        }
        return true;
    }

    /**
     * @param string $buffer
     */
    public function handleInvalidProtocol(string $buffer) : void{
        $packet = new IncompatibleProtocolVersion();
        $packet->setBuffer($buffer);
        $packet->decode();
        $this->getSideDownstream()->player->close("Incompatible protocol", TextFormat::RED . "Incompatible RakNet protocol sent to target server: " . TextFormat::YELLOW .  $packet->protocolVersion);
    }

    /**
     * @param int $mtuSize
     */
    public function sendConnectionRequest1(int $mtuSize) : void{
        $packet = new OpenConnectionRequest1();
        $mtuSize = min(abs(100), 1492);
        $packet->mtuSize = $mtuSize;
        $packet->protocol = 6;
        $this->sideConnection->send($packet);
        echo 'opcrequest';

    }

    public function sendConnectionRequest2() : void{

    }

}