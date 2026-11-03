<?php

namespace App\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Custom field that shows how many products of a family are in stock with a progress bar.
 */
final class FamilyStockField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_SHOW_PERCENTAGE = 'showPercentage';
    public const OPTION_SHOW_COUNT = 'showCount';
    public const OPTION_BAR_HEIGHT = 'barHeight';

    /**
     * @param TranslatableInterface|string|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/field/family_stock.html.twig')
            ->setCustomOption(self::OPTION_SHOW_PERCENTAGE, true)
            ->setCustomOption(self::OPTION_SHOW_COUNT, true)
            ->setCustomOption(self::OPTION_BAR_HEIGHT, '8px')
            ->setVirtual(true)
            ->setSortable(false);
    }

    public function showPercentage(bool $show = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_PERCENTAGE, $show);

        return $this;
    }

    public function showCount(bool $show = true): self
    {
        $this->setCustomOption(self::OPTION_SHOW_COUNT, $show);

        return $this;
    }
}
