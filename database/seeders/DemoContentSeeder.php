<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\Bundle;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLanguage;
use App\Models\CourseTopic;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LiveClass;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Review;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo content seeder — seeds baseline institutional, RBAC, or reference records.
 *
 * @author Renatus Bernardo
 */
class DemoContentSeeder extends Seeder
{
    /**
     * Execute the database seeder.
     */
    public function run(): void
    {
        // 1. Course Categories (6 rich categories)
        $categoriesData = [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'icon' => 'fa-solid fa-laptop-code',
                'description' => 'Master modern frontend, backend, and full-stack web engineering with hands-on projects.',
                'display_order' => 1,
            ],
            [
                'name' => 'Data Science & AI',
                'slug' => 'data-science-ai',
                'icon' => 'fa-solid fa-microchip',
                'description' => 'Build machine learning models, LLM applications, and data pipelines from scratch.',
                'display_order' => 2,
            ],
            [
                'name' => 'UI/UX & Product Design',
                'slug' => 'ui-ux-product-design',
                'icon' => 'fa-solid fa-palette',
                'description' => 'Design intuitive digital products, design systems, and interactive prototypes.',
                'display_order' => 3,
            ],
            [
                'name' => 'Digital Marketing',
                'slug' => 'digital-marketing',
                'icon' => 'fa-solid fa-chart-line',
                'description' => 'Scale brands with performance marketing, SEO, analytics, and conversion optimization.',
                'display_order' => 4,
            ],
            [
                'name' => 'Business & Strategy',
                'slug' => 'business-strategy',
                'icon' => 'fa-solid fa-briefcase',
                'description' => 'Lead high-performing teams, manage agile products, and scale modern enterprises.',
                'display_order' => 5,
            ],
            [
                'name' => 'Cloud & Cybersecurity',
                'slug' => 'cloud-cybersecurity',
                'icon' => 'fa-solid fa-shield-halved',
                'description' => 'Architect resilient cloud infrastructure, Kubernetes clusters, and secure systems.',
                'display_order' => 6,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = CourseCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['is_active' => true])
            );
        }

        $language = CourseLanguage::firstOrCreate(
            ['code' => 'en'],
            ['name' => 'English', 'is_active' => true]
        );

        // 2. Instructors (4 approved expert instructors)
        $instructorsData = [
            [
                'email' => 'instructor@lsmout.ac.tz',
                'legacy_emails' => ['instructor@out.ac.tz', 'instructor@edux.com', 'instructor@eduex.com'],
                'name' => 'Prof. Daniel Carter',
                'specialization' => 'Principal Full-Stack & Cloud Architect',
                'bio' => '15+ years building distributed web applications and mentoring over 45,000 engineers worldwide.',
                'profile_photo' => 'assets/front/img/home-1/team/team-01.jpg',
                'years_experience' => 15,
            ],
            [
                'email' => 'sarah.jenkins@lsmout.ac.tz',
                'legacy_emails' => ['sarah.jenkins@out.ac.tz'],
                'name' => 'Dr. Sarah Jenkins',
                'specialization' => 'Lead AI Scientist & ML Researcher',
                'bio' => 'PhD in Machine Learning with deep industry experience deploying generative AI and computer vision systems.',
                'profile_photo' => 'assets/front/img/home-1/team/team-02.jpg',
                'years_experience' => 12,
            ],
            [
                'email' => 'marcus.vance@lsmout.ac.tz',
                'legacy_emails' => ['marcus.vance@out.ac.tz'],
                'name' => 'Marcus Vance',
                'specialization' => 'Staff Product Designer & UX Director',
                'bio' => 'Design systems architect who has led product design teams at top Silicon Valley tech companies.',
                'profile_photo' => 'assets/front/img/home-1/team/team-03.jpg',
                'years_experience' => 10,
            ],
            [
                'email' => 'elena.rostova@lsmout.ac.tz',
                'legacy_emails' => ['elena.rostova@out.ac.tz'],
                'name' => 'Elena Rostova',
                'specialization' => 'VP of Growth & Product Strategy',
                'bio' => 'Growth strategist helping SaaS and EdTech startups scale from seed stage to global market leaders.',
                'profile_photo' => 'assets/front/img/home-1/team/team-04.jpg',
                'years_experience' => 11,
            ],
        ];

        $instructors = [];
        foreach ($instructorsData as $inst) {
            if (! User::where('email', $inst['email'])->exists() && ! empty($inst['legacy_emails'])) {
                $legacyUser = User::whereIn('email', $inst['legacy_emails'])->first();
                if ($legacyUser) {
                    $legacyUser->update(['email' => $inst['email']]);
                }
            }
            $user = User::updateOrCreate(
                ['email' => $inst['email']],
                [
                    'name' => $inst['name'],
                    'password' => Hash::make('password'),
                    'role' => 'instructor',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'specialization' => $inst['specialization'],
                    'bio' => $inst['bio'],
                    'profile_photo' => $inst['profile_photo'],
                    'years_experience' => $inst['years_experience'],
                ]
            );
            if (method_exists($user, 'syncRoles')) {
                try {
                    $user->syncRoles(['instructor']);
                } catch (\Throwable $e) {
                }
            }
            $instructors[] = $user;
        }

        // 3. Students (4 active learners)
        $studentsData = [
            [
                'email' => 'student@lsmout.ac.tz',
                'legacy_emails' => ['student@out.ac.tz', 'student@edux.com', 'student@eduex.com'],
                'name' => 'Alex Rivera',
                'profile_photo' => 'assets/front/img/home-1/courses/client-01.png',
            ],
            [
                'email' => 'priya.patel@lsmout.ac.tz',
                'legacy_emails' => ['priya.patel@out.ac.tz'],
                'name' => 'Priya Patel',
                'profile_photo' => 'assets/front/img/home-1/courses/client-02.png',
            ],
            [
                'email' => 'liam.oconnor@lsmout.ac.tz',
                'legacy_emails' => ['liam.oconnor@out.ac.tz'],
                'name' => 'Liam O\'Connor',
                'profile_photo' => 'assets/front/img/home-1/courses/client-03.png',
            ],
            [
                'email' => 'sofia.chen@lsmout.ac.tz',
                'legacy_emails' => ['sofia.chen@out.ac.tz'],
                'name' => 'Sofia Chen',
                'profile_photo' => 'assets/front/img/home-1/courses/client-04.png',
            ],
        ];

        $students = [];
        foreach ($studentsData as $st) {
            if (! User::where('email', $st['email'])->exists() && ! empty($st['legacy_emails'])) {
                $legacyUser = User::whereIn('email', $st['legacy_emails'])->first();
                if ($legacyUser) {
                    $legacyUser->update(['email' => $st['email']]);
                }
            }
            $user = User::updateOrCreate(
                ['email' => $st['email']],
                [
                    'name' => $st['name'],
                    'password' => Hash::make('password'),
                    'role' => 'student',
                    'is_approved' => true,
                    'email_verified_at' => now(),
                    'profile_photo' => $st['profile_photo'],
                ]
            );
            if (method_exists($user, 'syncRoles')) {
                try {
                    $user->syncRoles(['student']);
                } catch (\Throwable $e) {
                }
            }
            $students[] = $user;
        }

        // 4. Courses (6 flagship published courses)
        $templateIds = CertificateTemplate::pluck('id')->toArray();
        $defaultTemplateId = $templateIds[0] ?? null;

        $coursesData = [
            [
                'title' => 'Complete Full-Stack Web Development Bootcamp (Laravel & React)',
                'category_slug' => 'web-development',
                'instructor' => $instructors[0],
                'featured_image' => 'assets/front/img/home-1/courses/courses-01.jpg',
                'difficulty' => 'intermediate',
                'pricing_model' => 'paid',
                'regular_price' => 129.00,
                'sale_price' => 79.00,
                'tags' => 'Laravel,React,Full-Stack,PHP,JavaScript,API',
                'description' => '<p>Master modern full-stack engineering by building production-ready web applications with Laravel, React, RESTful APIs, and MySQL. Designed for aspiring software engineers and working developers.</p>',
                'requirements' => "Basic HTML, CSS, and JavaScript familiarity\nA computer with macOS, Windows, or Linux",
                'objectives' => "Architect scalable Laravel backends and REST APIs\nBuild interactive React frontends with state management\nDeploy production full-stack applications with CI/CD",
            ],
            [
                'title' => 'Applied Generative AI, LLMs & Machine Learning Engineering',
                'category_slug' => 'data-science-ai',
                'instructor' => $instructors[1],
                'featured_image' => 'assets/front/img/home-1/courses/courses-02.jpg',
                'difficulty' => 'advanced',
                'pricing_model' => 'paid',
                'regular_price' => 149.00,
                'sale_price' => 89.00,
                'tags' => 'AI,Machine Learning,LLM,Python,Data Science,RAG',
                'description' => '<p>Learn how to build, fine-tune, and deploy Large Language Models, Retrieval-Augmented Generation (RAG) pipelines, and intelligent AI agents in Python.</p>',
                'requirements' => "Intermediate Python programming\nFoundational linear algebra and probability concepts",
                'objectives' => "Build production RAG pipelines and vector search systems\nFine-tune open-weights LLMs for domain tasks\nEvaluate and monitor AI model performance",
            ],
            [
                'title' => 'Mastering UI/UX Product Design & Figma Design Systems',
                'category_slug' => 'ui-ux-product-design',
                'instructor' => $instructors[2],
                'featured_image' => 'assets/front/img/home-1/courses/courses-03.jpg',
                'difficulty' => 'beginner',
                'pricing_model' => 'paid',
                'regular_price' => 99.00,
                'sale_price' => 59.00,
                'tags' => 'UI/UX,Figma,Design Systems,Product Design,Prototyping',
                'description' => '<p>Go from wireframes to high-fidelity interactive prototypes and enterprise multi-brand design systems in Figma.</p>',
                'requirements' => "No prior design experience required\nFree Figma account",
                'objectives' => "Conduct user research and usability testing\nBuild tokenized component libraries in Figma\nHand off production specs to engineering teams",
            ],
            [
                'title' => 'Growth Marketing, SEO & Conversion Rate Optimization Masterclass',
                'category_slug' => 'digital-marketing',
                'instructor' => $instructors[3],
                'featured_image' => 'assets/front/img/home-1/courses/courses-04.jpg',
                'difficulty' => 'intermediate',
                'pricing_model' => 'paid',
                'regular_price' => 89.00,
                'sale_price' => 49.00,
                'tags' => 'Digital Marketing,SEO,Growth,Analytics,CRO',
                'description' => '<p>Build repeatable customer acquisition engines combining technical SEO, paid acquisition funnels, and data-driven A/B testing.</p>',
                'requirements' => "Basic familiarity with websites and online marketing",
                'objectives' => "Execute technical SEO audits and content strategies\nDesign high-converting landing pages and funnels\nMeasure CAC, LTV, and multi-touch attribution",
            ],
            [
                'title' => 'Agile Product Management & Tech Leadership Essentials',
                'category_slug' => 'business-strategy',
                'instructor' => $instructors[3],
                'featured_image' => 'assets/front/img/home-1/courses/courses-05.jpg',
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0.00,
                'sale_price' => 0.00,
                'tags' => 'Product Management,Agile,Leadership,Business,Strategy',
                'description' => '<p>A complete free foundational program on product discovery, roadmap prioritization, OKRs, and leading cross-functional engineering and design squads.</p>',
                'requirements' => "Open to all aspiring and current product managers",
                'objectives' => "Write clear PRDs and user stories\nPrioritize product roadmaps with RICE and Kano frameworks\nRun effective sprint planning and product launches",
            ],
            [
                'title' => 'Cloud Native DevOps, Docker, Kubernetes & AWS Architecture',
                'category_slug' => 'cloud-cybersecurity',
                'instructor' => $instructors[0],
                'featured_image' => 'assets/front/img/home-1/courses/courses-06.jpg',
                'difficulty' => 'advanced',
                'pricing_model' => 'paid',
                'regular_price' => 139.00,
                'sale_price' => 79.00,
                'tags' => 'Cloud,AWS,Docker,Kubernetes,DevOps,Security',
                'description' => '<p>Design, containerize, and orchestrate highly available cloud infrastructure using Docker, Kubernetes, Terraform, and AWS security best practices.</p>',
                'requirements' => "Linux command line basics and web application familiarity",
                'objectives' => "Containerize microservices with Docker\nDeploy auto-scaling Kubernetes clusters\nAutomate cloud infrastructure with Terraform and GitHub Actions",
            ],
        ];

        $courses = [];
        foreach ($coursesData as $idx => $cData) {
            $cat = $categories[$cData['category_slug']];
            $tplId = $templateIds[$idx % max(1, count($templateIds))] ?? $defaultTemplateId;

            $course = Course::updateOrCreate(
                ['title' => $cData['title']],
                [
                    'instructor_id' => $cData['instructor']->id,
                    'certificate_template_id' => $tplId,
                    'description' => $cData['description'],
                    'featured_image' => $cData['featured_image'],
                    'intro_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'visibility' => 'public',
                    'public_course' => true,
                    'max_students' => 500,
                    'difficulty' => $cData['difficulty'],
                    'pricing_model' => $cData['pricing_model'],
                    'regular_price' => $cData['regular_price'],
                    'sale_price' => $cData['sale_price'],
                    'course_category_id' => $cat->id,
                    'course_language_id' => $language->id,
                    'category' => $cat->name,
                    'language' => $language->name,
                    'tags' => $cData['tags'],
                    'requirements' => $cData['requirements'],
                    'objectives' => $cData['objectives'],
                    'status' => 'published',
                    'is_live_course' => true,
                ]
            );
            $courses[] = $course;

            // Seed 2 Topics, 4 Lessons, 1 Quiz, and 1 Assignment per course
            $topic1 = CourseTopic::updateOrCreate(
                ['course_id' => $course->id, 'order' => 1],
                [
                    'title' => 'Module 1: Core Foundations & Architecture',
                    'description' => 'Master the core principles, environment setup, and foundational patterns.',
                ]
            );

            Lesson::updateOrCreate(
                ['course_topic_id' => $topic1->id, 'order' => 1],
                [
                    'title' => 'Welcome, Architecture Overview & Environment Setup',
                    'description' => '<p>In this opening lesson, we explore the overall system architecture and configure your local development workspace.</p>',
                    'video_type' => 'url',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'duration' => 18,
                    'is_preview' => true,
                    'is_live' => false,
                ]
            );

            Lesson::updateOrCreate(
                ['course_topic_id' => $topic1->id, 'order' => 2],
                [
                    'title' => 'Deep Dive into Core Workflows & Best Practices',
                    'description' => '<p>Step-by-step implementation of core workflows with industry best practices and clean code standards.</p>',
                    'video_type' => 'url',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'duration' => 32,
                    'is_preview' => false,
                    'is_live' => false,
                ]
            );

            $quiz = Quiz::updateOrCreate(
                ['course_topic_id' => $topic1->id, 'order' => 3],
                [
                    'title' => 'Module 1 Knowledge Check Quiz',
                    'description' => 'Verify your understanding of the core concepts covered in Module 1.',
                    'passing_score' => 70,
                    'time_limit' => 15,
                ]
            );

            QuizQuestion::updateOrCreate(
                ['quiz_id' => $quiz->id, 'order' => 1],
                [
                    'question' => 'Which architectural practice improves maintainability and scalability the most?',
                    'type' => 'multiple-choice',
                    'options' => [
                        'Modular separation of concerns and automated testing',
                        'Hardcoding configuration values directly in views',
                        'Skipping version control',
                        'Disabling error logging',
                    ],
                    'correct_answer' => 0,
                ]
            );

            $topic2 = CourseTopic::updateOrCreate(
                ['course_id' => $course->id, 'order' => 2],
                [
                    'title' => 'Module 2: Production Implementation & Capstone Project',
                    'description' => 'Build, test, optimize, and ship a complete real-world project.',
                ]
            );

            Lesson::updateOrCreate(
                ['course_topic_id' => $topic2->id, 'order' => 1],
                [
                    'title' => 'Building the Real-World Capstone Project',
                    'description' => '<p>Follow along as we build and test the complete production project from end to end.</p>',
                    'video_type' => 'url',
                    'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                    'duration' => 45,
                    'is_preview' => false,
                    'is_live' => false,
                ]
            );

            Assignment::updateOrCreate(
                ['course_topic_id' => $topic2->id, 'order' => 2],
                [
                    'title' => 'Capstone Project Submission',
                    'description' => 'Submit your completed project repository and documentation for instructor feedback.',
                    'instructions' => 'Upload a ZIP archive or PDF summary containing your project code, screenshots, and architecture notes.',
                ]
            );
        }

        // 5. Enrollments & Reviews (Testimonials)
        $reviewsComments = [
            'This course completely transformed my career trajectory. The hands-on projects and instructor mentorship are unmatched!',
            'Crystal clear explanations, modern production patterns, and an awesome interactive learning portal. Highly recommended!',
            'The curriculum is structured so thoughtfully. I was able to apply everything directly to my team\'s production project.',
            'Best online learning experience I have ever had. The live sessions and practical capstone assignments made all the difference.',
            'From beginner concepts to advanced enterprise architecture, every module delivered immense value.',
            'Outstanding instructor support and top-notch course materials. Earned my verified certificate and landed a promotion!',
        ];

        foreach ($courses as $idx => $course) {
            foreach ($students as $sIdx => $student) {
                $status = ($idx === 0 && $sIdx === 0) ? Enrollment::STATUS_COMPLETED : Enrollment::STATUS_APPROVED;
                $enrollment = Enrollment::updateOrCreate(
                    ['user_id' => $student->id, 'course_id' => $course->id],
                    [
                        'status' => $status,
                        'enrolled_at' => now()->subDays(15 - $idx),
                    ]
                );

                if ($sIdx < 2) {
                    Review::updateOrCreate(
                        ['course_id' => $course->id, 'user_id' => $student->id],
                        [
                            'enrollment_id' => $enrollment->id,
                            'rating' => 5,
                            'comment' => $reviewsComments[($idx + $sIdx) % count($reviewsComments)],
                            'is_approved' => true,
                        ]
                    );
                }
            }
        }

        // 6. Course Bundles (2 featured bundles)
        $bundle1 = Bundle::updateOrCreate(
            ['title' => 'Full-Stack Engineering & Applied AI Career Bundle'],
            [
                'vendor_id' => $instructors[0]->id,
                'description' => 'Master Full-Stack Web Development, Generative AI Engineering, and Cloud Native DevOps in one comprehensive career track.',
                'featured_image' => 'assets/front/img/home-1/courses/courses-01.jpg',
                'price' => 149.00,
                'status' => 'active',
                'approval_status' => 'approved',
            ]
        );
        $bundle1->courses()->sync([$courses[0]->id, $courses[1]->id, $courses[5]->id]);

        $bundle2 = Bundle::updateOrCreate(
            ['title' => 'Product Design, Growth Marketing & Strategy Bundle'],
            [
                'vendor_id' => $instructors[2]->id,
                'description' => 'Combine UI/UX Figma mastery, Growth Marketing, and Agile Product Management to launch and scale digital products.',
                'featured_image' => 'assets/front/img/home-1/courses/courses-03.jpg',
                'price' => 99.00,
                'status' => 'active',
                'approval_status' => 'approved',
            ]
        );
        $bundle2->courses()->sync([$courses[2]->id, $courses[3]->id, $courses[4]->id]);

        // 7. Scheduled Live Classes (Zoom & Google Meet)
        LiveClass::updateOrCreate(
            ['course_id' => $courses[0]->id, 'title' => 'Live Architecture Q&A & Code Review Session'],
            [
                'instructor_id' => $instructors[0]->id,
                'description' => 'Bring your project questions for a live interactive code review and system design session.',
                'provider' => 'zoom',
                'meeting_id' => '84920194821',
                'meeting_password' => 'OUT2026',
                'join_url' => 'https://zoom.us/j/84920194821?pwd=OUT2026',
                'host_url' => 'https://zoom.us/s/84920194821?role=host',
                'scheduled_at' => now()->addDays(2)->setHour(16)->setMinute(0),
                'duration_minutes' => 60,
                'status' => LiveClass::STATUS_SCHEDULED,
            ]
        );

        LiveClass::updateOrCreate(
            ['course_id' => $courses[1]->id, 'title' => 'Live RAG & AI Agents Implementation Lab'],
            [
                'instructor_id' => $instructors[1]->id,
                'description' => 'Live hands-on coding session building a multi-agent workflow with vector search.',
                'provider' => 'google_meet',
                'meeting_id' => 'edu-ai-lab',
                'meeting_password' => null,
                'join_url' => 'https://meet.google.com/edu-aiwl-lab',
                'host_url' => 'https://meet.google.com/edu-aiwl-lab',
                'scheduled_at' => now()->addDays(4)->setHour(18)->setMinute(0),
                'duration_minutes' => 90,
                'status' => LiveClass::STATUS_SCHEDULED,
            ]
        );

        // 8. Banners (for Web & Mobile App Home)
        $bannersData = [
            [
                'title' => 'Master Full-Stack & Applied AI Engineering',
                'subtitle' => 'Join 50,000+ learners building real-world projects with expert mentorship.',
                'image_path' => 'assets/front/img/home-1/courses/courses-01.jpg',
                'link' => '/courses',
                'button_text' => 'Explore Courses',
                'order' => 1,
                'is_active' => true,
            ],
        ];
        foreach ($bannersData as $b) {
            DB::table('banners')->updateOrInsert(
                ['title' => $b['title']],
                array_merge($b, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 12. Coupons
        DB::table('coupons')->updateOrInsert(
            ['code' => 'WELCOME20'],
            [
                'type' => 'percentage',
                'value' => 20,
                'valid_from' => now()->subDays(5),
                'valid_to' => now()->addMonths(6),
                'usage_limit' => 500,
                'used_count' => 12,
                'course_id' => null,
                'instructor_id' => $instructors[0]->id,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 13. Payments & Lesson Progress
        $firstEnrollment = Enrollment::where('user_id', $students[0]->id)->where('course_id', $courses[0]->id)->first();
        if ($firstEnrollment) {
            $paymentId = DB::table('payments')->where('enrollment_id', $firstEnrollment->id)->value('id');
            if (!$paymentId) {
                $paymentId = DB::table('payments')->insertGetId([
                    'user_id' => $students[0]->id,
                    'enrollment_id' => $firstEnrollment->id,
                    'payment_method' => 'stripe',
                    'amount' => 79.00,
                    'subtotal' => 79.00,
                    'tax_amount' => 0.00,
                    'tax_rate' => 0.00,
                    'platform_commission' => 15.80,
                    'instructor_earning' => 63.20,
                    'commission_processed' => true,
                    'status' => 'completed',
                    'transaction_id' => 'TXN-OUT-10001',
                    'created_at' => now()->subDays(10),
                    'updated_at' => now()->subDays(10),
                ]);
                $firstEnrollment->update(['payment_id' => $paymentId]);
            }

            // Mark lessons completed for the completed course
            $courseLessons = Lesson::whereHas('topic', fn($q) => $q->where('course_id', $courses[0]->id))->get();
            foreach ($courseLessons as $les) {
                DB::table('lesson_progress')->updateOrInsert(
                    ['user_id' => $students[0]->id, 'lesson_id' => $les->id],
                    [
                        'enrollment_id' => $firstEnrollment->id,
                        'is_completed' => true,
                        'completed_at' => now()->subDays(2),
                        'progress_percentage' => 100,
                        'last_accessed_at' => now()->subDays(2),
                        'created_at' => now()->subDays(5),
                        'updated_at' => now()->subDays(2),
                    ]
                );
            }
        }

        // 14. Community Q&A & Course Forum Discussions
        $qId = DB::table('community_questions')->where('title', 'Best practices for structuring large Laravel + React monorepos?')->value('id');
        if (!$qId) {
            $qId = DB::table('community_questions')->insertGetId([
                'user_id' => $students[0]->id,
                'title' => 'Best practices for structuring large Laravel + React monorepos?',
                'description' => 'When building an enterprise SaaS platform with Laravel API and React SPA, how do you organize shared types, authentication, and CI/CD pipelines?',
                'tags' => json_encode(['Laravel', 'React', 'Architecture']),
                'views' => 48,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ]);
            DB::table('community_answers')->insert([
                'user_id' => $instructors[0]->id,
                'question_id' => $qId,
                'content' => 'Use Laravel Sanctum for stateful SPA authentication or token-based mobile auth, keep domain logic in service classes, and run automated feature tests in GitHub Actions.',
                'is_accepted' => true,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]);
        }

        $discId = DB::table('course_discussions')->where('course_id', $courses[0]->id)->value('id');
        if (!$discId) {
            $discId = DB::table('course_discussions')->insertGetId([
                'course_id' => $courses[0]->id,
                'user_id' => $instructors[0]->id,
                'title' => 'Welcome to the Bootcamp! Resources & Live Office Hours Schedule',
                'content' => 'Welcome everyone! Make sure to download the starter repository from Module 1 and join our weekly Zoom architecture Q&A session.',
                'is_pinned' => true,
                'is_announcement' => true,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ]);
            DB::table('course_discussion_replies')->insert([
                'discussion_id' => $discId,
                'user_id' => $students[0]->id,
                'content' => 'Thank you Professor! Excited to join the upcoming live session.',
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ]);
        }

        // 15. Newsletter Subscribers & Instructor Support Ticket
        foreach (['alex.rivera@example.com' => 'Alex Rivera', 'priya.patel@example.com' => 'Priya Patel'] as $em => $nm) {
            DB::table('newsletter_subscribers')->updateOrInsert(
                ['email' => $em],
                ['name' => $nm, 'source' => 'homepage', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $instTicket = SupportTicket::firstOrCreate(
            ['user_id' => $instructors[0]->id, 'sender_role' => 'instructor'],
            [
                'ticket_number' => SupportTicket::generateTicketNumber(),
                'subject' => 'Zoom Auto-Provisioning & Payout Schedule Inquiry',
                'department' => 'technical',
                'priority' => 'medium',
                'status' => 'answered',
                'last_reply_at' => now()->subHours(3),
            ]
        );
        if ($instTicket->messages()->count() === 0) {
            SupportTicketMessage::create([
                'ticket_id' => $instTicket->id,
                'user_id' => $instructors[0]->id,
                'is_admin_reply' => false,
                'message' => 'Hello Admin team, could you confirm that Zoom S2S OAuth is enabled for my upcoming live architecture workshop?',
            ]);
        }
    }
}
