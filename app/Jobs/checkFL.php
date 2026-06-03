<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;


class checkFL implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $client = new Client([
            'proxy' => 'socks5://127.0.0.1:10808',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0',
            ]
        ]);

        $response = $client->get('https://www.fl.ru/projects/');

        $html = $response->getBody()->getContents();

        $crawler = new Crawler($html);

        $projects = $crawler->filter('.b-post')->each(function ($node) {
            return [
                'title' => $node->filter('.b-post__title')->text(),
                'url' => $node->filter('a')->attr('href'),
                'description' => $node->filter('.b-post__txt')->text(),
                'platform' => 'fl'
            ];
        });

        $new_projects = [];

        foreach ($projects as $project) {
            $project_id = explode("/",$project['url'])[2];
            $dbproject = Project::where('project_id', $project_id)->first();

            if(empty($dbproject)) {
                $project['url'] = "https://fl.ru/" . $project['url'];
                $new_projects[] = $project;

                Project::create([
                    'project_id' => $project_id,
                    'platform' => 'fl'
                ]);
            }
        }

        baleBotJob::dispatch($new_projects);
    }
}
