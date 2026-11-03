<?php

namespace App\Admin;

use Symfony\Component\Translation\TranslatableMessage;

final class EntityPageTitle
{
    /**
     * @param array<string, string> $values
     */
    public static function detail(string $titleName, array $values): TranslatableMessage
    {
        return self::create('detail_title.'.$titleName, $values);
    }

    /**
     * @param array<string, string> $values
     */
    public static function edit(string $titleName, array $values): TranslatableMessage
    {
        return self::create('edit_title.'.$titleName, $values);
    }

    /**
     * EasyAdmin renders page titles as raw HTML, so the values that come from the
     * database must be escaped here to not allow injecting HTML contents.
     *
     * @param array<string, string> $values
     */
    private static function create(string $translationId, array $values): TranslatableMessage
    {
        return new TranslatableMessage(
            $translationId,
            array_map(static fn (string $value): string => htmlspecialchars($value, \ENT_QUOTES), $values),
        );
    }
}
