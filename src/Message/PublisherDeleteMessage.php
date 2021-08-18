<?php

namespace App\Message;

class PublisherDeleteMessage
{
    private int $id;

    private string $reason;

    public function __construct(int $id, string $reason)
    {
        $this->id = $id;
        $this->reason = $reason;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
