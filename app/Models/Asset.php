<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Asset extends Model
{
    protected $fillable = [
        'category_id',
        'code',
        'name',
        'description',
        'image',
        'total_stock',
        'available_stock',
        'condition',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function imageUrl(): ?string
    {
        if (blank($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'data:image/')
            || str_starts_with($this->image, 'http://')
            || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        if (! self::isPublicImagePath($this->image) || ! Storage::disk('public')->exists($this->image)) {
            return null;
        }

        return route('storage-file.show', ['path' => $this->image], false);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->imageUrl();
    }

    public static function isPublicImagePath(string $path): bool
    {
        return preg_match('/\Aassets\/[A-Za-z0-9][A-Za-z0-9._-]*\z/', $path) === 1;
    }
}
