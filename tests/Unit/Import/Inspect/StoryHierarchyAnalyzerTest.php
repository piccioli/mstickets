<?php

declare(strict_types=1);

use App\Import\Inspect\Analyzers\StoryHierarchyAnalyzer;

test('detects mismatches between story_story rows and stories.parent_id', function (): void {
    $stories = [
        ['id' => 1, 'parent_id' => null],
        ['id' => 2, 'parent_id' => 1],
        ['id' => 3, 'parent_id' => null],
        ['id' => 4, 'parent_id' => 999],
    ];

    $storyStoryRows = [
        ['parent_id' => 1, 'child_id' => 2],
        ['parent_id' => 1, 'child_id' => 3],
    ];

    $analysis = StoryHierarchyAnalyzer::analyze($stories, $storyStoryRows);

    expect($analysis['story_story_rows'])->toBe(2)
        ->and($analysis['story_story_not_reflected_in_parent_id'])->toBe(1)
        ->and($analysis['parent_id_not_reflected_in_story_story'])->toBe(1);
});

test('reports zero mismatches when both sides agree', function (): void {
    $stories = [
        ['id' => 1, 'parent_id' => null],
        ['id' => 2, 'parent_id' => 1],
    ];

    $storyStoryRows = [
        ['parent_id' => 1, 'child_id' => 2],
    ];

    $analysis = StoryHierarchyAnalyzer::analyze($stories, $storyStoryRows);

    expect($analysis['story_story_not_reflected_in_parent_id'])->toBe(0)
        ->and($analysis['parent_id_not_reflected_in_story_story'])->toBe(0);
});
