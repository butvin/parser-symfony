<?php

declare(strict_types=1);

namespace App\Message;

class AppStorePositionParseMessage
{
    private string $country;

    public function __construct(string $country)
    {
        $this->country = $country;
    }

    public function getCountry(): string
    {
        return $this->country;
    }
}
