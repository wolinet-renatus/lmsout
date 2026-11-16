<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * You tube playlist service — encapsulates domain business logic and transactional operations.
 *
 * @author Renatus Bernardo
 */
class YouTubePlaylistService
{
    /**
     * Extract playlist ID from a YouTube playlist or watch URL.
     */
    public function extractPlaylistId(string $url): ?string
    {
        if (preg_match('/[?&]list=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Fetch playlist video items (title, video_id, url, duration_minutes) from YouTube playlist URL.
     * Uses YouTube's public playlist page initial data (no API key required) with graceful fallback.
     *
     * @return array{playlist_id: ?string, title: string, videos: array<int, array<string, mixed>>, error: ?string}
     */
    public function fetchPlaylistVideos(string $url): array
    {
        $playlistId = $this->extractPlaylistId($url);
        if (!$playlistId) {
            return [
                'playlist_id' => null,
                'title' => '',
                'videos' => [],
                'error' => 'Invalid YouTube playlist URL. Expected a URL containing list=...',
            ];
        }

        $targetUrl = 'https://www.youtube.com/playlist?list=' . urlencode($playlistId);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept-Language' => 'en-US,en;q=0.9',
                ])
                ->get($targetUrl);

            if (!$response->successful()) {
                return [
                    'playlist_id' => $playlistId,
                    'title' => 'YouTube Playlist',
                    'videos' => [],
                    'error' => 'Unable to reach YouTube playlist (HTTP ' . $response->status() . ').',
                ];
            }

            $html = $response->body();
            $videos = [];
            $playlistTitle = 'Imported YouTube Playlist';

            if (preg_match('/var ytInitialData\s*=\s*(\{.*?\});\s*<\/script>/s', $html, $m)) {
                $json = json_decode($m[1], true);
                if (is_array($json)) {
                    $playlistTitle = $json['metadata']['playlistMetadataRenderer']['title'] ?? $playlistTitle;
                    $contents = $json['contents']['twoColumnBrowseResultsRenderer']['tabs'][0]['tabRenderer']['content']['sectionListRenderer']['contents'][0]['itemSectionRenderer']['contents'][0]['playlistVideoListRenderer']['contents'] ?? [];
                    foreach ($contents as $item) {
                        $renderer = $item['playlistVideoRenderer'] ?? null;
                        if (!$renderer || empty($renderer['videoId'])) {
                            continue;
                        }
                        $vid = $renderer['videoId'];
                        $title = $renderer['title']['runs'][0]['text'] ?? ($renderer['title']['simpleText'] ?? ('Video ' . (count($videos) + 1)));
                        $seconds = (int) ($renderer['lengthSeconds'] ?? 0);
                        $videos[] = [
                            'video_id' => $vid,
                            'title' => $title,
                            'video_url' => 'https://www.youtube.com/watch?v=' . $vid,
                            'duration_minutes' => max(1, (int) ceil($seconds / 60)),
                            'is_preview' => count($videos) === 0,
                        ];
                    }
                }
            }

            // Secondary regex fallback if ytInitialData structure differs
            if (empty($videos) && preg_match_all('/"playlistVideoRenderer":\{"videoId":"([a-zA-Z0-9_-]{6,20})".*?"title":\{"runs":\[\{"text":"([^"]+)"/s', $html, $matches, PREG_SET_ORDER)) {
                $seen = [];
                foreach ($matches as $match) {
                    $vid = $match[1];
                    if (isset($seen[$vid])) {
                        continue;
                    }
                    $seen[$vid] = true;
                    $videos[] = [
                        'video_id' => $vid,
                        'title' => html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'),
                        'video_url' => 'https://www.youtube.com/watch?v=' . $vid,
                        'duration_minutes' => 10,
                        'is_preview' => count($videos) === 0,
                    ];
                }
            }

            if (empty($videos)) {
                return [
                    'playlist_id' => $playlistId,
                    'title' => $playlistTitle,
                    'videos' => [],
                    'error' => 'No public videos found in this playlist. Make sure the playlist is Public or Unlisted.',
                ];
            }

            return [
                'playlist_id' => $playlistId,
                'title' => $playlistTitle,
                'videos' => $videos,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('YouTubePlaylistService error: ' . $e->getMessage());
            return [
                'playlist_id' => $playlistId,
                'title' => 'YouTube Playlist',
                'videos' => [],
                'error' => 'Could not fetch playlist: ' . $e->getMessage(),
            ];
        }
    }
}

