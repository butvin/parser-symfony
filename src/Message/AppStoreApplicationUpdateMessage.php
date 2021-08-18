<?php

namespace App\Message;

class AppStoreApplicationUpdateMessage
{
    private int $id;

    private string $oldVersion;

    private string $newVersion;

    public function __construct(int $id, string $oldVersion, string $newVersion)
    {
        $this->id = $id;
        $this->oldVersion = $oldVersion;
        $this->newVersion = $newVersion;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOldVersion(): string
    {
        return $this->oldVersion;
    }

    public function getNewVersion(): string
    {
        return $this->newVersion;
    }
}
