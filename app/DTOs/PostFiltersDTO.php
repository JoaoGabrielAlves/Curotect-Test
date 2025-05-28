<?php

namespace App\DTOs;

readonly class PostFiltersDTO
{
    public function __construct(
        public string $search = '',
        public string $category = '',
        public string $status = '',
        public string $sort = 'created_at',
        public string $direction = 'desc',
        public int $perPage = 10
    ) {}

    public static function fromArray(array $filters): self
    {
        return new self(
            search: $filters['search'] ?? '',
            category: $filters['category'] ?? '',
            status: $filters['status'] ?? '',
            sort: $filters['sort'] ?? 'created_at',
            direction: $filters['direction'] ?? 'desc',
            perPage: (int) ($filters['per_page'] ?? 10)
        );
    }

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'category' => $this->category,
            'status' => $this->status,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'per_page' => $this->perPage,
        ];
    }
}
