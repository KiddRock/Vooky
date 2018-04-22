<?php

namespace Vooky\network\packet;


use raklib\protocol\NewIncomingConnection;

class NewIncommingConnection extends NewIncomingConnection
{

    public function encodePayload(): void
    {
        $this->putAddress($this->address);
    }

}