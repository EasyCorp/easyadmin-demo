<?php

namespace App\Story;

use App\Factory\CommentFactory;
use App\Factory\PostFactory;
use App\Factory\TagFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class InitialStateStory extends Story
{
    public function build(): void
    {
        TagFactory::createMany(10);
        UserFactory::createMany(5);
        CommentFactory::createMany(100);
        PostFactory::createMany(50, static function () {
            return [
                'comments' => CommentFactory::randomRange(0, 5),
                'tags' => TagFactory::randomRange(1, 5),
                'author' => UserFactory::random(),
            ];
        });
    }
}
