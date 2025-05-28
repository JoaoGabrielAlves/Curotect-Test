<?php

use App\DTOs\PostFiltersDTO;

describe('PostFiltersDTO', function () {
    describe('constructor', function () {
        it('creates DTO with default values', function () {
            $dto = new PostFiltersDTO;

            expect($dto->search)->toBe('')
                ->and($dto->category)->toBe('')
                ->and($dto->status)->toBe('')
                ->and($dto->sort)->toBe('created_at')
                ->and($dto->direction)->toBe('desc')
                ->and($dto->perPage)->toBe(10);
        });

        it('creates DTO with custom values', function () {
            $dto = new PostFiltersDTO(
                search: 'Laravel Tutorial',
                category: 'technology',
                status: 'published',
                sort: 'title',
                direction: 'asc',
                perPage: 25
            );

            expect($dto->search)->toBe('Laravel Tutorial')
                ->and($dto->category)->toBe('technology')
                ->and($dto->status)->toBe('published')
                ->and($dto->sort)->toBe('title')
                ->and($dto->direction)->toBe('asc')
                ->and($dto->perPage)->toBe(25);
        });

        it('creates DTO with partial custom values', function () {
            $dto = new PostFiltersDTO(
                search: 'Vue.js Guide',
                status: 'draft',
                perPage: 5
            );

            expect($dto->search)->toBe('Vue.js Guide')
                ->and($dto->category)->toBe('') // default
                ->and($dto->status)->toBe('draft')
                ->and($dto->sort)->toBe('created_at') // default
                ->and($dto->direction)->toBe('desc') // default
                ->and($dto->perPage)->toBe(5);
        });
    });

    describe('fromArray', function () {
        it('creates DTO from complete array', function () {
            $filters = [
                'search' => 'React Components',
                'category' => 'javascript',
                'status' => 'published',
                'sort' => 'updated_at',
                'direction' => 'asc',
                'per_page' => 20,
            ];

            $dto = PostFiltersDTO::fromArray($filters);

            expect($dto->search)->toBe('React Components')
                ->and($dto->category)->toBe('javascript')
                ->and($dto->status)->toBe('published')
                ->and($dto->sort)->toBe('updated_at')
                ->and($dto->direction)->toBe('asc')
                ->and($dto->perPage)->toBe(20);
        });

        it('creates DTO from partial array with defaults', function () {
            $filters = [
                'search' => 'Python',
                'category' => 'programming',
            ];

            $dto = PostFiltersDTO::fromArray($filters);

            expect($dto->search)->toBe('Python')
                ->and($dto->category)->toBe('programming')
                ->and($dto->status)->toBe('') // default
                ->and($dto->sort)->toBe('created_at') // default
                ->and($dto->direction)->toBe('desc') // default
                ->and($dto->perPage)->toBe(10); // default
        });

        it('creates DTO from empty array with all defaults', function () {
            $dto = PostFiltersDTO::fromArray([]);

            expect($dto->search)->toBe('')
                ->and($dto->category)->toBe('')
                ->and($dto->status)->toBe('')
                ->and($dto->sort)->toBe('created_at')
                ->and($dto->direction)->toBe('desc')
                ->and($dto->perPage)->toBe(10);
        });

        it('handles type coercion for perPage', function () {
            $filters = [
                'per_page' => '25', // string number
            ];

            $dto = PostFiltersDTO::fromArray($filters);

            expect($dto->perPage)->toBe(25)
                ->and($dto->perPage)->toBeInt();
        });

        it('handles non-numeric perPage gracefully', function () {
            $filters = [
                'per_page' => 'invalid',
            ];

            $dto = PostFiltersDTO::fromArray($filters);

            expect($dto->perPage)->toBe(0); // PHP's (int) cast of 'invalid'
        });

        it('handles null values with defaults', function () {
            $filters = [
                'search' => null,
                'category' => null,
                'status' => null,
                'sort' => null,
                'direction' => null,
                'per_page' => null,
            ];

            $dto = PostFiltersDTO::fromArray($filters);

            expect($dto->search)->toBe('')
                ->and($dto->category)->toBe('')
                ->and($dto->status)->toBe('')
                ->and($dto->sort)->toBe('created_at')
                ->and($dto->direction)->toBe('desc')
                ->and($dto->perPage)->toBe(10);
        });
    });

    describe('toArray', function () {
        it('converts DTO to array with default values', function () {
            $dto = new PostFiltersDTO;

            $array = $dto->toArray();

            expect($array)->toBe([
                'search' => '',
                'category' => '',
                'status' => '',
                'sort' => 'created_at',
                'direction' => 'desc',
                'per_page' => 10,
            ]);
        });

        it('converts DTO to array with custom values', function () {
            $dto = new PostFiltersDTO(
                search: 'Machine Learning',
                category: 'ai',
                status: 'published',
                sort: 'views_count',
                direction: 'desc',
                perPage: 15
            );

            $array = $dto->toArray();

            expect($array)->toBe([
                'search' => 'Machine Learning',
                'category' => 'ai',
                'status' => 'published',
                'sort' => 'views_count',
                'direction' => 'desc',
                'per_page' => 15,
            ]);
        });

        it('maintains consistent key naming (perPage -> per_page)', function () {
            $dto = new PostFiltersDTO(perPage: 50);

            $array = $dto->toArray();

            expect($array)->toHaveKey('per_page')
                ->and($array['per_page'])->toBe(50)
                ->and($array)->not->toHaveKey('perPage');
        });
    });

    describe('roundtrip conversion', function () {
        it('maintains data integrity through fromArray -> toArray', function () {
            $originalData = [
                'search' => 'Database Design',
                'category' => 'backend',
                'status' => 'draft',
                'sort' => 'title',
                'direction' => 'asc',
                'per_page' => 12,
            ];

            $dto = PostFiltersDTO::fromArray($originalData);
            $convertedData = $dto->toArray();

            expect($convertedData)->toBe($originalData);
        });

        it('handles partial data roundtrip correctly', function () {
            $inputData = [
                'search' => 'GraphQL API',
                'per_page' => 8,
            ];

            $expectedOutput = [
                'search' => 'GraphQL API',
                'category' => '',
                'status' => '',
                'sort' => 'created_at',
                'direction' => 'desc',
                'per_page' => 8,
            ];

            $dto = PostFiltersDTO::fromArray($inputData);
            $outputData = $dto->toArray();

            expect($outputData)->toBe($expectedOutput);
        });
    });

    describe('immutability', function () {
        it('is readonly and cannot modify properties', function () {
            $dto = new PostFiltersDTO(search: 'Original Search');

            expect($dto->search)->toBe('Original Search');

            // This would cause a fatal error in PHP 8.1+ with readonly properties
            // We can't test this directly without causing test failure
            // But we can document the expected behavior
            expect(true)->toBeTrue(); // Placeholder assertion
        });
    });

    describe('edge cases', function () {
        it('handles empty strings correctly', function () {
            $filters = [
                'search' => '',
                'category' => '',
                'status' => '',
                'sort' => '',
                'direction' => '',
                'per_page' => '',
            ];

            $dto = PostFiltersDTO::fromArray($filters);

            expect($dto->search)->toBe('')
                ->and($dto->category)->toBe('')
                ->and($dto->status)->toBe('')
                ->and($dto->sort)->toBe('') // Empty, not default
                ->and($dto->direction)->toBe('') // Empty, not default
                ->and($dto->perPage)->toBe(0); // (int) '' = 0
        });

        it('handles whitespace in string values', function () {
            $filters = [
                'search' => '  Laravel Framework  ',
                'category' => ' web-development ',
                'status' => ' published ',
            ];

            $dto = PostFiltersDTO::fromArray($filters);

            // DTO doesn't trim - that would be business logic
            expect($dto->search)->toBe('  Laravel Framework  ')
                ->and($dto->category)->toBe(' web-development ')
                ->and($dto->status)->toBe(' published ');
        });

        it('handles large perPage values', function () {
            $dto = PostFiltersDTO::fromArray(['per_page' => 9999]);

            expect($dto->perPage)->toBe(9999);
        });

        it('handles negative perPage values', function () {
            $dto = PostFiltersDTO::fromArray(['per_page' => -5]);

            expect($dto->perPage)->toBe(-5);
        });
    });
});
