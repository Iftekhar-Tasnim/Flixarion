<?php

use App\Services\FilenameParser;
use Tests\TestCase;

uses(TestCase::class);

test('parses movie filename with year and quality', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Inception.2010.1080p.BluRay.x264.mkv');

    expect($result->title)->toBe('Inception');
    expect($result->year)->toBe(2010);
    expect($result->quality)->toBe('1080p');
    expect($result->type)->toBe('movie');
    expect($result->codec)->toBe('x264');
    expect($result->sourceType)->toBe('BluRay');
    expect($result->season)->toBeNull();
    expect($result->episode)->toBeNull();
});

test('parses series filename with season and episode', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Breaking.Bad.S01E01.720p.WEB-DL.mkv');

    expect($result->title)->toBe('Breaking Bad');
    expect($result->type)->toBe('series');
    expect($result->season)->toBe(1);
    expect($result->episode)->toBe(1);
    expect($result->quality)->toBe('720p');
    expect($result->sourceType)->toBe('WEB-DL');
});

test('parses 4K movie', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('The.Dark.Knight.2008.2160p.UHD.BluRay.x265.mkv');

    expect($result->title)->toBe('The Dark Knight');
    expect($result->year)->toBe(2008);
    expect($result->quality)->toBe('2160p');
    expect($result->codec)->toBe('x265');
});

test('handles spaces instead of dots', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('The Dark Knight 2008 1080p.mkv');

    expect($result->title)->toBe('The Dark Knight');
    expect($result->year)->toBe(2008);
    expect($result->quality)->toBe('1080p');
});

test('handles underscores', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Inception_2010_1080p_BluRay.mkv');

    expect($result->title)->toBe('Inception');
    expect($result->year)->toBe(2010);
});

test('handles multi-part detection', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Kill.Bill.2003.CD1.1080p.BluRay.mkv');

    expect($result->title)->toBe('Kill Bill');
    expect($result->partNumber)->toBe(1);
});

test('handles multi-part with Part keyword', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Harry.Potter.2010.Part.2.720p.mkv');

    expect($result->partNumber)->toBe(2);
});

test('parses filename without year', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Interstellar.1080p.BluRay.mkv');

    expect($result->title)->not->toBeEmpty();
    expect($result->quality)->toBe('1080p');
    expect($result->year)->toBeNull();
});

test('isSeries and isMovie methods work', function () {
    $parser = new FilenameParser();

    $movie = $parser->parse('Inception.2010.mkv');
    expect($movie->isMovie())->toBeTrue();
    expect($movie->isSeries())->toBeFalse();

    $series = $parser->parse('Breaking.Bad.S01E01.mkv');
    expect($series->isSeries())->toBeTrue();
    expect($series->isMovie())->toBeFalse();
});

test('quality score is calculated', function () {
    $parser = new FilenameParser();

    $result = $parser->parse('Inception.2010.1080p.BluRay.mkv');
    expect($result->qualityScore)->toBeGreaterThan(0);
});

test('extracts 4k quality', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Movie.2020.4k.mkv');

    expect($result->quality)->toBe('4k');
});

test('toArray returns all fields', function () {
    $parser = new FilenameParser();
    $result = $parser->parse('Inception.2010.1080p.mkv');

    $array = $result->toArray();
    expect($array)->toHaveKeys(['title', 'year', 'quality', 'type', 'season', 'episode', 'codec', 'source_type', 'quality_score', 'part_number']);
});
