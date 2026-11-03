<?php

namespace App\Security;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Product>
 */
final class ProductVoter extends Voter
{
    public const VIEW = 'PRODUCT_VIEW';
    public const EDIT = 'PRODUCT_EDIT';

    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT], true) && ($subject instanceof Product || null === $subject);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        // the subject is null in the "new" page because EasyAdmin checks the entity
        // permission (defined with setEntityPermission()) before creating the entity
        if (null === $subject) {
            return self::VIEW === $attribute && $this->security->isGranted('ROLE_EDITOR');
        }

        return match ($attribute) {
            self::VIEW => $subject->isPublished() || $this->security->isGranted('ROLE_EDITOR'),
            self::EDIT => $this->security->isGranted('ROLE_ADMIN') || $this->isEditorAndManager($subject, $token),
            default => false,
        };
    }

    private function isEditorAndManager(Product $product, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return $user instanceof User
            && $this->security->isGranted('ROLE_EDITOR')
            && $user->getId() === $product->getManager()?->getId();
    }
}
