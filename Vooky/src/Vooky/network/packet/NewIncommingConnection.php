<?php

namespace Vooky\network\packet;


use raklib\protocol\NewIncomingConnection;

class NewIncommingConnection extends NewIncomingConnection
{

    public function encodePayload(): void
    {
        $this->putAddress($this->address);
        //sendPingtime - sendpong time = 0
        //todo
        $this->putLong(time());
        $this->putLong(time());
    }



}