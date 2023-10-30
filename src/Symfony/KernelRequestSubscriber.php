<?php

namespace App\Symfony;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class KernelRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $type = $event->getRequest()->query->get('type') ?? $event->getRequest()->request->get('type');
        $filters = $this->entityManager->getFilters();
        if ($type !== null) {
            $filter = $filters->enable('type_filter');
            $filter->setParameter('type', $type);
        } else {
            if ($filters->isEnabled('type_filter')) {
                $filters->disable('type_filter');
            }
        }
    }
}
