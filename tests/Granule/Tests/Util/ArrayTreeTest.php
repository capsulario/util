<?php

namespace Granule\Tests\Util;

use Granule\Util\ArrayTree;
use Granule\Util\MutableArrayTree;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Granule\Util\ArrayTree
 * @coversDefaultClass Granule\Util\MutableArrayTree
 */
class ArrayTreeTest extends TestCase {

    public function provider(): array {
        return [
            [
                [
                    'var 0' => 'string var 0',
                    'var 1' => [
                        ['var 1.0.0', 'var 1.0.1', 'var 1.0.2'],
                        ['var 1.1.0', 'var 1.1.1', 'var 1.1.2']
                    ],
                    'var 2' => 2,
                    'var 3' => false,
                    'var 4' => '',
                    'var 5' => [
                        'var 5.0' => 'value string 5.0',
                        'var 5.1' => 5.1,
                        'var 5.2' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * @param array $data
     *
     * @test
     * @dataProvider provider
     * @covers ::offsetGet
     */
    public function data_should_be_accessible_by_key(array $data): void {
        $tree = ArrayTree::fromArray($data);

        $this->assertEquals('string var 0', $tree['var 0']);
        $this->assertEquals(2, $tree['var 2']);
        $this->assertEquals(false, $tree['var 3']);
        $this->assertInstanceOf(ArrayTree::class, $tree['var 1']);
        $this->assertEquals('var 1.0.1', $data['var 1'][0][1]);
        $this->assertFalse(isset($tree['var 7']));
        $this->assertFalse(array_key_exists('var 7', $tree));
        $this->assertFalse(isset($tree['var 1'][3][1]));
        $this->assertFalse(array_key_exists('var 1.0.5', $tree));
    }

    /**
     * @param array $data
     *
     * @test
     * @dataProvider provider
     * @covers ::offsetGet
     */
    public function data_should_be_accessible_by_path(array $data): void {
        $tree = ArrayTree::fromArray($data);

        $this->assertEquals('var 1.1.1', $tree['var 1.1.1'], 'Numeric path elements failure');
        $this->assertEquals('var 1.0.1', $tree['var 1.0.1'], 'Nullable numeric path elements failure');
        $this->assertEquals('value string 5.0', $tree['var 5.var 5\.0'], 'Character escaping failure');
    }

    /**
     * @param array $data
     *
     * @test
     * @dataProvider provider
     * @covers ::current
     * @covers ::next
     * @covers ::key
     * @covers ::valid
     * @covers ::rewind
     */
    public function it_should_be_iterable(array $data): void {
        $tree = ArrayTree::fromArray($data);

        $index = 0;
        foreach ($tree as $key => $value) {
            $this->assertEquals("var {$index}", $key);
            $this->assertEquals(is_array($data[$key]) ? ArrayTree::fromArray($data[$key]) : $data[$key],  $value);
            $index++;
        }
    }

    /**
     * @param array $data
     *
     * @test
     * @dataProvider provider
     * @covers ::count
     */
    public function it_should_be_countable(array $data): void {
        $tree = ArrayTree::fromArray($data);

        $this->assertEquals(6, count($tree));
        $this->assertEquals(2, count($tree['var 1']));
        $this->assertEquals(3, count($tree['var 1.0']));
    }

    /**
     * @param array $data
     *
     * @test
     * @dataProvider provider
     * @covers ::toImmutable
     * @covers ::toMutable
     */
    public function it_should_be_convertible_to_mutable_and_back(array $data): void {
        $tree = ArrayTree::fromArray($data);
        $mutableTree = MutableArrayTree::fromArray($data);

        $this->assertEquals($tree, $tree->toImmutable());
        $this->assertEquals($tree, $mutableTree->toImmutable());
        $this->assertEquals($mutableTree, $mutableTree->toMutable());
        $this->assertEquals($mutableTree, $tree->toMutable());
    }

    /**
     * @param array $data
     *
     * @test
     * @dataProvider provider
     * @covers ::toArray
     */
    public function it_should_be_convertible_to_array(array $data): void {
        $tree = ArrayTree::fromArray($data);
        $mutableTree = MutableArrayTree::fromArray($data);

        $this->assertEquals($data, $tree->toArray());
        $this->assertEquals($data, $mutableTree->toArray());
    }

    /**
     * @param array $data
     *
     * @test
     * @dataProvider provider
     * @covers ::offsetSet
     * @covers ::offsetUnset
     */
    public function mutable_tree_should_be_mutable(array $data): void {
        $mutableTree = MutableArrayTree::fromArray($data);
        $mutableTree['var 0'] = 'string 000';
        $mutableTree['var 1'][0][1] = 'string 1.0.1 but other one';

        $this->assertEquals('string 000', $mutableTree['var 0']);
        $this->assertEquals('string 1.0.1 but other one', $mutableTree['var 1'][0][1]);
        $this->assertEquals('string 1.0.1 but other one', $mutableTree['var 1.0.1']);

        unset($mutableTree['var 1'][0][1]);
        unset($mutableTree['var 1.1.1']);

        $this->assertFalse(isset($mutableTree['var 1'][0][1]));
        $this->assertFalse(isset($mutableTree['var 1.0.1']));
        $this->assertFalse(isset($mutableTree['var 1.1.1']));
    }
}