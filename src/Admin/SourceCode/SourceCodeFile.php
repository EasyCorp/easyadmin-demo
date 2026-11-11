<?php

namespace App\Admin\SourceCode;

/**
 * A file of this application whose code is shown in the source code modal, and the
 * parts of that code which are marked as the relevant ones.
 *
 * Marks are described with names and text instead of line numbers, so they keep
 * working when the file changes. A mark that matches nothing throws an exception.
 */
final class SourceCodeFile
{
    /** @var list<array{type: SourceCodeMarkType, value: string, end?: string}> */
    private array $marks = [];

    private function __construct(
        public readonly string $path,
    ) {
    }

    public static function new(string $path): self
    {
        return new self($path);
    }

    /**
     * Marks the declaration of the class defined in this PHP file, including its attributes.
     */
    public function markClass(): self
    {
        $this->marks[] = ['type' => SourceCodeMarkType::ClassDeclaration, 'value' => ''];

        return $this;
    }

    /**
     * Marks the given methods of the class defined in this PHP file, including their attributes and docblocks.
     */
    public function markMethods(string ...$methodNames): self
    {
        foreach ($methodNames as $methodName) {
            $this->marks[] = ['type' => SourceCodeMarkType::Method, 'value' => $methodName];
        }

        return $this;
    }

    /**
     * Marks every line that contains the given text.
     */
    public function markLines(string ...$texts): self
    {
        foreach ($texts as $text) {
            $this->marks[] = ['type' => SourceCodeMarkType::Lines, 'value' => $text];
        }

        return $this;
    }

    /**
     * Marks every PHP statement that contains the given text, from that line to the
     * line that ends with a semicolon.
     */
    public function markStatements(string ...$texts): self
    {
        foreach ($texts as $text) {
            $this->marks[] = ['type' => SourceCodeMarkType::Statement, 'value' => $text];
        }

        return $this;
    }

    /**
     * Marks every block of code that starts with a line that contains the given text and ends
     * with the first line that has the same indentation and starts with the given end text
     * (e.g. an HTML element or a CSS rule).
     */
    public function markBlocks(string $startText, string $endText): self
    {
        $this->marks[] = ['type' => SourceCodeMarkType::Block, 'value' => $startText, 'end' => $endText];

        return $this;
    }

    /**
     * @return list<array{type: SourceCodeMarkType, value: string, end?: string}>
     */
    public function getMarks(): array
    {
        return $this->marks;
    }
}
