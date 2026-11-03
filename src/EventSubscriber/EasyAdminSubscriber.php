<?php

namespace App\EventSubscriber;

use App\Entity\Family;
use App\Entity\Product;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Shows how to change entities right before EasyAdmin saves them: it generates
 * the missing slugs and assigns new products to the user who creates them.
 */
class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'onBeforeEntityPersisted',
            BeforeEntityUpdatedEvent::class => 'onBeforeEntityUpdated',
        ];
    }

    public function onBeforeEntityPersisted(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        $this->generateMissingSlug($entity);
        $this->assignManager($entity);
    }

    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event): void
    {
        $this->generateMissingSlug($event->getEntityInstance());
    }

    private function generateMissingSlug(object $entity): void
    {
        if (!$entity instanceof Product && !$entity instanceof Family) {
            return;
        }

        if ('' === (string) $entity->getSlug()) {
            $entity->setSlug($this->slugger->slug((string) $entity->getName())->lower()->toString());
        }
    }

    private function assignManager(object $entity): void
    {
        $user = $this->security->getUser();

        if ($entity instanceof Product && null === $entity->getManager() && $user instanceof User) {
            $entity->setManager($user);
        }
    }
}
