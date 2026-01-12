<?php

namespace App\Factory;

use App\Entity\Comment;
use App\Enum\CommentStatus;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Comment>
 */
final class CommentFactory extends PersistentObjectFactory
{
    private const COMMENT_TEMPLATES = [
        'Great article! I learned a lot from this.',
        'Thanks for sharing this. Very helpful!',
        'I have a question about the third section. Could you explain more?',
        'This is exactly what I was looking for. Bookmarked!',
        'Interesting perspective. I\'d love to see a follow-up post.',
        'I\'ve been struggling with this topic, and your explanation cleared things up.',
        'Nice work! Keep these articles coming.',
        'I disagree with some points, but overall a good read.',
        'Could you provide more code examples?',
        'This saved me hours of work. Thank you!',
        'Well written and easy to understand.',
        'I tried this approach and it worked perfectly.',
        'Looking forward to more content like this.',
        'Great tutorial! Very well explained.',
        'This is a must-read for beginners.',
    ];

    public static function class(): string
    {
        return Comment::class;
    }

    protected function defaults(): array|callable
    {
        // Weight towards approved status (most comments should be approved)
        $status = self::faker()->randomElement([
            CommentStatus::Approved,
            CommentStatus::Approved,
            CommentStatus::Approved,
            CommentStatus::Approved,
            CommentStatus::Pending,
            CommentStatus::Spam,
            CommentStatus::Rejected,
        ]);

        return [
            'author' => UserFactory::random(),
            'content' => self::faker()->randomElement(self::COMMENT_TEMPLATES).' '.self::faker()->optional(0.5)->sentence(),
            'post' => PostFactory::random(),
            'publishedAt' => \DateTimeImmutable::createFromMutable(
                self::faker()->dateTimeBetween('-1 year', 'now')
            ),
            'status' => $status,
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public function approved(): static
    {
        return $this->with(['status' => CommentStatus::Approved]);
    }

    public function pending(): static
    {
        return $this->with(['status' => CommentStatus::Pending]);
    }

    public function spam(): static
    {
        return $this->with(['status' => CommentStatus::Spam]);
    }
}
