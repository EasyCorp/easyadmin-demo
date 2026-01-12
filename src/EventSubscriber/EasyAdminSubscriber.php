<?php

namespace App\EventSubscriber;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Series;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Event subscriber for EasyAdmin entity lifecycle events.
 *
 * Demonstrates:
 * - Auto-generating slugs from title/name fields
 * - Auto-setting the author field from the logged-in user
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
            BeforeEntityPersistedEvent::class => ['onBeforeEntityPersisted'],
            BeforeEntityUpdatedEvent::class => ['onBeforeEntityUpdated'],
        ];
    }

    public function onBeforeEntityPersisted(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        // Auto-generate slug
        $this->autoGenerateSlug($entity);

        // Auto-set author for new entities
        $this->autoSetAuthor($entity);
    }

    public function onBeforeEntityUpdated(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();

        // Auto-generate slug if empty (allows manual override)
        $this->autoGenerateSlug($entity);
    }

    private function autoGenerateSlug(object $entity): void
    {
        // Handle Post entities
        if ($entity instanceof Post) {
            if (empty($entity->getSlug()) && !empty($entity->getTitle())) {
                $slug = $this->slugger->slug($entity->getTitle())->lower();
                $entity->setSlug($slug);
            }

            return;
        }

        // Handle Category entities
        if ($entity instanceof Category) {
            if (empty($entity->getSlug()) && !empty($entity->getName())) {
                $slug = $this->slugger->slug($entity->getName())->lower();
                $entity->setSlug($slug);
            }

            return;
        }

        // Handle Series entities
        if ($entity instanceof Series) {
            if (empty($entity->getSlug()) && !empty($entity->getTitle())) {
                $slug = $this->slugger->slug($entity->getTitle())->lower();
                $entity->setSlug($slug);
            }

            return;
        }
    }

    private function autoSetAuthor(object $entity): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof \App\Entity\User) {
            return;
        }

        // Handle Post entities
        if ($entity instanceof Post) {
            if (null === $entity->getAuthor()) {
                $entity->setAuthor($user);
            }

            return;
        }

        // Handle Series entities
        if ($entity instanceof Series) {
            if (null === $entity->getAuthor()) {
                $entity->setAuthor($user);
            }

            return;
        }
    }
}
