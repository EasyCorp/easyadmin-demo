<?php

namespace App\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Custom field to display a rating as stars and edit it with a list of the allowed values.
 */
final class RatingField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_MAX_RATING = 'maxRating';

    private const DEFAULT_MAX_RATING = 5;

    /**
     * @param TranslatableInterface|string|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        $ratings = range(1, self::DEFAULT_MAX_RATING);

        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/field/rating.html.twig')
            ->setFormType(ChoiceType::class)
            ->setFormTypeOptions([
                'choices' => array_combine($ratings, $ratings),
                'choice_translation_domain' => false,
                'placeholder' => '',
            ])
            ->setCustomOption(self::OPTION_MAX_RATING, self::DEFAULT_MAX_RATING);
    }
}
