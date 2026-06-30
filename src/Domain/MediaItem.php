<?php
declare(strict_types=1);

namespace ModernImageManager\Domain;

defined('ABSPATH') || exit;

final class MediaItem
{
    public function __construct(
        public readonly int $id,
        public readonly string $filename,
        public readonly string $slug,
        public readonly string $page,
        public readonly string $title,
        public readonly string $caption,
        public readonly string $description,
        public readonly string $alt,
        public readonly string $thumb,
        public readonly string $url,
        public readonly string $status
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'filename' => $this->filename,
            'new_filename' => '',
            'slug' => $this->slug,
            'page' => $this->page,
            'title' => $this->title,
            'caption' => $this->caption,
            'description' => $this->description,
            'alt' => $this->alt,
            'thumb' => $this->thumb,
            'url' => $this->url,
            'status' => $this->status,
        ];
    }
}
