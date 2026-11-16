<?php

namespace App\Services;

use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gemini service — encapsulates domain business logic and transactional operations.
 *
 * @author Renatus Bernardo
 */
class GeminiService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    protected $settings;

    /**
     * Initialize dependencies for this class.
     */
    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
        $config = $settings->get('ai.config', []);
        $this->apiKey = $config['gemini_api_key'] ?? null;
        $this->model = $config['gemini_model'] ?? 'gemini-2.5-flash';
    }

    /**
     * Generate content using Gemini API
     *
     * @param string $prompt
     * @param array $history
     * @return string|null
     */
    public function generateContent(string $prompt, array $history = [])
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is not configured.');
            return 'AI configuration is missing. Please contact the administrator.';
        }

        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        $contents = [];

        // Format history for Gemini API
        foreach ($history as $message) {
            $contents[] = [
                'role' => $message->is_ai ? 'model' : 'user',
                'parts' => [
                    ['text' => $message->message]
                ]
            ];
        }

        // Add current prompt
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'topK' => 40,
                            'topP' => 0.95,
                            'maxOutputTokens' => 1024,
                        ]
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated.';
            }

            Log::error('Gemini API Error: ' . $response->body());
            return 'Sorry, I encountered an error providing a response. Please try again later.';

        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return 'An error occurred while connecting to the AI service.';
        }
    }

    /**
     * Request structured JSON from Gemini API, falling back to a deterministic draft when unconfigured or offline.
     */
    public function generateJson(string $prompt, callable $fallbackBuilder, int $maxOutputTokens = 4096): array
    {
        if (empty($this->apiKey)) {
            return ['source' => 'template', 'data' => $fallbackBuilder()];
        }

        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        try {
            $response = Http::timeout(45)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [['text' => $prompt]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.5,
                    'maxOutputTokens' => $maxOutputTokens,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $raw = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $cleaned = trim(preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $raw));
                $decoded = json_decode($cleaned, true);
                if (is_array($decoded)) {
                    return ['source' => 'gemini', 'data' => $decoded];
                }
            }
            Log::warning('Gemini JSON generation fallback used: ' . $response->body());
        } catch (\Exception $e) {
            Log::warning('Gemini JSON generation exception: ' . $e->getMessage());
        }

        return ['source' => 'template', 'data' => $fallbackBuilder()];
    }

    /**
     * AI Course Builder: one prompt drafts title, sales copy, outcomes, requirements, SEO tags, and full curriculum.
     */
    public function draftFullCourse(string $prompt, ?string $documentText = null): array
    {
        $topic = trim($prompt) ?: 'Modern Practical Mastery';
        $docSnippet = $documentText ? "\n\nReference document excerpt:\n" . mb_substr(trim($documentText), 0, 6000) : '';

        $aiPrompt = "You are an expert curriculum architect. Based on the following request{$docSnippet}, return a JSON object with keys: "
            . "title (string), subtitle (string), description (string, 2-3 paragraphs HTML-safe sales copy), "
            . "outcomes (array of 5 strings), requirements (array of 3 strings), "
            . "seo_title (string), seo_description (string), seo_tags (comma-separated string), "
            . "marketing_copy (object with email and social strings), "
            . "topics (array of 3-4 objects, each with title, description, lessons: array of objects with title, duration_minutes, is_preview (bool), content_text (string)). "
            . "Request: {$topic}";

        return $this->generateJson($aiPrompt, function () use ($topic, $documentText) {
            $cleanTitle = ucwords(mb_substr($topic, 0, 72));
            $excerpt = $documentText ? ' Built from your uploaded reference material.' : '';
            return [
                'title' => $cleanTitle,
                'subtitle' => "A structured, hands-on program on {$cleanTitle} from fundamentals to real-world execution.",
                'description' => "<p>Master <strong>{$cleanTitle}</strong> with step-by-step lessons, guided practice, and measurable checkpoints.{$excerpt}</p><p>Designed for learners who want clear frameworks, practical templates, and immediate application.</p>",
                'outcomes' => [
                    "Explain core principles and architecture behind {$cleanTitle}",
                    'Apply structured workflows to solve real-world scenarios',
                    'Evaluate trade-offs, troubleshoot bottlenecks, and verify quality',
                    'Build and present a complete capstone deliverable',
                ],
                'requirements' => [
                    'Basic computer literacy and willingness to practice',
                    'No prior advanced experience required; fundamentals are covered in Module 1',
                ],
                'seo_title' => "{$cleanTitle} — Complete Course & Practical Guide",
                'seo_description' => "Learn {$cleanTitle} with structured modules, quizzes, and practical assignments.",
                'seo_tags' => strtolower(implode(', ', array_slice(preg_split('/\s+/', preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanTitle)), 0, 6))) . ', online course, certification',
                'marketing_copy' => [
                    'email' => "Subject: New Course Open — {$cleanTitle}\n\nReady to master {$cleanTitle}? Enroll today for step-by-step modules, hands-on assignments, and verified progress.",
                    'social' => "Launch alert: {$cleanTitle} is now live with interactive lessons, quizzes, and practical projects. Start Module 1 free preview today!",
                ],
                'topics' => [
                    [
                        'title' => 'Module 1: Foundations & Core Mental Models',
                        'description' => 'Establish terminology, setup, and core principles.',
                        'lessons' => [
                            ['title' => "Welcome & Roadmap for {$cleanTitle}", 'duration_minutes' => 8, 'is_preview' => true, 'content_text' => '<p>Course overview, learning milestones, and how to get the most from each module.</p>'],
                            ['title' => 'Core Concepts & Key Terminology', 'duration_minutes' => 14, 'is_preview' => false, 'content_text' => '<p>Essential definitions and foundational patterns.</p>'],
                        ],
                    ],
                    [
                        'title' => 'Module 2: Practical Implementation',
                        'description' => 'Step-by-step workflows and guided exercises.',
                        'lessons' => [
                            ['title' => 'Guided Walkthrough & Setup', 'duration_minutes' => 18, 'is_preview' => false, 'content_text' => '<p>Hands-on implementation steps with checkpoints.</p>'],
                            ['title' => 'Common Pitfalls & Best Practices', 'duration_minutes' => 15, 'is_preview' => false, 'content_text' => '<p>How to avoid frequent mistakes and validate results.</p>'],
                        ],
                    ],
                    [
                        'title' => 'Module 3: Mastery & Capstone Review',
                        'description' => 'Apply everything in a realistic scenario.',
                        'lessons' => [
                            ['title' => 'Capstone Brief & Evaluation Rubric', 'duration_minutes' => 12, 'is_preview' => false, 'content_text' => '<p>Capstone instructions and grading criteria.</p>'],
                        ],
                    ],
                ],
            ];
        });
    }

    /**
     * AI Studio: generate reviewable artifacts for curriculum, lessons, quizzes, assignments, FAQs, or marketing copy.
     */
    public function generateStudioContent(string $mode, string $prompt, array $context = []): array
    {
        $topic = trim($prompt) ?: ($context['course_title'] ?? 'Course Topic');
        $docText = $context['document_text'] ?? null;
        $docPart = $docText ? "\n\nUploaded Document Content:\n" . mb_substr($docText, 0, 6000) : '';

        $instructions = match ($mode) {
            'outline' => 'Return JSON { "topics": [ { "title": string, "description": string, "lessons": [ { "title": string, "duration_minutes": int, "is_preview": bool } ] } ] }.',
            'lesson_text' => 'Return JSON { "title": string, "content_text": string (rich HTML), "key_takeaways": [string] }.',
            'quiz_questions' => 'Return JSON { "questions": [ { "question_text": string, "type": "multiple_choice", "options": [string, string, string, string], "correct_index": int, "explanation": string, "difficulty": "easy|medium|hard" } ] } with 5 questions.',
            'assignment_rubric' => 'Return JSON { "title": string, "description": string, "accepted_submission_types": ["file","text","url"], "max_points": 100, "rubric": [ { "criterion": string, "points": int, "description": string } ] }.',
            'faqs' => 'Return JSON { "faqs": [ { "question": string, "answer": string } ] } with 5 items.',
            'marketing_copy' => 'Return JSON { "titles": [string, string, string], "seo_title": string, "seo_description": string, "seo_tags": string, "email": string, "social": string }.',
            default => 'Return JSON { "text": string }.',
        };

        $fullPrompt = "AI Studio Mode: {$mode}. {$instructions}\nTopic/Prompt: {$topic}{$docPart}";

        return $this->generateJson($fullPrompt, function () use ($mode, $topic) {
            return match ($mode) {
                'outline' => [
                    'topics' => [
                        ['title' => "Section 1: Introduction to {$topic}", 'description' => 'Core foundations', 'lessons' => [
                            ['title' => "Overview of {$topic}", 'duration_minutes' => 10, 'is_preview' => true],
                            ['title' => 'Key Principles & Setup', 'duration_minutes' => 15, 'is_preview' => false],
                        ]],
                        ['title' => "Section 2: Applied {$topic}", 'description' => 'Hands-on execution', 'lessons' => [
                            ['title' => 'Practical Case Study', 'duration_minutes' => 20, 'is_preview' => false],
                            ['title' => 'Review & Next Steps', 'duration_minutes' => 12, 'is_preview' => false],
                        ]],
                    ],
                ],
                'lesson_text' => [
                    'title' => $topic,
                    'content_text' => "<h3>{$topic}</h3><p>In this lesson, we break down the essential concepts behind <strong>{$topic}</strong> and walk through practical steps you can apply immediately.</p><ul><li>Understand the primary objective and constraints.</li><li>Follow the repeatable workflow checklist.</li><li>Verify your output against quality benchmarks.</li></ul>",
                    'key_takeaways' => [
                        "Core mechanism of {$topic}",
                        'Step-by-step execution checklist',
                        'Verification and troubleshooting tips',
                    ],
                ],
                'quiz_questions' => [
                    'questions' => [
                        [
                            'question_text' => "Which statement best describes the primary goal of {$topic}?",
                            'type' => 'multiple_choice',
                            'options' => [
                                "Establishing a repeatable and verifiable workflow for {$topic}",
                                'Skipping validation to save initial setup time',
                                'Avoiding structured documentation',
                                'Relying solely on manual guesswork',
                            ],
                            'correct_index' => 0,
                            'explanation' => 'A structured, verifiable workflow ensures consistent and reliable outcomes.',
                            'difficulty' => 'easy',
                        ],
                        [
                            'question_text' => "When troubleshooting {$topic}, what should be verified first?",
                            'type' => 'multiple_choice',
                            'options' => [
                                'Input assumptions and baseline configuration',
                                'Deleting all prior logs',
                                'Changing every parameter simultaneously',
                                'Ignoring edge-case constraints',
                            ],
                            'correct_index' => 0,
                            'explanation' => 'Checking input assumptions and baseline settings isolates root causes quickly.',
                            'difficulty' => 'medium',
                        ],
                        [
                            'question_text' => "What is a recommended best practice before finalizing a {$topic} deliverable?",
                            'type' => 'multiple_choice',
                            'options' => [
                                'Run peer or rubric-based verification checks',
                                'Skip testing on realistic inputs',
                                'Hardcode environment-specific values',
                                'Remove version history',
                            ],
                            'correct_index' => 0,
                            'explanation' => 'Rubric-based verification catches gaps before release.',
                            'difficulty' => 'medium',
                        ],
                    ],
                ],
                'assignment_rubric' => [
                    'title' => "Practical Assignment: {$topic}",
                    'description' => "Apply what you learned in {$topic} by submitting a concise solution report and supporting artifact.",
                    'accepted_submission_types' => ['file', 'text', 'url'],
                    'max_points' => 100,
                    'rubric' => [
                        ['criterion' => 'Problem Understanding & Scope', 'points' => 25, 'description' => 'Clearly defines objectives, constraints, and assumptions.'],
                        ['criterion' => 'Technical Execution', 'points' => 45, 'description' => 'Applies concepts accurately with clean structure.'],
                        ['criterion' => 'Verification & Clarity', 'points' => 30, 'description' => 'Includes evidence of testing and clear explanation.'],
                    ],
                ],
                'faqs' => [
                    'faqs' => [
                        ['question' => "Who is this {$topic} course designed for?", 'answer' => 'Learners seeking practical, step-by-step mastery from foundational concepts to real-world execution.'],
                        ['question' => 'Do I get lifetime access and updates?', 'answer' => 'Yes, once enrolled you retain full access to all lessons, quizzes, and future updates.'],
                        ['question' => 'Are quizzes and assignments included?', 'answer' => 'Yes, every module includes knowledge checks and practical exercises to reinforce learning.'],
                    ],
                ],
                'marketing_copy' => [
                    'titles' => [
                        "{$topic}: Complete Practical Bootcamp",
                        "Mastering {$topic} From First Principles",
                        "Applied {$topic} for Real-World Results",
                    ],
                    'seo_title' => "{$topic} | Online Course & Certification",
                    'seo_description' => "Build real-world skills in {$topic} with structured video lessons, quizzes, and graded assignments.",
                    'seo_tags' => strtolower($topic) . ', online learning, practical course',
                    'email' => "Hi there,\n\nEnrollment is open for {$topic}. Inside you will find structured modules, downloadable templates, and feedback-driven assignments.\n\nSee you inside!",
                    'social' => "Level up your skills with {$topic} — hands-on lessons, interactive quizzes, and real-world projects. Enroll now!",
                ],
                default => ['text' => "Generated content for {$topic}."],
            };
        });
    }
}
