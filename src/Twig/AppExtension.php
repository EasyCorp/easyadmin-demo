<?php

namespace App\Twig;

use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class AppExtension
{
    #[AsTwigFunction('gravatar_url')]
    public function gravatarUrl(?string $email, int $size = 80): string
    {
        $hash = md5(strtolower(trim($email ?? '')));

        return sprintf('https://www.gravatar.com/avatar/%s?s=%d&d=identicon', $hash, $size);
    }

    #[AsTwigFilter('word_count')]
    public function wordCount(?string $html): int
    {
        if (null === $html || '' === $html) {
            return 0;
        }

        $text = strip_tags($html);
        $text = html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = trim($text);

        if ('' === $text) {
            return 0;
        }

        // Unicode-aware word count using \p{L} (letters) and \p{N} (numbers)
        return preg_match_all('/[\p{L}\p{N}]+/u', $text);
    }
}
