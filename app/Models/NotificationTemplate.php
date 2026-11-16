<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Notification template Eloquent model — represents domain state, relationships, and query scopes.
 *
 * @author Renatus Bernardo
 */
class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'channel',
        'subject',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Render subject & body placeholders with provided key-value pairs.
     *
     * @param  array<string, string|int|float|null>  $data
     * @return array{subject: string, body: string}
     */
    public function render(array $data): array
    {
        $search = [];
        $replace = [];
        foreach ($data as $k => $v) {
            $search[] = '{' . $k . '}';
            $replace[] = (string) ($v ?? '');
        }

        return [
            'subject' => str_replace($search, $replace, (string) ($this->subject ?? '')),
            'body' => str_replace($search, $replace, (string) $this->body),
        ];
    }
}
