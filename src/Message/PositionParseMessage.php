<?php

declare(strict_types=1);

namespace App\Message;

class PositionParseMessage
{
    private string $country;

    public function __construct(string $country)
    {
        $this->country = $country;
    }

    final public function getCountry(): string
    {
        return $this->country;
    }
}
