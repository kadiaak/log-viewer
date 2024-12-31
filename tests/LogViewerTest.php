<?php

namespace Kadiaak\LogViewer\Tests;

use Kadiaak\LogViewer\Facades\LogViewer;
use Kadiaak\LogViewer\Logs\Level;

class LogViewerTest extends TestCase
{
    private string $sample = <<<'LOG'
    [2024-01-15 10:30:45] local.INFO: User logged in
    [2024-01-15 10:31:02] local.ERROR: Boom
    [2024-01-15 10:32:00] local.ERROR: Another boom
    [2024-01-15 10:33:00] local.DEBUG: debugging the search needle here
    LOG;

    public function test_it_discovers_files(): void
    {
        $this->makeLog('laravel.log', $this->sample);

        $files = LogViewer::files();

        $this->assertCount(1, $files);
        $this->assertSame('laravel.log', $files->first()->name);
    }

    public function test_it_counts_levels_and_paginates(): void
    {
        $this->makeLog('laravel.log', $this->sample);
        $file = LogViewer::files()->first();

        $result = $file->scan(['per_page' => 2, 'direction' => 'asc']);

        $this->assertSame(2, $result['level_counts'][Level::ERROR]);
        $this->assertSame(1, $result['level_counts'][Level::INFO]);
        $this->assertSame(4, $result['pagination']['total']);
        $this->assertCount(2, $result['entries']);
    }

    public function test_it_filters_by_level(): void
    {
        $this->makeLog('laravel.log', $this->sample);
        $file = LogViewer::files()->first();

        $result = $file->scan(['levels' => [Level::ERROR]]);

        $this->assertSame(2, $result['pagination']['total']);
    }

    public function test_it_searches(): void
    {
        $this->makeLog('laravel.log', $this->sample);
        $file = LogViewer::files()->first();

        $result = $file->scan(['query' => 'needle']);

        $this->assertSame(1, $result['pagination']['total']);
        $this->assertStringContainsString('needle', $result['entries'][0]['message']);
    }

    public function test_index_route_loads(): void
    {
        $this->makeLog('laravel.log', $this->sample);

        $this->get('log-viewer')->assertOk()->assertSee('Log Viewer');
    }

    public function test_api_returns_entries(): void
    {
        $this->makeLog('laravel.log', $this->sample);
        $id = LogViewer::files()->first()->identifier;

        $this->getJson("log-viewer/api/files/{$id}/logs")
            ->assertOk()
            ->assertJsonStructure(['entries', 'level_counts', 'pagination', 'file']);
    }

    public function test_api_lists_files(): void
    {
        $this->makeLog('laravel.log', $this->sample);

        $this->getJson('log-viewer/api/files')
            ->assertOk()
            ->assertJsonStructure(['folders']);
    }
}
