<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $fillable = [
        'image_path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public static function ordered(): Collection
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public static function publicPayload(): array
    {
        return static::ordered()
            ->map(fn (self $image) => [
                'id'        => $image->id,
                'image_url' => asset('storage/'.$image->image_path),
            ])
            ->values()
            ->all();
    }
}
