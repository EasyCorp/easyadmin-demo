<?php

namespace App\Twig;

use App\Admin\SourceCode\SourceCodeFile;
use App\Admin\SourceCode\SourceCodeReader;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFunction;

final class AppExtension
{
    public function __construct(
        private readonly Packages $packages,
        private readonly SourceCodeReader $sourceCodeReader,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    #[AsTwigFunction('avatar_url')]
    public function avatarUrl(?string $avatar, ?string $email, int $size = 80): string
    {
        if (null === $avatar || '' === $avatar) {
            return $this->gravatarUrl($email, $size);
        }

        return $this->packages->getUrl('uploads/avatars/'.$avatar);
    }

    /**
     * Returns the given class and its parent classes of this application, because
     * some CRUD controllers define most of their code in a parent controller.
     *
     * @param class-string $className
     *
     * @return list<array{path: string, code: string, language: string, marks: list<array{start: int, length: int}>}>
     */
    #[AsTwigFunction('source_code_files')]
    public function sourceCodeFiles(string $className): array
    {
        $files = [];
        for ($class = new \ReflectionClass($className); false !== $class && str_starts_with($class->getName(), 'App\\'); $class = $class->getParentClass()) {
            $files[] = $this->sourceCodeReader->read(SourceCodeFile::new(substr($class->getFileName(), \strlen($this->projectDir) + 1)));
        }

        return $files;
    }

    private function gravatarUrl(?string $email, int $size = 80): string
    {
        return sprintf(
            'https://www.gravatar.com/avatar/%s?s=%d&d=identicon',
            md5(strtolower(trim($email ?? ''))),
            $size
        );
    }
}
