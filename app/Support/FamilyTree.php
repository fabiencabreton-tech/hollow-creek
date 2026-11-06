<?php

namespace App\Support;

use App\Models\FamilyMember;
use Illuminate\Support\Collection;

/**
 * Mise en page de l'arbre généalogique d'une famille, calculée côté serveur :
 * une génération par rangée, les enfants regroupés sous leurs parents, les
 * conjoints côte à côte. Le résultat se dessine en SVG, sans bibliothèque.
 */
class FamilyTree
{
    public const NODE_WIDTH = 168;

    public const NODE_HEIGHT = 58;

    public const GAP_X = 28;

    public const GAP_Y = 64;

    public const PADDING = 20;

    /**
     * @param  Collection<int, FamilyMember>  $members  nœuds d'une même famille, personnages chargés
     * @return array{nodes: list<array<string, mixed>>, edges: list<array<string, mixed>>, width: int, height: int, roots: list<int>}
     */
    public static function layout(Collection $members): array
    {
        $byId = $members->keyBy('id');

        if ($byId->isEmpty()) {
            return ['nodes' => [], 'edges' => [], 'width' => 0, 'height' => 0, 'roots' => []];
        }

        $parentsOf = fn (FamilyMember $member): array => array_values(array_filter(
            [$member->parent_id, $member->second_parent_id],
            fn (?int $id): bool => $id !== null && $byId->has($id),
        ));

        // Générations : un enfant est sous ses parents ; un conjoint rejoint la rangée de l'autre.
        $generation = $byId->map(fn (): int => 0)->all();
        for ($pass = 0; $pass < $byId->count() + 2; $pass++) {
            $changed = false;
            foreach ($byId as $id => $member) {
                $wanted = $generation[$id];
                foreach ($parentsOf($member) as $parentId) {
                    $wanted = max($wanted, $generation[$parentId] + 1);
                }
                if ($member->spouse_id && $byId->has($member->spouse_id)) {
                    $wanted = max($wanted, $generation[$member->spouse_id]);
                }
                if ($wanted !== $generation[$id] && $wanted < 50) {
                    $generation[$id] = $wanted;
                    $changed = true;
                }
            }
            if (! $changed) {
                break;
            }
        }

        // Ordre dans chaque rangée : parcours en profondeur depuis les racines.
        $childrenOf = [];
        foreach ($byId as $id => $member) {
            $parents = $parentsOf($member);
            if ($parents !== []) {
                $childrenOf[$parents[0]][] = $id;
            }
        }

        $order = [];
        $visited = [];
        $visit = function (int $id) use (&$visit, &$order, &$visited, $byId, $childrenOf, $generation): void {
            if (isset($visited[$id])) {
                return;
            }
            $visited[$id] = true;
            $order[$generation[$id]][] = $id;

            $member = $byId[$id];
            $spouseId = $member->spouse_id;
            if ($spouseId && $byId->has($spouseId) && ! isset($visited[$spouseId]) && $generation[$spouseId] === $generation[$id]) {
                $visited[$spouseId] = true;
                $order[$generation[$id]][] = $spouseId;
            }

            $children = array_merge($childrenOf[$id] ?? [], $spouseId ? ($childrenOf[$spouseId] ?? []) : []);
            usort($children, fn (int $a, int $b): int => [$byId[$a]->sort_order, $a] <=> [$byId[$b]->sort_order, $b]);
            foreach ($children as $childId) {
                $visit($childId);
            }
        };

        $roots = $byId->filter(fn (FamilyMember $member): bool => $parentsOf($member) === [])
            ->sortBy(fn (FamilyMember $member): array => [$generation[$member->id], $member->sort_order, $member->id])
            ->keys()
            ->all();

        foreach ($roots as $rootId) {
            $visit($rootId);
        }
        foreach ($byId->keys() as $id) {
            $visit($id);
        }

        ksort($order);
        $widest = max(array_map('count', $order));
        $width = self::PADDING * 2 + $widest * self::NODE_WIDTH + ($widest - 1) * self::GAP_X;
        $height = self::PADDING * 2 + count($order) * self::NODE_HEIGHT + (count($order) - 1) * self::GAP_Y;

        $positions = [];
        foreach (array_values($order) as $row => $ids) {
            $rowWidth = count($ids) * self::NODE_WIDTH + (count($ids) - 1) * self::GAP_X;
            $start = (int) round(($width - $rowWidth) / 2);
            foreach ($ids as $index => $id) {
                $positions[$id] = [
                    'x' => $start + $index * (self::NODE_WIDTH + self::GAP_X),
                    'y' => self::PADDING + $row * (self::NODE_HEIGHT + self::GAP_Y),
                ];
            }
        }

        $nodes = [];
        foreach ($positions as $id => $position) {
            $member = $byId[$id];
            $nodes[] = [
                'id' => $id,
                'x' => $position['x'],
                'y' => $position['y'],
                'name' => $member->displayName(),
                'note' => $member->note,
                'played' => $member->isPlayed(),
                'deceased' => $member->is_deceased,
                'url' => $member->isPlayed() ? route('characters.show', $member->character) : null,
                'parents' => $parentsOf($member),
                'spouse' => $member->spouse_id && $byId->has($member->spouse_id) ? $member->spouse_id : null,
            ];
        }

        $edges = [];
        $seenCouples = [];
        foreach ($nodes as $node) {
            foreach ($node['parents'] as $parentId) {
                $from = $positions[$parentId];
                $x1 = $from['x'] + self::NODE_WIDTH / 2;
                $y1 = $from['y'] + self::NODE_HEIGHT;
                $x2 = $node['x'] + self::NODE_WIDTH / 2;
                $y2 = $node['y'];
                $mid = $y1 + (int) round(self::GAP_Y / 2);
                $edges[] = ['kind' => 'parent', 'from' => $parentId, 'to' => $node['id'], 'd' => "M{$x1} {$y1}V{$mid}H{$x2}V{$y2}"];
            }

            if ($node['spouse'] !== null) {
                $pair = [min($node['id'], $node['spouse']), max($node['id'], $node['spouse'])];
                $key = implode('-', $pair);
                $a = $positions[$pair[0]];
                $b = $positions[$pair[1]];
                if (! isset($seenCouples[$key]) && $a['y'] === $b['y']) {
                    $seenCouples[$key] = true;
                    [$left, $right] = $a['x'] < $b['x'] ? [$a, $b] : [$b, $a];
                    $y = $left['y'] + (int) round(self::NODE_HEIGHT / 2);
                    $x1 = $left['x'] + self::NODE_WIDTH;
                    $x2 = $right['x'];
                    $edges[] = ['kind' => 'spouse', 'from' => $pair[0], 'to' => $pair[1], 'd' => "M{$x1} {$y}H{$x2}"];
                }
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges, 'width' => $width, 'height' => $height, 'roots' => array_values($roots)];
    }
}
