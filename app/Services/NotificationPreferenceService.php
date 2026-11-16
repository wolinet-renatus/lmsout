<?php

namespace App\Services;

use App\Models\User;
use App\Services\SettingsRepository;

/**
 * Notification Preference Service
 *
 * This service is responsible for managing notification preferences for users.
 *
 * @package App\Services
 * @author Renatus Bernardo
 */
class NotificationPreferenceService
{
    /**
     * Initialize dependencies for this class.
     */
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    /**
     * Student defaults.
     */
    public function studentDefaults(): array
    {
        return [
            'email_notifications' => true,
            'course_updates' => true,
            'review_notifications' => true,
            'course_reminders' => true,
            'progress_reports' => true,
            'marketing_emails' => $this->marketingOptInDefault(),
        ];
    }

    /**
     * Instructor defaults.
     */
    public function instructorDefaults(): array
    {
        return [
            'email_notifications' => true,
            'course_updates' => true,
            'review_notifications' => true,
            'marketing_emails' => $this->marketingOptInDefault(),
        ];
    }

    /**
     * Defaults for user.
     */
    public function defaultsForUser(User $user): array
    {
        return $user->isInstructor()
            ? $this->instructorDefaults()
            : $this->studentDefaults();
    }

    /**
     * Merge with defaults.
     */
    public function mergeWithDefaults(array $existing, string $role): array
    {
        $defaults = $role === 'instructor'
            ? $this->instructorDefaults()
            : $this->studentDefaults();

        return array_merge($defaults, $existing);
    }

    /**
     * Merge for user.
     */
    public function mergeForUser(User $user, array $existing = [], array $updates = []): array
    {
        $defaults = $this->defaultsForUser($user);

        return array_merge($defaults, $existing, $updates);
    }

    /**
     * Prefers.
     */
    public function prefers(User $user, string $key, bool $fallback = true): bool
    {
        $preferences = $user->notificationSettings?->preferences ?? [];
        $merged = $this->mergeForUser($user, $preferences);

        return (bool) ($merged[$key] ?? $fallback);
    }

    /**
     * Marketing opt in default.
     */
    protected function marketingOptInDefault(): bool
    {
        $preferences = $this->settingsRepository->get('email.preferences', []);

        return (bool) ($preferences['marketing_opt_in_default'] ?? false);
    }
}
