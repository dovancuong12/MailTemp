<?php

namespace App\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class FindOrCreateCustomEmailBoxRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 64)]
        #[Assert\Regex(pattern: '/^[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?$/', message: 'Name may contain letters, digits, dot, underscore and hyphen only.')]
        public string $name,
    ) {
    }
}
