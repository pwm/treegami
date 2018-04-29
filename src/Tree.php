<?php
declare(strict_types=1);

namespace Pwm\Treegami;

use Closure;

final class Tree
{
    /** @var null|mixed */
    private $node;
    /** @var Tree[] */
    private $children;

    public function __construct($node = null, array $children = [])
    {
        $this->node = $node;
        $this->children = $children;
    }

    // (b -> (a, [b])) -> b -> Tree a
    public static function unfold(callable $f, $seed): Tree
    {
        $unfold = function (callable $f) use (&$unfold): Closure {
            return function ($seed) use (&$unfold, $f): Tree {
                [$node, $remaining] = $f($seed);
                return new Tree($node, \array_map($unfold($f), $remaining));
            };
        };
        [$node, $remaining] = $f($seed);
        return new Tree($node, \array_map($unfold($f), $remaining));
    }

    // (a -> [b] -> b) -> Tree a -> b
    public function fold(callable $f)
    {
        $fold = function (Tree $tree) use (&$fold, $f) {
            return $f($tree->node, \array_map($fold, $tree->children));
        };
        return $fold($this);
    }

    // (a -> b) -> Tree a -> Tree b
    public function map(callable $f): Tree
    {
        return $this->fold(function ($node, array $children) use ($f): Tree {
            return new Tree($f($node), $children);
        });
    }
}
