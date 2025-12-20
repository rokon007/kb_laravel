<?php

namespace App\Services;

use App\Models\Post;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class VideoProcessingService
{
    protected $ffmpeg;
    
    public function __construct()
    {
        $this->ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => env('FFMPEG_BINARY', '/usr/bin/ffmpeg'),
            'ffprobe.binaries' => env('FFPROBE_BINARY', '/usr/bin/ffprobe'),
            'timeout'          => 3600,
            'ffmpeg.threads'   => 12,
        ]);
    }
    
    /**
     * Process uploaded video
     */
    public function processVideo(Post $post, $videoFile)
    {
        try {
            // Generate unique filename
            $filename = uniqid('video_') . '.mp4';
            $path = "videos/{$post->user_id}/{$filename}";
            
            // Store original video temporarily
            $tempPath = $videoFile->store('temp');
            $fullPath = Storage::path($tempPath);
            
            // Open video
            $video = $this->ffmpeg->open($fullPath);
            
            // Get video duration
            $duration = $this->getVideoDuration($fullPath);
            
            // Compress video
            $format = new X264('aac');
            $format->setKiloBitrate(1000)
                   ->setAudioKiloBitrate(128);
            
            $outputPath = Storage::path($path);
            $video->save($format, $outputPath);
            
            // Generate thumbnail
            $thumbnailPath = $this->generateThumbnail($outputPath, $post);
            
            // Update post
            $post->update([
                'media_url' => $path,
                'thumbnail_url' => $thumbnailPath,
                'video_duration' => $duration,
            ]);
            
            // Delete temp file
            Storage::delete($tempPath);
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Video processing failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process reel (vertical video)
     */
    public function processReel(Post $post, $videoFile)
    {
        try {
            $filename = uniqid('reel_') . '.mp4';
            $path = "reels/{$post->user_id}/{$filename}";
            
            $tempPath = $videoFile->store('temp');
            $fullPath = Storage::path($tempPath);
            
            $video = $this->ffmpeg->open($fullPath);
            $duration = $this->getVideoDuration($fullPath);
            
            // Reels are limited to 60 seconds
            if ($duration > 60) {
                throw new \Exception('Reel duration cannot exceed 60 seconds');
            }
            
            // Compress for mobile
            $format = new X264('aac');
            $format->setKiloBitrate(800)
                   ->setAudioKiloBitrate(96);
            
            $outputPath = Storage::path($path);
            $video->save($format, $outputPath);
            
            // Generate thumbnail
            $thumbnailPath = $this->generateThumbnail($outputPath, $post);
            
            $post->update([
                'media_url' => $path,
                'thumbnail_url' => $thumbnailPath,
                'video_duration' => $duration,
            ]);
            
            Storage::delete($tempPath);
            
            return true;
            
        } catch (\Exception $e) {
            \Log::error('Reel processing failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate video thumbnail
     */
    protected function generateThumbnail($videoPath, Post $post)
    {
        try {
            $video = $this->ffmpeg->open($videoPath);
            
            $filename = uniqid('thumb_') . '.jpg';
            $thumbnailPath = "thumbnails/{$post->user_id}/{$filename}";
            $fullThumbnailPath = Storage::path($thumbnailPath);
            
            // Extract frame at 2 seconds
            $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(2))
                  ->save($fullThumbnailPath);
            
            // Resize thumbnail
            Image::make($fullThumbnailPath)
                 ->fit(640, 360)
                 ->save($fullThumbnailPath, 80);
            
            return $thumbnailPath;
            
        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get video duration
     */
    protected function getVideoDuration($videoPath)
    {
        $ffprobe = \FFMpeg\FFProbe::create([
            'ffprobe.binaries' => env('FFPROBE_BINARY', '/usr/bin/ffprobe'),
        ]);
        
        return $ffprobe->format($videoPath)->get('duration');
    }
    
    /**
     * Get video streaming URL
     */
    public function getStreamingUrl(Post $post)
    {
        // For local storage
        if (config('filesystems.default') === 'local') {
            return Storage::url($post->media_url);
        }
        
        // For S3/Spaces
        return Storage::disk('s3')->temporaryUrl(
            $post->media_url,
            now()->addHours(1)
        );
    }
}