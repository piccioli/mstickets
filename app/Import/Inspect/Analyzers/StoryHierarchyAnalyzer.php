<?php

declare(strict_types=1);

namespace App\Import\Inspect\Analyzers;

final class StoryHierarchyAnalyzer
{
    /**
     * @param  array<int, array{id: int, parent_id: int|null}>  $stories
     * @param  array<int, array{parent_id: int, child_id: int}>  $storyStoryRows
     * @return array{story_story_rows:int,story_story_not_reflected_in_parent_id:int,parent_id_not_reflected_in_story_story:int}
     */
    public static function analyze(array $stories, array $storyStoryRows): array
    {
        $parentIdByStoryId = [];

        foreach ($stories as $story) {
            $parentIdByStoryId[$story['id']] = $story['parent_id'];
        }

        $storyStoryParentByChildId = [];
        $notReflectedInParentId = 0;

        foreach ($storyStoryRows as $row) {
            $storyStoryParentByChildId[$row['child_id']] = $row['parent_id'];

            if (($parentIdByStoryId[$row['child_id']] ?? null) !== $row['parent_id']) {
                $notReflectedInParentId++;
            }
        }

        $notReflectedInStoryStory = 0;

        foreach ($parentIdByStoryId as $storyId => $parentId) {
            if ($parentId === null) {
                continue;
            }

            if (($storyStoryParentByChildId[$storyId] ?? null) !== $parentId) {
                $notReflectedInStoryStory++;
            }
        }

        return [
            'story_story_rows' => count($storyStoryRows),
            'story_story_not_reflected_in_parent_id' => $notReflectedInParentId,
            'parent_id_not_reflected_in_story_story' => $notReflectedInStoryStory,
        ];
    }
}
