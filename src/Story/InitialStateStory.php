<?php

namespace App\Story;

use App\Enum\PostStatus;
use App\Factory\CategoryFactory;
use App\Factory\CommentFactory;
use App\Factory\PostFactory;
use App\Factory\SeriesFactory;
use App\Factory\SubscriberFactory;
use App\Factory\TagFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture('initial_state')]
final class InitialStateStory extends Story
{
    public function build(): void
    {
        // Reset factory indexes
        CategoryFactory::resetPosition();
        SeriesFactory::resetIndex();
        TagFactory::resetIndex();
        PostFactory::resetTitleIndex();

        // Create users first (we need them for posts, comments, and series)
        UserFactory::new()->admin()->create(['email' => 'admin@example.com', 'fullName' => 'Admin User']);
        UserFactory::new()->editor()->create();
        UserFactory::new()->editor()->create();
        UserFactory::createMany(7);

        // Create all categories
        CategoryFactory::createMany(8);

        // Create all tags
        TagFactory::createMany(15);

        // Create series - first 2 will be complete, rest incomplete
        $seriesList = [];
        for ($s = 0; $s < 5; ++$s) {
            $isCompleteSeries = $s < 2;
            $seriesList[] = SeriesFactory::createOne(['isComplete' => $isCompleteSeries]);
        }

        // Create posts with categories, tags, and some in series
        $allCategories = CategoryFactory::all();

        // Create posts in series (3-5 posts per series)
        // Complete series have all posts published
        // Incomplete series have their last post as draft
        foreach ($seriesList as $index => $series) {
            $isCompleteSeries = $index < 2;
            $postsInSeries = rand(3, 5);

            for ($i = 1; $i <= $postsInSeries; ++$i) {
                $isLastPost = ($i === $postsInSeries);
                // In complete series, all posts are published
                // In incomplete series, last post is draft
                $isDraft = !$isCompleteSeries && $isLastPost;

                PostFactory::createOne([
                    'series' => $series,
                    'seriesPosition' => $i,
                    'author' => $series->getAuthor(),
                    'category' => $allCategories[array_rand($allCategories)],
                    'tags' => TagFactory::randomRange(2, 5),
                    'status' => $isDraft ? PostStatus::Draft : PostStatus::Published,
                    'publishedAt' => $isDraft ? null : new \DateTimeImmutable('-'.(($postsInSeries - $i) * 7).' days'),
                ]);
            }
        }

        // Create standalone posts (not in series)
        PostFactory::createMany(30, static fn () => [
            'category' => CategoryFactory::random(),
            'tags' => TagFactory::randomRange(1, 5),
            'author' => UserFactory::random(),
        ]);

        // Create comments for published posts only
        $allPosts = PostFactory::all();
        foreach ($allPosts as $postProxy) {
            if ($postProxy->isPublished()) {
                $commentCount = random_int(0, 8);
                for ($i = 0; $i < $commentCount; ++$i) {
                    CommentFactory::createOne([
                        'post' => $postProxy,
                        'author' => UserFactory::random(),
                    ]);
                }
            }
        }

        // Create subscribers
        SubscriberFactory::createMany(40);
        SubscriberFactory::new()->pending()->many(10)->create();
    }
}
