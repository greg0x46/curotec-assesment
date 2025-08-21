<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'assigned_to_id',
        'title',
        'description',
        'priority',
        'due_date',
        'status',
    ];

    protected $casts = [
        'status'   => TaskStatus::class,
        'priority' => TaskPriority::class,
        'due_date' => 'immutable_datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    /** Apply a "q" full-text-ish search on title/description. */
    public function scopeSearch($query, ?string $term)
    {
        if (!filled($term)) {
            return $query;
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

        return $query->where(function ($q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('description', 'like', $like);
        });
    }

    /** Filter by status (accepts enum instance or backed value). */
    public function scopeStatus($query, $status)
    {
        if ($status === null || $status === '') {
            return $query;
        }

        // If enums are string-backed (e.g. 'pending', 'in_progress', 'done'),
        // this will work with either the enum instance or the backed value.
        return $query->where('status', $status instanceof TaskStatus ? $status->value : $status);
    }

    /** Filter by priority (accepts enum instance or backed value). */
    public function scopePriority($query, $priority)
    {
        if ($priority === null || $priority === '') {
            return $query;
        }

        return $query->where('priority', $priority instanceof TaskPriority ? $priority->value : $priority);
    }

    /** Filter by a single category id. */
    public function scopeCategory($query, $categoryId)
    {
        if (empty($categoryId)) {
            return $query;
        }

        return $query->whereHas('categories', function ($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    /** Convenience scope to apply all supported filters at once. */
    public function scopeApplyFilters($query, array $filters)
    {
        return $query
            ->search($filters['q'] ?? null)
            ->status($filters['status'] ?? null)
            ->priority($filters['priority'] ?? null)
            ->category($filters['category'] ?? null);
    }
}
