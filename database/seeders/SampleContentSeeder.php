<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\Genre;
use Illuminate\Database\Seeder;

class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        $action = Genre::where('slug', 'action')->first();
        $drama = Genre::where('slug', 'drama')->first();
        $comedy = Genre::where('slug', 'comedy')->first();
        $scifi = Genre::where('slug', 'science-fiction')->first();
        $thriller = Genre::where('slug', 'thriller')->first();

        $contents = [
            [
                'tmdb_id' => 155,
                'imdb_id' => 'tt0468569',
                'type' => 'movie',
                'title' => 'The Dark Knight',
                'original_title' => 'The Dark Knight',
                'year' => 2008,
                'description' => 'Batman raises the stakes in his war on crime. With the help of Lt. Jim Gordon and District Attorney Harvey Dent, Batman sets out to dismantle the remaining criminal organizations.',
                'poster_path' => '/qJ2tW6WMUDux911kpUpLlmHdEE.jpg',
                'backdrop_path' => '/nMKdUUepR0i5zn0y1T4CsSB5eld.jpg',
                'rating' => 9.0,
                'vote_count' => 30000,
                'runtime' => 152,
                'language' => 'en',
                'enrichment_status' => 'completed',
                'confidence_score' => 100,
                'is_published' => true,
                'is_featured' => true,
                'watch_count' => 2500,
                'genres' => [$action, $drama, $thriller],
            ],
            [
                'tmdb_id' => 27205,
                'imdb_id' => 'tt1375666',
                'type' => 'movie',
                'title' => 'Inception',
                'original_title' => 'Inception',
                'year' => 2010,
                'description' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
                'poster_path' => '/edv5CZvWj09upOsy2Y6IwDhK8bt.jpg',
                'backdrop_path' => '/s3TBrRGB1iav7gFOCNx3H31MoES.jpg',
                'rating' => 8.4,
                'vote_count' => 34000,
                'runtime' => 148,
                'language' => 'en',
                'enrichment_status' => 'completed',
                'confidence_score' => 100,
                'is_published' => true,
                'is_featured' => false,
                'watch_count' => 1800,
                'genres' => [$action, $scifi, $thriller],
            ],
            [
                'tmdb_id' => 550,
                'imdb_id' => 'tt0137523',
                'type' => 'movie',
                'title' => 'Fight Club',
                'original_title' => 'Fight Club',
                'year' => 1999,
                'description' => 'A ticking-Loss bomb insomniac and a slippery soap salesman channel primal male aggression into a shocking new form of therapy.',
                'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
                'backdrop_path' => '/hZkgoQYus5dXo3H8T7Uef6DNknx.jpg',
                'rating' => 8.4,
                'vote_count' => 26000,
                'runtime' => 139,
                'language' => 'en',
                'enrichment_status' => 'completed',
                'confidence_score' => 100,
                'is_published' => true,
                'is_featured' => false,
                'watch_count' => 1200,
                'genres' => [$drama, $thriller],
            ],
            [
                'tmdb_id' => 1396,
                'imdb_id' => 'tt0903747',
                'type' => 'series',
                'title' => 'Breaking Bad',
                'original_title' => 'Breaking Bad',
                'year' => 2008,
                'description' => 'A high school chemistry teacher diagnosed with cancer turns to manufacturing and selling methamphetamine to secure his family\'s future.',
                'poster_path' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg',
                'backdrop_path' => '/tsRy63Mu5cu8etL1X7ZLyf7UP1M.jpg',
                'rating' => 8.9,
                'vote_count' => 12000,
                'runtime' => 47,
                'language' => 'en',
                'enrichment_status' => 'completed',
                'confidence_score' => 100,
                'is_published' => true,
                'is_featured' => true,
                'watch_count' => 3200,
                'genres' => [$drama, $thriller],
            ],
            [
                'tmdb_id' => 1399,
                'imdb_id' => 'tt0944947',
                'type' => 'series',
                'title' => 'Game of Thrones',
                'original_title' => 'Game of Thrones',
                'year' => 2011,
                'description' => 'Nine noble families fight for control over the lands of Westeros, while an ancient enemy returns after being dormant for millennia.',
                'poster_path' => '/1XS1oqL89opfnV6LRqs70AW3B.jpg',
                'backdrop_path' => '/suopoADq0k8YZr4dQXcU6pToj6s.jpg',
                'rating' => 8.4,
                'vote_count' => 21000,
                'runtime' => 60,
                'language' => 'en',
                'enrichment_status' => 'completed',
                'confidence_score' => 100,
                'is_published' => true,
                'is_featured' => false,
                'watch_count' => 4100,
                'genres' => [$action, $drama],
            ],
            [
                'tmdb_id' => 603,
                'imdb_id' => 'tt0133093',
                'type' => 'movie',
                'title' => 'The Matrix',
                'original_title' => 'The Matrix',
                'year' => 1999,
                'description' => 'A computer programmer discovers that reality as he knows it is a simulation created by machines, and joins a rebellion to break free.',
                'poster_path' => '/f89U3ADr1oiB1s9GkdPOEpXUk5H.jpg',
                'backdrop_path' => '/fNG7i7RGlMOvOkEa1gIRQzPnL.jpg',
                'rating' => 8.2,
                'vote_count' => 24000,
                'runtime' => 136,
                'language' => 'en',
                'enrichment_status' => 'completed',
                'confidence_score' => 100,
                'is_published' => true,
                'is_featured' => false,
                'watch_count' => 900,
                'genres' => [$action, $scifi],
            ],
        ];

        foreach ($contents as $data) {
            $genres = $data['genres'];
            unset($data['genres']);

            $content = Content::firstOrCreate(
                ['tmdb_id' => $data['tmdb_id']],
                $data
            );

            $genreIds = collect($genres)->filter()->pluck('id')->toArray();
            if ($genreIds) {
                $content->genres()->syncWithoutDetaching($genreIds);
            }
        }
    }
}
