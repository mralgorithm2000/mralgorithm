<?php

namespace App\Jobs;

use App\Services\BaleBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class baleBotJob implements ShouldQueue
{
    use Queueable;

    public $projects;
    /**
     * Create a new job instance.
     */
    public function __construct($projects)
    {
        $this->projects = $projects;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $bot = new BaleBotService();
        foreach ($this->projects as $project) {
            $message = "✅ #new_project #fl
✅ Title: " . $project['title'] . "
✅ Description: " . $project['description'] . "
✅ Url: " . $project['url'] . "
✅ Platform: " . $project['platform'];
            $bot->send($message);
        }

    }
}
