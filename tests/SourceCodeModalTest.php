<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class SourceCodeModalTest extends WebTestCase
{
    public function testModalShowsTheControllerAndItsParentController(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/admin/plant');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a.action-viewSource[href="#source-code-modal"][data-bs-toggle="modal"]');

        $tabs = $crawler->filter('#source-code-modal [role="tab"]');
        $this->assertSame(['src/Controller/Admin/PlantCrudController.php', 'src/Controller/Admin/AbstractProductCrudController.php'], $tabs->each(static fn (Crawler $tab): string => $tab->text()));
        $this->assertSame(['true', 'false'], $tabs->each(static fn (Crawler $tab): string => (string) $tab->attr('aria-selected')));
        $this->assertSame('src/Controller/Admin/', $tabs->first()->filter('.source-code-file-dir')->text());
        $this->assertSame('PlantCrudController.php', $tabs->first()->filter('.source-code-file-name')->text());

        $links = $crawler->filter('#source-code-modal .source-code-tab > a.source-code-file-link')->each(static fn (Crawler $link): string => (string) $link->attr('href'));
        $this->assertSame([
            'https://github.com/EasyCorp/easyadmin-demo/blob/main/src/Controller/Admin/PlantCrudController.php',
            'https://github.com/EasyCorp/easyadmin-demo/blob/main/src/Controller/Admin/AbstractProductCrudController.php',
        ], $links);

        $panes = $crawler->filter('#source-code-modal [role="tabpanel"]');
        $this->assertCount(2, $panes);
        $this->assertSame('source-code-tab-1', $panes->first()->attr('aria-labelledby'));
        $this->assertStringContainsString('class PlantCrudController', $panes->first()->filter('code')->text());
    }

    public function testCodeWithASingleFileHasNoTabs(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/admin/feature-tour/csv_export/code');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('.source-code-file'));
        $this->assertCount(0, $crawler->filter('[role="tablist"]'));
        $this->assertSelectorExists('.source-code-file-header .source-code-file-name');
        $this->assertSelectorExists('.source-code-file-header a.source-code-file-link');
    }

    public function testFeatureTourShowsTheMarkedCodeOfEachFeature(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/admin/feature-tour');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#source-code-modal');

        $urls = $crawler->filter('button[data-bs-target="#source-code-modal"]')->each(static fn (Crawler $button): string => (string) $button->attr('data-source-code-url'));
        $this->assertCount($crawler->filter('.feature-tour-item')->count(), $urls);

        foreach ($urls as $url) {
            $crawler = $client->request('GET', $url);

            $this->assertResponseIsSuccessful(sprintf('The code of "%s" is not available.', $url));
            $this->assertGreaterThan(0, $crawler->filter('.source-code-file')->count(), sprintf('The code of "%s" does not include any file.', $url));
            $this->assertGreaterThan(0, $crawler->filter('.source-code-mark')->count(), sprintf('The code of "%s" does not mark any line.', $url));
        }
    }

    public function testMarksIncludeTheAttributesOfMethods(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/admin/feature-tour/csv_export/code');

        $this->assertResponseIsSuccessful();
        $markedCode = $this->getMarkedCode($crawler->filter('.source-code-file')->first());

        $this->assertCount(2, $markedCode);
        $this->assertStringStartsWith('$exportCsvAction = Action::new(\'exportCsv\'', trim($markedCode[0]));
        $this->assertStringEndsWith('->createAsGlobalAction();', trim($markedCode[0]));
        $this->assertStringStartsWith('#[AdminRoute(\'/export\'', trim($markedCode[1]));
        $this->assertStringContainsString('public function exportCsv(): StreamedResponse', $markedCode[1]);
        $this->assertStringEndsWith('}', trim($markedCode[1]));
    }

    public function testUnknownFeaturesHaveNoCode(): void
    {
        $client = static::createClient();
        $client->request('GET', '/en/admin/feature-tour/feature_1/code');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * @return list<string>
     */
    private function getMarkedCode(Crawler $file): array
    {
        $lines = explode("\n", $file->filter('code')->text(normalizeWhitespace: false));

        return $file->filter('.source-code-mark')->each(static function (Crawler $mark) use ($lines): string {
            preg_match('/--first-line: (\d+); --num-lines: (\d+)/', (string) $mark->attr('style'), $matches);

            return implode("\n", \array_slice($lines, (int) $matches[1] - 1, (int) $matches[2]));
        });
    }
}
