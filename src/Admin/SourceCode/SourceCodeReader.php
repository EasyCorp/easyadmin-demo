<?php

namespace App\Admin\SourceCode;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Reads the code of the files shown in the source code modal and turns their marks
 * into the line ranges that the template highlights.
 */
final class SourceCodeReader
{
    private const LANGUAGES = ['php' => 'php', 'twig' => 'twig', 'yaml' => 'yaml', 'css' => 'css'];

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array{path: string, code: string, language: string, marks: list<array{start: int, length: int}>}
     */
    public function read(SourceCodeFile $file): array
    {
        $filePath = $this->projectDir.'/'.$file->path;
        if (!is_file($filePath)) {
            throw new \LogicException(sprintf('The source code file "%s" does not exist.', $filePath));
        }

        $code = file_get_contents($filePath);
        $lines = explode("\n", $code);

        $markedLineNumbers = [];
        foreach ($file->getMarks() as $mark) {
            $ranges = match ($mark['type']) {
                SourceCodeMarkType::ClassDeclaration => [$this->findClassDeclaration($file->path, $lines)],
                SourceCodeMarkType::Method => [$this->findMethod($file->path, $mark['value'], $lines)],
                SourceCodeMarkType::Lines => array_map(static fn (int $line): array => [$line, $line], $this->findLinesContaining($file->path, $mark['value'], $lines)),
                SourceCodeMarkType::Statement => $this->findStatements($file->path, $mark['value'], $lines),
                SourceCodeMarkType::Block => $this->findBlocks($file->path, $mark['value'], $mark['end'] ?? '', $lines),
            };

            foreach ($ranges as [$firstLine, $lastLine]) {
                array_push($markedLineNumbers, ...range($firstLine, $lastLine));
            }
        }

        return [
            'path' => $file->path,
            'code' => $code,
            'language' => self::LANGUAGES[pathinfo($file->path, \PATHINFO_EXTENSION)] ?? 'plaintext',
            'marks' => $this->groupConsecutiveLines($markedLineNumbers),
        ];
    }

    /**
     * @param list<string> $lines
     *
     * @return array{int, int}
     */
    private function findClassDeclaration(string $path, array $lines): array
    {
        $class = $this->reflectClass($path);

        return [$this->includeLinesAbove($class->getStartLine(), $lines), $class->getStartLine()];
    }

    /**
     * @param list<string> $lines
     *
     * @return array{int, int}
     */
    private function findMethod(string $path, string $methodName, array $lines): array
    {
        $class = $this->reflectClass($path);
        if (!$class->hasMethod($methodName) || $class->getName() !== $class->getMethod($methodName)->getDeclaringClass()->getName()) {
            throw new \LogicException(sprintf('The class "%s" does not define a method called "%s()".', $class->getName(), $methodName));
        }

        $method = $class->getMethod($methodName);

        return [$this->includeLinesAbove($method->getStartLine(), $lines), $method->getEndLine()];
    }

    /**
     * @param list<string> $lines
     *
     * @return non-empty-list<int>
     */
    private function findLinesContaining(string $path, string $text, array $lines): array
    {
        $lineNumbers = [];
        foreach ($lines as $index => $line) {
            if (str_contains($line, $text)) {
                $lineNumbers[] = $index + 1;
            }
        }

        if ([] === $lineNumbers) {
            throw new \LogicException(sprintf('No line of the file "%s" contains "%s".', $path, $text));
        }

        return $lineNumbers;
    }

    /**
     * @param list<string> $lines
     *
     * @return list<array{int, int}>
     */
    private function findStatements(string $path, string $text, array $lines): array
    {
        return array_map(
            fn (int $firstLine): array => [$firstLine, $this->findLastLine($path, $text, $lines, $firstLine, static fn (string $line): bool => str_ends_with(rtrim($line), ';'))],
            $this->findLinesContaining($path, $text, $lines),
        );
    }

    /**
     * @param list<string> $lines
     *
     * @return list<array{int, int}>
     */
    private function findBlocks(string $path, string $startText, string $endText, array $lines): array
    {
        return array_map(
            function (int $firstLine) use ($path, $startText, $endText, $lines): array {
                $indentation = $this->getIndentation($lines[$firstLine - 1]);
                $isLastLine = fn (string $line): bool => $indentation === $this->getIndentation($line) && str_starts_with(ltrim($line), $endText);

                return [$firstLine, $this->findLastLine($path, $startText, $lines, $firstLine + 1, $isLastLine)];
            },
            $this->findLinesContaining($path, $startText, $lines),
        );
    }

    /**
     * @param list<string>           $lines
     * @param callable(string): bool $isLastLine
     */
    private function findLastLine(string $path, string $text, array $lines, int $fromLine, callable $isLastLine): int
    {
        for ($lineNumber = $fromLine; $lineNumber <= \count($lines); ++$lineNumber) {
            if ($isLastLine($lines[$lineNumber - 1])) {
                return $lineNumber;
            }
        }

        throw new \LogicException(sprintf('The code that contains "%s" in the file "%s" never ends.', $text, $path));
    }

    /**
     * Reflection starts classes and methods at their declaration, but their attributes,
     * docblocks and comments are part of the code that explains them.
     *
     * @param list<string> $lines
     */
    private function includeLinesAbove(int $startLine, array $lines): int
    {
        while ($startLine > 1 && preg_match('{^\s*(#\[|/\*\*|\*|//)}', $lines[$startLine - 2])) {
            --$startLine;
        }

        return $startLine;
    }

    /**
     * @return \ReflectionClass<object>
     */
    private function reflectClass(string $path): \ReflectionClass
    {
        if (!str_starts_with($path, 'src/') || !str_ends_with($path, '.php')) {
            throw new \LogicException(sprintf('Only the PHP files of the "src/" directory define classes, but "%s" was given.', $path));
        }

        $className = 'App\\'.str_replace('/', '\\', substr($path, \strlen('src/'), -\strlen('.php')));
        if (!class_exists($className) && !interface_exists($className) && !trait_exists($className)) {
            throw new \LogicException(sprintf('The file "%s" does not define the "%s" class.', $path, $className));
        }

        return new \ReflectionClass($className);
    }

    private function getIndentation(string $line): string
    {
        return substr($line, 0, \strlen($line) - \strlen(ltrim($line)));
    }

    /**
     * @param list<int> $lineNumbers
     *
     * @return list<array{start: int, length: int}>
     */
    private function groupConsecutiveLines(array $lineNumbers): array
    {
        $lineNumbers = array_values(array_unique($lineNumbers));
        sort($lineNumbers);

        $groups = [];
        foreach ($lineNumbers as $lineNumber) {
            $lastGroup = array_key_last($groups);
            if (null !== $lastGroup && $groups[$lastGroup]['start'] + $groups[$lastGroup]['length'] === $lineNumber) {
                ++$groups[$lastGroup]['length'];

                continue;
            }

            $groups[] = ['start' => $lineNumber, 'length' => 1];
        }

        return $groups;
    }
}
