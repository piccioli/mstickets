<?php

declare(strict_types=1);

namespace App\Domain\Documentation\Models;

use App\Domain\Documentation\Enums\DocumentationCategory;
use App\Domain\Tags\Models\Tag;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'slug', 'body', 'category', 'pdf_path', 'pdf_generated_at'])]
class DocumentationPage extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentationCategory::class,
            'pdf_generated_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents');
        $this->addMediaCollection('images');
    }

    /**
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }
}
