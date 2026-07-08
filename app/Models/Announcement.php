<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Announcement extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'links',
        'image_paths',
    ];

    protected function casts(): array
    {
        return [
            'links'       => 'array',
            'image_paths' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function excerpt(int $limit = 160): string
    {
        return Str::limit(trim(strip_tags($this->body)), $limit);
    }

    public function imageUrls(): array
    {
        return collect($this->image_paths ?? [])
            ->map(fn (string $path) => asset('storage/'.$path))
            ->values()
            ->all();
    }

    public function publicPayload(): array
    {
        return [
            'id'      => $this->id,
            'title'   => $this->title,
            'body'    => $this->body,
            'date'    => $this->created_at?->format('M d, Y'),
            'excerpt' => $this->excerpt(),
            'links'   => $this->normalizedLinks(),
            'images'  => $this->imageUrls(),
            'author'  => $this->user?->name,
        ];
    }

    public function managePayload(): array
    {
        return [
            ...$this->publicPayload(),
            'image_paths' => $this->image_paths ?? [],
            'created_at'  => $this->created_at?->format('M d, Y g:i A'),
            'updated_at'  => $this->updated_at?->format('M d, Y g:i A'),
        ];
    }

    public function normalizedLinks(): array
    {
        return collect($this->links ?? [])
            ->filter(fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
            ->map(fn ($link) => [
                'label' => $link['label'],
                'url'   => $link['url'],
            ])
            ->values()
            ->all();
    }

    public static function publicList(int $limit = null): array
    {
        $query = static::query()
            ->with('user:id,name')
            ->latest();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(fn (self $announcement) => $announcement->publicPayload())
            ->values()
            ->all();
    }
}
