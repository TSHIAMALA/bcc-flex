<?php

namespace App\Event;

use App\Entity\ConjonctureJour;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when conjoncture-related data is created or updated.
 * This triggers automatic alert recalculation.
 */
class ConjonctureDataUpdatedEvent extends Event
{
    public const NAME = 'conjoncture.data.updated';

    public function __construct(
        private ConjonctureJour $conjoncture,
        private string $source = 'unknown'
    ) {}

    public function getConjoncture(): ConjonctureJour
    {
        return $this->conjoncture;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
