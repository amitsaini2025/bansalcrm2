<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class FollowupConsultant extends Model
{
    protected $table = 'followup_consultants';

    protected $fillable = [
        'slug',
        'name',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Active consultants ordered for menus / forms.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function activeOrdered()
    {
        if (! Schema::hasTable('followup_consultants')) {
            return static::query()->whereRaw('0 = 1')->get();
        }

        return static::query()
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Short UI label (nav / checkboxes) from DB name, e.g. "Ankit Calendar" → "Ankit".
     */
    public static function shortLabelFromName(?string $name, string $slugFallback = ''): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return $slugFallback;
        }

        $short = preg_replace('/\s+(Calendar|Followups)$/ui', '', $name);

        return ($short !== null && trim($short) !== '') ? trim($short) : $name;
    }

    /**
     * Note/body style label, e.g. "Ankit Calendar" → "Ankit Followups".
     */
    public static function followupLabelFromName(?string $name, string $slugFallback = ''): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return $slugFallback;
        }

        $replaced = preg_replace('/\s+Calendar$/u', ' Followups', $name);

        return ($replaced !== null && trim($replaced) !== '') ? trim($replaced) : $name;
    }
}
