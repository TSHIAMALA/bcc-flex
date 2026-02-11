<?php

namespace App\EventSubscriber;

use App\Event\ConjonctureDataUpdatedEvent;
use App\Service\AlerteService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber that automatically recalculates alerts
 * whenever conjoncture-related data is updated.
 */
class AlerteEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AlerteService $alerteService,
        private ?LoggerInterface $logger = null
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            ConjonctureDataUpdatedEvent::NAME => 'onConjonctureDataUpdated',
        ];
    }

    public function onConjonctureDataUpdated(ConjonctureDataUpdatedEvent $event): void
    {
        $conjoncture = $event->getConjoncture();
        $source = $event->getSource();

        try {
            $this->alerteService->calculateAlerts($conjoncture);
            
            $this->logger?->info('Alertes recalculées automatiquement', [
                'conjoncture_id' => $conjoncture->getId(),
                'date_situation' => $conjoncture->getDateSituation()?->format('Y-m-d'),
                'source' => $source,
            ]);
        } catch (\Exception $e) {
            // Log the error but don't break the main flow
            $this->logger?->error('Erreur lors du recalcul automatique des alertes', [
                'conjoncture_id' => $conjoncture->getId(),
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
