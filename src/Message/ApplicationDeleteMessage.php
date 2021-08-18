<?php

namespace App\Message;

class ApplicationDeleteMessage
{
    private int $publisherId;

    private array $appIds;

    public function __construct(int $publisherId, array $appIds)
    {
        $this->appIds = $appIds;
        $this->publisherId = $publisherId;
    }
    
    public function getPublisherId(): int
    {
        return $this->publisherId;
    }

    public function getAppIds(): array
    {
        return $this->appIds;
    }
}
