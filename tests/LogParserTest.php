<?php

namespace Kadiaak\LogViewer\Tests;

use Kadiaak\LogViewer\Logs\Level;
use Kadiaak\LogViewer\Logs\LogParser;

class LogParserTest extends TestCase
{
    private string $sample = <<<'LOG'
    [2024-01-15 10:30:45] local.INFO: User logged in {"id":1}
    [2024-01-15 10:31:02] production.ERROR: Something broke
    #0 /app/foo.php(12): bar()
    #1 {main}
    [2024-01-15 10:32:00] local.DEBUG: Just debugging
    LOG;

    public function test_it_parses_entries(): void
    {
        $path = $this->makeLog('test.log', $this->sample);

        $entries = iterator_to_array((new LogParser())->parseFile($path));

        $this->assertCount(3, $entries);
        $this->assertSame(Level::INFO, $entries[0]->level);
        $this->assertSame('User logged in {"id":1}', $entries[0]->message);
        $this->assertSame('local', $entries[0]->environment);
    }

    public function test_it_captures_multiline_body(): void
    {
        $path = $this->makeLog('test.log', $this->sample);

        $entries = iterator_to_array((new LogParser())->parseFile($path));

        $this->assertSame(Level::ERROR, $entries[1]->level);
        $this->assertStringContainsString('#0 /app/foo.php(12): bar()', $entries[1]->body);
        $this->assertStringContainsString('#1 {main}', $entries[1]->body);
        $this->assertTrue($entries[1]->hasBody());
        $this->assertFalse($entries[0]->hasBody());
    }

    public function test_it_parses_datetime(): void
    {
        $path = $this->makeLog('test.log', $this->sample);

        $entries = iterator_to_array((new LogParser())->parseFile($path));

        $this->assertNotNull($entries[0]->datetime);
        $this->assertSame('2024-01-15 10:30:45', $entries[0]->datetime->format('Y-m-d H:i:s'));
    }
}
