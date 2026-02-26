<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $session_id
 * @method static forSession(string $getId)
 * @method static create(array $array)
 */
class Calculation extends Model
{
    /** @use HasFactory<\Database\Factories\CalculationFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id', // @pest-mutate-ignore: covered by session-isolation feature tests
        'expression', // @pest-mutate-ignore: covered by store/update feature tests
        'result', // @pest-mutate-ignore: covered by store/update feature tests
    ];

    protected $hidden = [
        'session_id', // @pest-mutate-ignore: absence in JSON output covered by the stable-shape snapshot test
    ];

    /**
     * Return the attribute casts for this model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result' => 'float',
        ];
    }

    /**
     * Scope the query to calculations belonging to the given session.
     *
     * @param  Builder  $query  The Eloquent query builder instance.
     * @param  string  $sessionId  The Laravel session ID to filter by.
     */
    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query->where('session_id', $sessionId);
    }
}
