<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class FollowupCalendarBlockTiming extends Model
{
    protected $table = 'followup_calendar_block_timings';

    public const BLOCK_TYPES = [
        'unavailable' => 'Unavailable',
        'busy' => 'Busy',
    ];

    public const RECURRENCE = [
        'none' => 'No Recurrence',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ];

    /**
     * Built-in short labels (kept so existing block UIs work without the consultants table).
     *
     * @var array<string, string>
     */
    public const CONSULTANT_SLUG_OPTIONS = [
        'ankit' => 'Ankit',
        'rakshita' => 'Rakshita',
        'jaspreet' => 'Jaspreet',
        'syed' => 'Syed',
    ];

    /**
     * Consultant checkboxes / display: built-in four + active DB consultants.
     *
     * @return array<string, string> slug => short label
     */
    public static function consultantSlugOptions(): array
    {
        $options = self::CONSULTANT_SLUG_OPTIONS;

        if (Schema::hasTable('followup_consultants')) {
            foreach (FollowupConsultant::activeOrdered() as $consultant) {
                $slug = (string) $consultant->slug;
                if ($slug === '') {
                    continue;
                }
                $options[$slug] = FollowupConsultant::shortLabelFromName((string) $consultant->name, $slug);
            }
        }

        return $options;
    }

    /**
     * Slugs accepted when saving a block (includes inactive/legacy so edits keep working).
     *
     * @return list<string>
     */
    public static function allowedConsultantSlugsForValidation(): array
    {
        $slugs = array_keys(self::CONSULTANT_SLUG_OPTIONS);

        if (Schema::hasTable('followup_consultants')) {
            $db = FollowupConsultant::query()->pluck('slug')->all();
            $slugs = array_values(array_unique(array_merge($slugs, array_map('strval', $db))));
        }

        return array_values(array_filter($slugs, static fn ($s) => is_string($s) && $s !== ''));
    }

    protected $fillable = [
        'title',
        'block_date',
        'is_all_day',
        'start_time',
        'end_time',
        'block_type',
        'recurrence',
        'locations',
        'calendar_types',
        'consultant_slugs',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'block_date' => 'date',
            'is_all_day' => 'boolean',
            'is_active' => 'boolean',
            'locations' => 'array',
            'calendar_types' => 'array',
            'consultant_slugs' => 'array',
        ];
    }
}
