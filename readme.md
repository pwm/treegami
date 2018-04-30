# Treegami

[![Build Status](https://travis-ci.org/pwm/treegami.svg?branch=master)](https://travis-ci.org/pwm/treegami)
[![codecov](https://codecov.io/gh/pwm/treegami/branch/master/graph/badge.svg)](https://codecov.io/gh/pwm/treegami)
[![Maintainability](https://api.codeclimate.com/v1/badges/25356a7f11c642ee8ac5/maintainability)](https://codeclimate.com/github/pwm/treegami/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/25356a7f11c642ee8ac5/test_coverage)](https://codeclimate.com/github/pwm/treegami/test_coverage)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

Treegami is a small library for mapping and folding trees of arbitrary shape. As programmers we often work with trees, either in the open or in hiding, eg. when the're camouflaging as a json document. Ultimately we want to work with these structures by transforming them and Treegami is here to help with that need. The name Treegami is a [portmanteau](https://en.wikipedia.org/wiki/Portmanteau) of the words tree and [origami](https://en.wikipedia.org/wiki/Origami).


## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
* [How it works](#how-it-works)
* [Tests](#tests)
* [Changelog](#changelog)
* [Licence](#licence)

## Requirements

PHP 7.1+

## Installation

    $ composer require pwm/treegami

## Usage

As a start, let's just create a tree manually:

```php
$tree = new Tree(
    'first', [ // node has 3 children
        new Tree('second', [ // Node has 2 children
            new Tree('third'),  // children are optional and defaults to []
            new Tree(), // node value is optional and defaults to null
        ]),
        new Tree('fourth', []), // explicitly saying no children
        new Tree('fifth', [
            new Tree(null, []) // explicitly saying no node value or children
        ]),
    ]
);
```

Which results in the following tree:

```
        ____first____
       /      |      \
    second  fourth  fifth
    /    \            |
 third   null        null
```

Let's map this tree using a function that maps a string to its length and null to 0:

```php
$mappedTree = $tree->map(function (?string $node): int {
    return is_string($node)
        ? strlen($node)
        : 0;
});
```

Which results in the following tree:

```
        ____5____
       /    |    \
      6     6     5
     / \          |
    5   0         0
```

Now let's fold this tree to the sum of its node values:

```php
$foldedTree = $mappedTree->fold(function (int $node, array $acc): int {
    return $node + array_sum($acc);
});

assert($foldedTree === 27); // true
```

Finally an example of unfolding a tree from a seed value:

```php
$tree = Tree::unfold(function (int $x): array {
    return $x < 2 ** 3
        ? [$x, range(2 * $x, 2 * $x + 1)]
        : [$x, []];
}, 1);
```

Which results in the following full binary tree:

```
        ______1______
       /             \
    __2__           __3__
   /     \         /     \
  4       5       6       7
 / \     / \     / \     / \
8   9  10  11  12  13  14  15
```
 
## How it works

Some (most?) of the readers will be familiar with the higher-order functions `map` and `fold` on lists. In PHP they are called `array_map` and `array_reduce`, respectively.

As a quick refresher: the function `map` maps a list into another list by applying a function to its elements, while the function `fold` "folds up" a list starting from a seed value by applying a function to its elements accumulating them into a final value. For example if we have a function  `addOne`, which adds one to a number, then `map(addOne, [1,2,3,4,5])` results in `[2,3,4,5,6]`. If we then have a function `add`, which adds 2 numbers together, then `fold(add, 0, [2,3,4,5,6])`, `0` being the seed value, results in `0+2+3+4+5+6 = 20`.

However `map` and `fold` is not specific to lists. In fact many different structures can be mapped and folded, including trees. Mapping a function over a tree results in another tree of the same shape where each node is the result of the function applied to it. Folding a tree using a function means traversing the tree, applying the function to its nodes and accumulating them into a final value.

For example if we have the following tree:

```
        ______1______
       /             \
    __2__           __3__
   /     \         /     \
  4       5       6       7
         / \       \
        8   9      10
```
then mapping `addOne` over it results in:

```
        ______2______
       /             \
    __3__           __4__
   /     \         /     \
  5       6       7       8
         / \       \
        9  10      11
```
In turn, folding this tree with `add`, using the root value `2` as the seed value, results in: `2+3+4+5+6+7+8+9+10+11 = 65`.

In Treegami the seed value for `fold` by default is the value of the root node and an empty tree means a tree with no children and null as the node value.

Depending in which order we combine the current value with the accumulated value in our fold function we get different traversal orders. Specifically, if we prepend the current value to the accumulated value we get preorder traversal, while if we append the current value to the accumulated value we get postorder traversal.

In general `fold` does not need to "collapse" the structure into some scalar value, like an integer. We can just as easily "fold" the structure into another structure. This is exactly how `map` works in Treegami, meaning that `map` is expressed via `fold`.

`unfold` on the other hand is the "opposite" (or dual, to use the correct terminology) of `fold`. It takes a function and a seed value and "unfolds" that seed value into a structure, in our case into a tree. What's important to understand is that the function that unfolds the seed returns a pair of values: a node in our tree and a list of seed values, that will be used for subsequent iterations of `unfold`, until it returns an empty list, essentially terminating the iteration.

For the curious and brave out there: `fold` is a [Catamorphism](https://en.wikipedia.org/wiki/Catamorphism) while its dual `unfold` is an [Anamorphism](https://en.wikipedia.org/wiki/Anamorphism). You can read more about these terms in the paper [Functional programming with bananas, lenses, envelopes and barbed wire](https://maartenfokkinga.github.io/utwente/mmf91m.pdf).

## Tests

	$ vendor/bin/phpunit
	$ composer phpcs
	$ composer phpstan

## Changelog

[Click here](changelog.md)

## Licence

[MIT](LICENSE)
