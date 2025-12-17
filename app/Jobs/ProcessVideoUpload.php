<?php

namespace App\Jobs;

use App\Models\Post;
use App\Services\VideoProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVideoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 3600; // 1 hour
    public $tries = 3;
    
    protected $post;
    protected $videoPath;
    
    public function __construct(Post $post, string $videoPath)
    {
        $this->post = $post;
        $this->videoPath = $videoPath;
    }
    
    public function handle(VideoProcessingService $service)
    {
        try {
            if ($this->post->type === 'reel') {
                $service->processReel($this->post, $this->videoPath);
            } else {
                $service->processVideo($this->post, $this->videoPath);
            }
            
            // Notify user that video is processed
            $this->post->user->notify(new \App\Notifications\VideoProcessed($this->post));
            
        } catch (\Exception $e) {
            \Log::error('Video processing job failed', [
                'post_id' => $this->post->id,
                'error' => $e->getMessage(),
            ]);
            
            $this->fail($e);
        }
    }
}