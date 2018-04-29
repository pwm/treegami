<?php
declare(strict_types=1);

namespace Pwm\Treegami;

use PHPUnit\Framework\TestCase;

final class TreeTest extends TestCase
{
    /**
     * @test
     */
    public function creation(): void
    {
        $tree = new Tree(
            'node1', [
                new Tree('node2', [
                    new Tree(),
                    new Tree(),
                ]),
                new Tree('node3'), // children are optional and default to []
                new Tree(), // node is optional and defaults to null
            ]
        );
        self::assertInstanceOf(Tree::class, $tree);
    }

    /**
     * @test
     */
    public function unfold_and_fold_preserving_structure(): void
    {
        // (b -> (a, [b]))
        $unfoldSeedToTree = function (int $x): array {
            // We're creating a full binary tree of height 4:
            //         ______1______
            //        /             \
            //     __2__           __3__
            //    /     \         /     \
            //   4       5       6       7
            //  / \     / \     / \     / \
            // 8   9  10  11  12  13  14  15
            return $x < 2 ** 3
                ? [$x, range(2 * $x, 2 * $x + 1)]
                : [$x, []];
        };

        // (a -> [b] -> b)
        $foldTreeToMap = function ($x, array $acc) {
            return count($acc) > 0
                ? [$x, [$acc[0], $acc[1]]]
                : $x;
        };

        // Unfold a binary tree from the value 1 and then fold it into a map
        // showing how fold preserves the tree structure.
        self::assertSame(
            [1, [[2, [[4, [8, 9]], [5, [10, 11]]]], [3, [[6, [12, 13]], [7, [14, 15]]]]]],
            Tree::unfold($unfoldSeedToTree, 1)->fold($foldTreeToMap)
        );
    }

    /**
     * @test
     */
    public function unfold_map_and_fold_down_to_a_single_value(): void
    {
        // Our full binary tree from earlier, unfolded from the number 1
        $tree = Tree::unfold(function (int $x): array {
            return $x < 2 ** 3
                ? [$x, range(2 * $x, 2 * $x + 1)]
                : [$x, []];
        }, 1);

        // (a -> b)
        $mapIntTreeToStringTree = function ($x) {
            return str_repeat('x', 2 * $x);
        };

        // (a -> [b] -> b)
        $foldTreeToLengthSum = function ($x, array $acc) {
            return \strlen($x) + \array_sum($acc);
        };

        // We map the same binary tree unfolded form the number 1 into another tree where nodes are strings
        // of 'x'-s, each being twice as long as the number from the original tree.
        // We then fold it into a value which is the sum of all the string lengths.
        // (formula: n * (n + 1) / 2 * 2 = n * (n + 1) | n = 15)
        self::assertSame(240, $tree->map($mapIntTreeToStringTree)->fold($foldTreeToLengthSum));
    }

    /**
     * @test
     */
    public function preorder_and_postorder_traversals(): void
    {
        // Our full binary tree from earlier, unfolded from the number 1
        $tree = Tree::unfold(function (int $x): array {
            return $x < 2 ** 3
                ? [$x, range(2 * $x, 2 * $x + 1)]
                : [$x, []];
        }, 1);

        // prepending the value to the accumulator gives us preorder traversal
        $preOrderTraversal = function ($x, $acc) {
            return count($acc) > 0
                ? $x . ',' . implode(',', $acc)
                : $x;
        };

        // appending the value to the accumulator gives us postorder traversal
        $postOrderTraversal = function ($x, $acc) {
            return count($acc) > 0
                ? implode(',', $acc) . ',' . $x
                : $x;
        };

        self::assertSame('1,2,4,8,9,5,10,11,3,6,12,13,7,14,15', $tree->fold($preOrderTraversal));
        self::assertSame('8,9,4,10,11,5,2,12,13,6,14,15,7,3,1', $tree->fold($postOrderTraversal));
    }

    /**
     * @test
     */
    public function json_processing(): void
    {
        // Helper function to massage a map to an unfold friendly list, ie. a list with (key, value) tuples.
        // Eg.: ['k_a' => 'v_a', 'k_b' => 'v_b'] becomes [["k_a","v_a"],["k_b","v_b"]]
        function mapToList(array $map): array
        {
            $list = [];
            foreach ($map as $k => $v) {
                $list[] = \is_array($v)
                    ? [$k, mapToList($v)]
                    : [$k, $v];
            }
            return $list;
        }

        // (b -> (a, [b]))
        $unfoldJsonToTree = function ($seed) {
            [$key, $data] = $seed;
            return \is_array($data)
                ? [[$key, null], $data]
                : [[$key, $data], []];
        };

        // (a -> [b] -> b)
        $foldJsonTreeToMap = function ($node, array $acc) {
            [$key, $data] = $node;
            return $data !== null
                ? [$key => $data]
                : [
                    $key => count($acc) > 0
                        ? array_merge(...$acc)
                        : [],
                ];
        };

        // Random json generated by https://www.json-generator.com/
        $jsonString = '[{"_id":"5ae31bfc2b7c587dc9163efc","index":0,"guid":"d0244519-091a-41bb-9fb8-62e0e587a15e","isActive":true,"balance":"$3,927.12","picture":"http://placehold.it/32x32","age":34,"eyeColor":"brown","name":"Rhea Castro","gender":"female","company":"ARTIQ","email":"rheacastro@artiq.com","phone":"+1 (859) 414-3281","address":"677 Beekman Place, Downsville, New Mexico, 9044","about":"Incididunt id veniam sint ea nisi velit elit. Est nisi id sunt enim mollit sint amet sit dolore ad incididunt et sunt labore. Dolore enim eiusmod occaecat occaecat proident eu ex culpa deserunt mollit. Non commodo qui est minim excepteur. Sint non amet incididunt amet incididunt aliqua non officia. Id minim ut ea exercitation veniam.\r\n","registered":"2018-02-27T06:24:58 -00:00","latitude":30.362848,"longitude":-31.189619,"tags":["velit","exercitation","pariatur","dolor","amet","adipisicing","ullamco"],"friends":[{"id":0,"name":"Alba Christensen"},{"id":1,"name":"Ellis Graham"},{"id":2,"name":"Kramer Houston"}],"greeting":"Hello, Rhea Castro! You have 7 unread messages.","favoriteFruit":"banana"},{"_id":"5ae31bfc46da54493339ac38","index":1,"guid":"80199a1f-9f4b-44ef-ae9a-07c2538376ab","isActive":true,"balance":"$2,459.88","picture":"http://placehold.it/32x32","age":22,"eyeColor":"blue","name":"Sarah Parrish","gender":"female","company":"QABOOS","email":"sarahparrish@qaboos.com","phone":"+1 (943) 418-3204","address":"717 Macdougal Street, Jackpot, Missouri, 5831","about":"Cupidatat do nulla deserunt culpa ut fugiat occaecat quis sit nisi irure. Do occaecat ullamco culpa non fugiat mollit laborum anim veniam et laborum. Culpa veniam veniam duis quis id id culpa occaecat fugiat proident. Consequat sint sunt sit mollit labore nulla exercitation commodo elit incididunt. Eu incididunt sunt occaecat pariatur ex ipsum aliqua ex adipisicing pariatur minim dolor officia elit.\r\n","registered":"2014-01-12T04:09:47 -00:00","latitude":-63.504507,"longitude":115.876153,"tags":["fugiat","dolor","eiusmod","occaecat","quis","sint","aliqua"],"friends":[{"id":0,"name":"Cote Mcconnell"},{"id":1,"name":"Pansy Mayo"},{"id":2,"name":"Colleen Martinez"}],"greeting":"Hello, Sarah Parrish! You have 3 unread messages.","favoriteFruit":"apple"},{"_id":"5ae31bfc56df3720824d8363","index":2,"guid":"d0b158a5-578e-4c19-beff-ad2d48c30f62","isActive":true,"balance":"$3,121.67","picture":"http://placehold.it/32x32","age":24,"eyeColor":"brown","name":"Myers Riley","gender":"male","company":"OVERPLEX","email":"myersriley@overplex.com","phone":"+1 (836) 574-2827","address":"523 Randolph Street, Eggertsville, American Samoa, 5884","about":"Laborum ut esse anim laboris magna id labore tempor ad dolor aute esse. Exercitation pariatur ipsum do dolor irure. Veniam minim laboris enim qui quis non.\r\n","registered":"2014-08-04T02:21:43 -01:00","latitude":33.706597,"longitude":111.19427,"tags":["pariatur","eu","mollit","nostrud","aliquip","id","Lorem"],"friends":[{"id":0,"name":"Leslie Shelton"},{"id":1,"name":"Farrell Morse"},{"id":2,"name":"Celeste Porter"}],"greeting":"Hello, Myers Riley! You have 10 unread messages.","favoriteFruit":"banana"},{"_id":"5ae31bfc5e7187a781fd89b4","index":3,"guid":"2449df0d-08bc-4658-bf6f-f15194046f53","isActive":false,"balance":"$2,769.15","picture":"http://placehold.it/32x32","age":20,"eyeColor":"blue","name":"Maryanne Clark","gender":"female","company":"EVENTAGE","email":"maryanneclark@eventage.com","phone":"+1 (963) 490-2861","address":"227 Leonard Street, Rodman, District Of Columbia, 8306","about":"Ullamco et ad reprehenderit labore consectetur nulla magna. Adipisicing nisi aute quis voluptate sunt. Excepteur deserunt officia veniam irure pariatur do occaecat velit duis dolor culpa excepteur. Do id exercitation anim quis esse deserunt sint culpa ex proident occaecat consequat. Do eiusmod tempor duis dolor. Reprehenderit ut proident ad aliquip qui anim. Occaecat pariatur commodo quis aliqua adipisicing aliqua occaecat ut.\r\n","registered":"2016-07-29T06:59:42 -01:00","latitude":-65.550797,"longitude":-121.668205,"tags":["laborum","duis","do","fugiat","sit","elit","ipsum"],"friends":[{"id":0,"name":"Kelley Vance"},{"id":1,"name":"Tommie Valencia"},{"id":2,"name":"Trina Merritt"}],"greeting":"Hello, Maryanne Clark! You have 3 unread messages.","favoriteFruit":"strawberry"},{"_id":"5ae31bfc3a0673bfa1fb6e88","index":4,"guid":"6d66bdcf-10f2-40c0-bdec-65981162b14f","isActive":false,"balance":"$3,042.82","picture":"http://placehold.it/32x32","age":37,"eyeColor":"blue","name":"Chase Mcbride","gender":"male","company":"SULTRAX","email":"chasemcbride@sultrax.com","phone":"+1 (968) 491-3672","address":"713 Willow Street, Vincent, North Carolina, 3494","about":"Id reprehenderit amet ipsum ad Lorem quis eiusmod. Adipisicing eu pariatur ipsum irure ea consequat quis. Ea officia pariatur voluptate ea sit id. Ad mollit deserunt aute eiusmod nisi sit incididunt culpa ex. Cillum aliquip magna nulla fugiat adipisicing cupidatat magna incididunt sint elit sit aute. Deserunt eiusmod ipsum nisi amet.\r\n","registered":"2018-04-13T11:55:52 -01:00","latitude":48.852883,"longitude":-61.608519,"tags":["ut","adipisicing","excepteur","mollit","ea","enim","laboris"],"friends":[{"id":0,"name":"Downs Stevens"},{"id":1,"name":"Erin Small"},{"id":2,"name":"Juana Hoffman"}],"greeting":"Hello, Chase Mcbride! You have 1 unread messages.","favoriteFruit":"banana"}]';

        // create the list representation of the json string enveloping it under a single "root" key
        $json = ['root', mapToList(json_decode($jsonString, true))];

        $jsonTree = Tree::unfold($unfoldJsonToTree, $json);

        $jsonMap = $jsonTree->fold($foldJsonTreeToMap);

        // unfolding and then folding gives back the original json
        self::assertSame($jsonString, json_encode($jsonMap['root'], JSON_UNESCAPED_SLASHES));

        // (a -> b)
        $mapToAgeTree = function ($node) {
            [$key, $data] = $node;
            return $key === 'age'
                ? $data
                : null;
        };

        // (a -> [b] -> b)
        $foldToAgeList = function ($node, array $acc) {
            $acc = count($acc) > 0
                ? array_merge(...$acc)
                : [];
            return $node === null
                ? $acc
                : [$node];
        };

        // getting the list of age values from the json
        $ageList = $jsonTree->map($mapToAgeTree)->fold($foldToAgeList);

        self::assertSame([34, 22, 24, 20, 37], $ageList);
    }
}
