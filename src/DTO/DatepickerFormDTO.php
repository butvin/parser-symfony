<?php

namespace App\DTO;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class DatepickerFormDTO
{
    /**
     * @Assert\NotBlank()
     * @Groups({"jsonResponse", "api"})
     */
    private string $dateInput;

    public function getDateInput(): ?string
    {
        return $this->dateInput;
    }

    public function setDateInput(string $dateInput): self
    {
        $this->dateInput = $dateInput;

        return $this;
    }
}
