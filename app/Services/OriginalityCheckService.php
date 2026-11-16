<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Originality check service — encapsulates domain business logic and transactional operations.
 *
 * @author Renatus Bernardo
 */
class OriginalityCheckService
{
    /**
     * Compare a submission against every other submission for the same assignment
     * using 4-word shingle Jaccard + containment similarity, plus optional external webhook/provider.
     */
    public function checkSubmission(AssignmentSubmission $submission, ?string $externalProvider = null): array
    {
        $submission->loadMissing('assignment', 'user');
        $targetText = $this->normalizeText((string) $submission->submission_text);
        $targetShingles = $this->buildShingles($targetText, 4);

        $others = AssignmentSubmission::with('user')
            ->where('assignment_id', $submission->assignment_id)
            ->where('id', '!=', $submission->id)
            ->get();

        $matches = [];
        $maxScore = 0.0;

        foreach ($others as $other) {
            $otherText = $this->normalizeText((string) $other->submission_text);
            if ($otherText === '' || $targetText === '') {
                continue;
            }

            $otherShingles = $this->buildShingles($otherText, 4);
            $score = $this->computeSimilarity($targetText, $otherText, $targetShingles, $otherShingles);

            if ($score > 0) {
                $matches[] = [
                    'submission_id' => $other->id,
                    'student_name' => $other->user->name ?? ('Student #' . $other->user_id),
                    'similarity_percent' => round($score, 2),
                    'submitted_at' => optional($other->submitted_at)->toDateTimeString(),
                ];
                if ($score > $maxScore) {
                    $maxScore = $score;
                }
            }
        }

        usort($matches, fn ($a, $b) => $b['similarity_percent'] <=> $a['similarity_percent']);
        $matches = array_slice($matches, 0, 10);

        $externalResult = null;
        if ($externalProvider && $targetText !== '') {
            $externalResult = $this->runExternalCheck($targetText, $externalProvider);
            if (isset($externalResult['similarity_percent']) && $externalResult['similarity_percent'] > $maxScore) {
                $maxScore = (float) $externalResult['similarity_percent'];
            }
        }

        $report = [
            'checked_at' => now()->toDateTimeString(),
            'compared_against_count' => $others->count(),
            'max_similarity_percent' => round($maxScore, 2),
            'peer_matches' => $matches,
            'external_provider' => $externalProvider,
            'external_result' => $externalResult,
        ];

        $submission->update([
            'similarity_score' => round($maxScore, 2),
            'similarity_report' => $report,
            'external_similarity_provider' => $externalProvider,
        ]);

        return $report;
    }

    /**
     * Normalize text.
     */
    protected function normalizeText(string $text): string
    {
        $plain = strip_tags($text);
        $plain = mb_strtolower($plain);
        $plain = preg_replace('/[^a-z0-9\s]/u', ' ', $plain);
        return trim(preg_replace('/\s+/', ' ', $plain));
    }

    /**
     * Build shingles.
     */
    protected function buildShingles(string $text, int $n = 4): array
    {
        if ($text === '') {
            return [];
        }
        $words = explode(' ', $text);
        if (count($words) <= $n) {
            return [implode(' ', $words) => true];
        }
        $shingles = [];
        $limit = count($words) - $n;
        for ($i = 0; $i <= $limit; $i++) {
            $shingles[implode(' ', array_slice($words, $i, $n))] = true;
        }
        return $shingles;
    }

    /**
     * Compute similarity.
     */
    protected function computeSimilarity(string $a, string $b, array $shinglesA, array $shinglesB): float
    {
        if ($a === $b) {
            return 100.0;
        }
        if (empty($shinglesA) || empty($shinglesB)) {
            similar_text($a, $b, $pct);
            return round((float) $pct, 2);
        }
        $intersection = count(array_intersect_key($shinglesA, $shinglesB));
        if ($intersection === 0) {
            return 0.0;
        }
        $union = count($shinglesA) + count($shinglesB) - $intersection;
        $jaccard = ($union > 0) ? ($intersection / $union) * 100 : 0.0;
        $containment = ($intersection / min(count($shinglesA), count($shinglesB))) * 100;
        return round(max($jaccard, $containment * 0.85), 2);
    }

    /**
     * Run external check.
     */
    protected function runExternalCheck(string $text, string $provider): array
    {
        $endpoint = config('services.originality.endpoint');
        $apiKey = config('services.originality.key');

        if ($endpoint && $apiKey) {
            try {
                $resp = Http::timeout(15)
                    ->withToken($apiKey)
                    ->post($endpoint, [
                        'provider' => $provider,
                        'text' => mb_substr($text, 0, 12000),
                    ]);
                if ($resp->successful()) {
                    $data = $resp->json();
                    return [
                        'status' => 'completed',
                        'provider' => $provider,
                        'similarity_percent' => (float) ($data['similarity_percent'] ?? $data['score'] ?? 0),
                        'sources' => $data['sources'] ?? [],
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('External originality provider failed: ' . $e->getMessage());
            }
        }

        return [
            'status' => 'not_configured',
            'provider' => $provider,
            'message' => 'External provider endpoint/API key not configured in services.originality; peer comparison completed locally.',
        ];
    }
}

