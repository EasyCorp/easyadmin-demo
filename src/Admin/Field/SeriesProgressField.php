<?php

namespace App\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Custom field to display series completion progress as a visual progress bar.
 */
final class SeriesProgressField implements FieldInterface
{
    use FieldTrait;

    private const HEX_COLOR_PATTERN = '/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/';

    public const OPTION_SHOW_PERCENTAGE = 'showPercentage';
    public const OPTION_SHOW_COUNT = 'showCount';
    public const OPTION_BAR_HEIGHT = 'barHeight';
    public const OPTION_COMPLETE_COLOR = 'completeColor';
    public const OPTION_INCOMPLETE_COLOR = 'incompleteColor';

    /**
     * @param TranslatableInterface|string|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplatePath('admin/field/series_progress.html.twig')
            ->setCustomOption(self::OPTION_SHOW_PERCENTAGE, true)
            ->setCustomOption(self::OPTION_SHOW_COUNT, true)
            ->setCustomOption(self::OPTION_BAR_HEIGHT, '8px')
            ->setCustomOption(self::OPTION_COMPLETE_COLOR, '#28a745')  // Bootstrap green
            ->setCustomOption(self::OPTION_INCOMPLETE_COLOR, '#6366f1') // EasyAdmin indigo
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

    public function setBarHeight(int $pixels): self
    {
        if ($pixels < 1) {
            throw new \InvalidArgumentException(sprintf('Bar height must be at least 1 pixel, %d given.', $pixels));
        }

        $this->setCustomOption(self::OPTION_BAR_HEIGHT, $pixels.'px');

        return $this;
    }

    public function setCompleteColor(string $color): self
    {
        $this->assertValidHexColor($color);
        $this->setCustomOption(self::OPTION_COMPLETE_COLOR, $color);

        return $this;
    }

    public function setIncompleteColor(string $color): self
    {
        $this->assertValidHexColor($color);
        $this->setCustomOption(self::OPTION_INCOMPLETE_COLOR, $color);

        return $this;
    }

    private function assertValidHexColor(string $color): void
    {
        if (!preg_match(self::HEX_COLOR_PATTERN, $color)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid hex color "%s". Expected format: #RGB or #RRGGBB (e.g., #fff or #28a745).',
                $color
            ));
        }
    }
}
