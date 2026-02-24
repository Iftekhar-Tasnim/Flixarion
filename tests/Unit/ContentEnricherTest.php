<?php

use App\DTOs\ParsedFilename;
use App\Models\Content;
use App\Models\ShadowContentSource;
use App\Models\Source;
use App\Services\ContentEnricher;
use App\Services\FilenameParser;
use App\Services\OmdbService;
use App\Services\TmdbService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('enricher successfully processes movie and creates content', function () {
    $source = Source::factory()->create();
    $shadow = ShadowContentSource::factory()->create([
        'source_id' => $source->id,
        'raw_filename' => 'Inception.2010.1080p.mkv',
        'file_path' => '/movies/Inception.2010.1080p.mkv',
    ]);

    // Mock dependencies
    $parser = Mockery::mock(FilenameParser::class);
    $tmdb = Mockery::mock(TmdbService::class);
    $omdb = Mockery::mock(OmdbService::class);

    $parsed = new ParsedFilename(
        title: 'Inception',
        year: 2010,
        quality: '1080p',
        type: 'movie'
    );

    $parser->shouldReceive('parse')->once()->andReturn($parsed);

    $tmdb->shouldReceive('searchMovie')->once()->with('Inception', 2010)->andReturn([
        'id' => 12345,
        'title' => 'Inception O',
    ]);
    $tmdb->shouldReceive('getMovieDetails')->once()->with(12345)->andReturn([
        'overview' => 'A thief...',
        'release_date' => '2010-07-15',
    ]);

    // We mock imageUrl to just return string
    $tmdb->shouldReceive('imageUrl')->andReturn('img.jpg');

    $enricher = new ContentEnricher($parser, $tmdb, $omdb);

    // Run
    $enricher->enrich($shadow);

    // Assert
    expect($shadow->fresh()->enrichment_status)->toBe('completed');

    $this->assertDatabaseCount('contents', 1);
    $this->assertDatabaseHas('contents', [
        'title' => 'Inception O', // from metadata
        'tmdb_id' => 12345,
        'type' => 'movie'
    ]);

    $this->assertDatabaseCount('source_links', 1);
    $this->assertDatabaseHas('source_links', [
        'linkable_type' => Content::class,
        'file_path' => '/movies/Inception.2010.1080p.mkv',
    ]);
});

test('enricher falls back to omdb and flags low confidence', function () {
    $source = Source::factory()->create();
    $shadow = ShadowContentSource::factory()->create([
        'source_id' => $source->id,
        'raw_filename' => 'UnknownMovie.2023.mkv',
        'file_path' => '/movies/UnknownMovie.2023.mkv',
    ]);

    $parser = Mockery::mock(FilenameParser::class);
    $tmdb = Mockery::mock(TmdbService::class);
    $omdb = Mockery::mock(OmdbService::class);

    $parsed = new ParsedFilename(title: 'UnknownMovie', year: 2023, type: 'movie');
    $parser->shouldReceive('parse')->once()->andReturn($parsed);

    // TMDb fails
    $tmdb->shouldReceive('searchMovie')->once()->andReturn(null);
    $tmdb->shouldReceive('imageUrl')->andReturn('img.jpg');

    // OMDb succeeds but title has low fuzzy match (confidence)
    $omdb->shouldReceive('search')->once()->with('UnknownMovie', 2023)->andReturn([
        'title' => 'Different Movie Title', // Low sim
        'year' => 2023,
    ]);

    $enricher = new ContentEnricher($parser, $tmdb, $omdb);
    $enricher->enrich($shadow);

    $content = Content::first();
    expect($shadow->fresh()->enrichment_status)->toBe('completed'); // shadow is completed (processed)
    // Wait, if confidence < threshold, enrichment_status is flagged on Content, but completed on Shadow source (it was processed)
    expect($content->enrichment_status)->toBe('flagged');
    expect($content->is_published)->toBeFalse();
});

test('enricher marks shadow as unmatched if both apis fail', function () {
    $source = Source::factory()->create();
    $shadow = ShadowContentSource::factory()->create();

    $parser = Mockery::mock(FilenameParser::class);
    $tmdb = Mockery::mock(TmdbService::class);
    $omdb = Mockery::mock(OmdbService::class);

    $parsed = new ParsedFilename(title: 'Giberish', year: 2023, type: 'movie');
    $parser->shouldReceive('parse')->once()->andReturn($parsed);

    $tmdb->shouldReceive('searchMovie')->once()->andReturn(null);
    $omdb->shouldReceive('search')->once()->andReturn(null);

    $enricher = new ContentEnricher($parser, $tmdb, $omdb);
    $enricher->enrich($shadow);

    expect($shadow->fresh()->enrichment_status)->toBe('unmatched');
    $this->assertDatabaseCount('contents', 0);
});
