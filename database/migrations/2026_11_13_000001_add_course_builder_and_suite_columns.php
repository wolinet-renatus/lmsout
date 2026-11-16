<?php

/**
 * Database migration: add course builder and suite columns.
 *
 * @author Renatus Bernardo
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'course_type')) {
                $table->string('course_type', 32)->default('course')->after('title');
            }
            if (!Schema::hasColumn('courses', 'drip_enabled')) {
                $table->boolean('drip_enabled')->default(false)->after('schedule_enabled');
            }
            if (!Schema::hasColumn('courses', 'assistant_instructor_id')) {
                $table->foreignId('assistant_instructor_id')->nullable()->after('instructor_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('courses', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('courses', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('seo_title');
            }
            if (!Schema::hasColumn('courses', 'seo_tags')) {
                $table->text('seo_tags')->nullable()->after('seo_description');
            }
            if (!Schema::hasColumn('courses', 'marketing_copy')) {
                $table->json('marketing_copy')->nullable()->after('seo_tags');
            }
        });

        Schema::table('course_topics', function (Blueprint $table) {
            if (!Schema::hasColumn('course_topics', 'drip_days')) {
                $table->unsignedInteger('drip_days')->nullable()->after('order');
            }
            if (!Schema::hasColumn('course_topics', 'drip_date')) {
                $table->timestamp('drip_date')->nullable()->after('drip_days');
            }
        });

        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'drip_days')) {
                $table->unsignedInteger('drip_days')->nullable()->after('is_preview');
            }
            if (!Schema::hasColumn('lessons', 'drip_date')) {
                $table->timestamp('drip_date')->nullable()->after('drip_days');
            }
            if (!Schema::hasColumn('lessons', 'content_text')) {
                $table->longText('content_text')->nullable()->after('description');
            }
        });

        if (!Schema::hasTable('course_co_instructors')) {
            Schema::create('course_co_instructors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('revenue_share', 5, 2)->default(0);
                $table->boolean('can_edit')->default(false);
                $table->string('role', 32)->default('co_instructor');
                $table->timestamps();

                $table->unique(['course_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('course_faqs')) {
            Schema::create('course_faqs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->string('question');
                $table->text('answer');
                $table->unsignedInteger('order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('question_banks')) {
            Schema::create('question_banks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('subject')->nullable();
                $table->string('difficulty', 32)->default('medium');
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('question_bank_items')) {
            Schema::create('question_bank_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_bank_id')->constrained('question_banks')->cascadeOnDelete();
                $table->string('subject')->nullable();
                $table->string('difficulty', 32)->default('medium');
                $table->text('question');
                $table->string('type', 32)->default('multiple_choice');
                $table->json('options')->nullable();
                $table->text('correct_answer')->nullable();
                $table->text('explanation')->nullable();
                $table->unsignedInteger('marks')->default(1);
                $table->timestamps();
            });
        }

        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'question_bank_id')) {
                $table->foreignId('question_bank_id')->nullable()->after('course_topic_id')->constrained('question_banks')->nullOnDelete();
            }
            if (!Schema::hasColumn('quizzes', 'random_questions_count')) {
                $table->unsignedInteger('random_questions_count')->nullable()->after('question_bank_id');
            }
            if (!Schema::hasColumn('quizzes', 'is_live_exam')) {
                $table->boolean('is_live_exam')->default(false)->after('time_limit');
            }
            if (!Schema::hasColumn('quizzes', 'result_publish_at')) {
                $table->timestamp('result_publish_at')->nullable()->after('is_live_exam');
            }
            if (!Schema::hasColumn('quizzes', 'proctoring_enabled')) {
                $table->boolean('proctoring_enabled')->default(false)->after('result_publish_at');
            }
            if (!Schema::hasColumn('quizzes', 'subject')) {
                $table->string('subject')->nullable()->after('title');
            }
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'proctoring_snapshots')) {
                $table->json('proctoring_snapshots')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('quiz_attempts', 'proctoring_flags')) {
                $table->json('proctoring_flags')->nullable()->after('proctoring_snapshots');
            }
            if (!Schema::hasColumn('quiz_attempts', 'merit_rank')) {
                $table->unsignedInteger('merit_rank')->nullable()->after('passed');
            }
            if (!Schema::hasColumn('quiz_attempts', 'paper_question_ids')) {
                $table->json('paper_question_ids')->nullable()->after('proctoring_flags');
            }
        });

        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'accepted_submission_types')) {
                $table->json('accepted_submission_types')->nullable()->after('instructions');
            }
            if (!Schema::hasColumn('assignments', 'max_file_size_mb')) {
                $table->unsignedInteger('max_file_size_mb')->default(10)->after('accepted_submission_types');
            }
            if (!Schema::hasColumn('assignments', 'rubric')) {
                $table->text('rubric')->nullable()->after('max_file_size_mb');
            }
            if (!Schema::hasColumn('assignments', 'originality_check_enabled')) {
                $table->boolean('originality_check_enabled')->default(true)->after('rubric');
            }
        });

        Schema::table('assignment_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('assignment_submissions', 'similarity_score')) {
                $table->decimal('similarity_score', 5, 2)->nullable()->after('grade');
            }
            if (!Schema::hasColumn('assignment_submissions', 'similarity_report')) {
                $table->json('similarity_report')->nullable()->after('similarity_score');
            }
            if (!Schema::hasColumn('assignment_submissions', 'external_similarity_provider')) {
                $table->string('external_similarity_provider')->nullable()->after('similarity_report');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'stripe_connect_account_id')) {
                $table->string('stripe_connect_account_id')->nullable()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'stripe_connect_onboarded')) {
                $table->boolean('stripe_connect_onboarded')->default(false)->after('stripe_connect_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_items');
        Schema::dropIfExists('question_banks');
        Schema::dropIfExists('course_faqs');
        Schema::dropIfExists('course_co_instructors');
    }
};

