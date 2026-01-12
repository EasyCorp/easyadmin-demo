<?php

namespace App\Factory;

use App\Entity\Post;
use App\Enum\PostStatus;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Post>
 */
final class PostFactory extends PersistentObjectFactory
{
    private const TITLES = [
        'How to Build Scalable Web Applications',
        '10 Tips for Better Code Reviews',
        'Understanding Dependency Injection',
        'The Future of PHP Development',
        'Getting Started with Symfony 7',
        'Database Performance Optimization Guide',
        'Modern CSS Techniques You Should Know',
        'REST API Design Best Practices',
        'Introduction to Docker Containers',
        'Securing Your Web Application',
        'Test-Driven Development Explained',
        'Working with Doctrine ORM',
        'JavaScript Frameworks Comparison',
        'CI/CD Pipeline Setup Tutorial',
        'Microservices Architecture Overview',
        'Clean Code Principles',
        'Git Workflow Strategies',
        'Performance Monitoring Tools',
        'Building Real-Time Applications',
        'Cloud Deployment Strategies',
    ];

    private static int $titleIndex = 0;

    public static function class(): string
    {
        return Post::class;
    }

    protected function defaults(): array|callable
    {
        $title = self::TITLES[self::$titleIndex % \count(self::TITLES)].' #'.(self::$titleIndex + 1);
        ++self::$titleIndex;

        $slugger = new AsciiSlugger();
        $status = self::faker()->randomElement([
            PostStatus::Draft,
            PostStatus::Published,
            PostStatus::Published,
            PostStatus::Published,
            PostStatus::Scheduled,
            PostStatus::Archived,
        ]);

        return [
            'title' => $title,
            'slug' => $slugger->slug($title)->lower(),
            'summary' => self::faker()->paragraph(3),
            'content' => self::generateRichContent(),
            'author' => UserFactory::random(),
            'status' => $status,
            'publishedAt' => PostStatus::Published === $status
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-2 years', 'now'))
                : null,
            'scheduledAt' => PostStatus::Scheduled === $status
                ? \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('now', '+1 month'))
                : null,
            'viewCount' => PostStatus::Published === $status ? self::faker()->numberBetween(0, 10000) : 0,
            'isFeatured' => self::faker()->boolean(15),
            'featuredImage' => null,
            'category' => null,
            'series' => null,
            'seriesPosition' => null,
        ];
    }

    private static function generateRichContent(): string
    {
        $paragraphs = [];

        // Introduction
        $paragraphs[] = '<p>'.self::faker()->paragraph(4).'</p>';

        // First section with heading
        $paragraphs[] = '<h2>'.self::faker()->sentence(4).'</h2>';
        $paragraphs[] = '<p>'.self::faker()->paragraph(5).'</p>';
        $paragraphs[] = '<p>'.self::faker()->paragraph(4).'</p>';

        // Bullet list
        $paragraphs[] = '<ul>';
        for ($i = 0; $i < self::faker()->numberBetween(3, 6); ++$i) {
            $paragraphs[] = '<li>'.self::faker()->sentence().'</li>';
        }
        $paragraphs[] = '</ul>';

        // Second section
        $paragraphs[] = '<h2>'.self::faker()->sentence(4).'</h2>';
        $paragraphs[] = '<p>'.self::faker()->paragraph(5).'</p>';

        // Code block
        $paragraphs[] = '<pre><code>// Example code
$example = new Example();
$example->doSomething();
return $example->getResult();</code></pre>';

        $paragraphs[] = '<p>'.self::faker()->paragraph(4).'</p>';

        // Third section
        $paragraphs[] = '<h2>'.self::faker()->sentence(3).'</h2>';
        $paragraphs[] = '<p>'.self::faker()->paragraph(5).'</p>';
        $paragraphs[] = '<p>'.self::faker()->paragraph(4).'</p>';

        // Conclusion
        $paragraphs[] = '<h2>Conclusion</h2>';
        $paragraphs[] = '<p>'.self::faker()->paragraph(3).'</p>';

        return implode("\n", $paragraphs);
    }

    protected function initialize(): static
    {
        return $this;
    }

    public function published(): static
    {
        return $this->with([
            'status' => PostStatus::Published,
            'publishedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 year', 'now')),
        ]);
    }

    public function draft(): static
    {
        return $this->with([
            'status' => PostStatus::Draft,
            'publishedAt' => null,
        ]);
    }

    public function featured(): static
    {
        return $this->with([
            'isFeatured' => true,
        ]);
    }

    public static function resetTitleIndex(): void
    {
        self::$titleIndex = 0;
    }
}
