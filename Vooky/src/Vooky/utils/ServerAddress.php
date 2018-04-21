<?php namespace Vooky\utils;

use raklib\utils\InternetAddress;

class ServerAddress extends InternetAddress
{
    /**
     * ServerAddress constructor.
     * @param string $address
     * @param int $port
     * @param int $version
     */
    public function __construct(string $address, int $port, int $version)
    {
        parent::__construct(gethostbyname($address), $port, $version);
    }

}