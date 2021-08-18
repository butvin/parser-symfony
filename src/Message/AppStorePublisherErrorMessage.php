<?php

namespace App\Message;

class AppStorePublisherErrorMessage
{
    private int $id;

    private string $reason;

    private string $trace;

    public function __construct(int $id, string $reason, string $trace)
    {
        $this->id = $id;
        $this->reason = $reason;
        $this->trace = $trace;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getTrace(): string
    {
        return $this->trace;
    }
}
