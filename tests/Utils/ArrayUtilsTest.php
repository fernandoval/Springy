<?php
/** \file
 *  Springy.
 *
 *  \brief      Test case for Classe de Utilidades para Manipulação de Arrays.
 *  \copyright  Copyright (c) 2007-2015 Fernando Val
 *  \author     Allan Marques - allan.marques@ymail.com
 *  \warning    Este arquivo é parte integrante do framework e não pode ser omitido
 *  \version    0.1.1
 *  \ingroup    tests
 */
use Springy\Utils\ArrayUtils;

class ArrayUtilsTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->arrayUtils = new ArrayUtils();
        $this->data = [
            ['key1' => 'val1', 'key2' => 'val2'],
            [
                ['name' => 'Name 1', 'language' => 'php'],
                ['name' => 'Name 2', 'language' => 'python'],
                ['name' => 'Name 3', 'language' => 'ruby'],
            ],
            [2, 14, 5, 56, 74, 36, 23],
            [
                'config' => [
                    'db' => [
                        'mysql' => [
                            'name'  => 'mysql',
                            'login' => 'A login',
                            'pass'  => 'A password',
                        ],
                        'postgre' => [
                            'name'  => 'postgre',
                            'login' => 'A login',
                            'pass'  => 'A password',
                        ],
                    ],
                    'session' => [
                        'type'    => 'mysql',
                        'expires' => 3600,
                    ],
                ],
            ],
        ];
    }

    public function testAddsANewValueOnlyIfKeyIsNotAlreadySet()
    {
        $data = $this->data[0];

        $expected = ['key1' => 'val1', 'key2' => 'val2', 'key3' => 'val3'];

        $actual = $this->arrayUtils->add($expected, 'key3', 'val3');

        $this->assertEquals($expected, $actual);

        $actual = $this->arrayUtils->add($expected, 'key3', 'changed');

        $this->assertEquals($expected['key3'], $actual['key3']);
    }

    public function testMakesAnArrayWithAFilterFunction()
    {
        $data = $this->data[0];

        $expected = ['key1' => 'filtered', 'key2' => 'filtered'];

        $actual = $this->arrayUtils->make($data, function ($key, $val) {
            $val = 'filtered';

            return [$key, $val];
        });

        $this->assertEquals($expected, $actual);
    }

    public function testPluckElementsOfAnArray()
    {
        $data = $this->data[1];

        $expected = ['php', 'python', 'ruby'];

        $actual = $this->arrayUtils->pluck($data, 'language');

        $this->assertEquals($expected, $actual);

        $expected = ['Name 1' => 'php', 'Name 2' => 'python', 'Name 3' => 'ruby'];

        $actual = $this->arrayUtils->pluck($data, 'language', 'name');

        $this->assertEquals($expected, $actual);
    }

    public function testSplitsAnArrayIntoTwo()
    {
        $data = $this->data[0];

        $expectedKeys = ['key1', 'key2'];
        $expectedValues = ['val1', 'val2'];

        list($actualKeys, $actualValues) = $this->arrayUtils->split($data);

        $this->assertEquals($expectedKeys, $actualKeys);
        $this->assertEquals($expectedValues, $actualValues);
    }

    public function testReturnsOnlyTheValuesThatMatchesTheGivenKeys()
    {
        $data = $this->data[0];

        $expected = ['key2' => 'val2'];

        $actual = $this->arrayUtils->only($data, ['key2']);

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsEveryValueExcpectTheOnesThatMatchesTheGivenKeys()
    {
        $data = $this->data[0];

        $expected = ['key2' => 'val2'];

        $actual = $this->arrayUtils->except($data, ['key1']);

        $this->assertEquals($expected, $actual);
    }

    public function testSortsTheArrayValuesUsingAUserDefinedFunction()
    {
        $data = $this->data[2];

        $expected = [2, 5, 14, 23, 36, 56, 74];

        $actual = $this->arrayUtils->sort($data, function ($val1, $val2) {
            if ($val1 == $val2) {
                return 0;
            }

            return ($val1 < $val2) ? -1 : 1;
        });

        $this->assertEquals($expected, array_values($actual));
    }

    public function testReturnsTheFirstValueThatPassTheTestFunction()
    {
        $data = $this->data[1];

        $expected = ['name' => 'Name 2', 'language' => 'python'];

        $actual = $this->arrayUtils->firstThatPasses($data, function ($key, $val) {
            return $val['language'] == 'python';
        });

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsTheLastValueThatPassTheTestFunction()
    {
        $data = $this->data[2];

        $expected = 74;

        $actual = $this->arrayUtils->lastThatPasses($data, function ($key, $val) {
            return $val > 50;
        });

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsAllTheValuesThatPAssesTheTestFunction()
    {
        $data = $this->data[2];

        $expected = [56, 74];

        $actual = $this->arrayUtils->allThatPasses($data, function ($key, $val) {
            return $val > 50;
        });

        $this->assertEquals($expected, array_values($actual));
    }

    public function testItFlattensAMultidimensionalArray()
    {
        $data = $this->data[3];

        $expected = ['mysql', 'A login', 'A password', 'postgre', 'A login', 'A password'];

        $actual = $this->arrayUtils->flatten($data['config']['db']);

        $this->assertEquals($expected, $actual);
    }

    public function testFlattensTheArrayKeepingTheHierarchyUsingTheDotNotation()
    {
        $data = $this->data[3];

        $flattenedArray = $this->arrayUtils->dottedMake($data);

        $expected = $data['config']['db']['mysql']['name'];

        $actual = $flattenedArray['config.db.mysql.name'];

        $this->assertEquals($expected, $actual);
    }

    public function testGetsAValueFromAMultidimensionalArrayUsingTheDotNotationWihoutChangingTheArray()
    {
        $data = $this->data[3];

        $expected = $data['config']['session']['type'];

        $actual = $this->arrayUtils->dottedGet($data, 'config.session.type');

        $this->assertEquals($expected, $actual);
    }

    public function testPullsAValueFromAMultidimensionalArrayUsingTheDotNotationRemovingItFromTheArray()
    {
        $data = $this->data[3];

        $expected = $data['config']['session']['type'];

        $actual = $this->arrayUtils->dottedPull($data, 'config.session.type');

        $this->assertEquals($expected, $actual);

        $this->assertFalse(isset($data['config']['session']['type']));
    }

    public function testSetsOrChangesAValueFromAMultidimensionalArrayUsingTheDotNotation()
    {
        $data = $this->data[3];

        //Changing
        $expected = 'cookie';

        $this->arrayUtils->dottedSet($data, 'config.session.type', $expected);

        $actual = $data['config']['session']['type'];

        $this->assertEquals($expected, $actual);

        //Adding
        $expected = 'redis';

        $this->arrayUtils->dottedSet($data, 'config.cache.driver.type', $expected);

        $actual = $data['config']['cache']['driver']['type'];

        $this->assertEquals($expected, $actual);
    }

    public function testUnsetsAValueFromAMuiltidimensionalArrayUsingTheDotNotation()
    {
        $data = $this->data[3];

        $this->arrayUtils->dottedUnset($data, 'config.db.postgre');

        $this->assertFalse(isset($data['config']['db']['postgre']));
    }

    public function testFetchesAPartOfAnArrayAndFlattenItUsingTheDotNotation()
    {
        $data = $this->data[3];

        $expected = [$data['config']['db']['mysql']];

        $actual = $this->arrayUtils->dottedFetch($data, 'db.mysql');

        $this->assertEquals($expected, $actual);
    }

    public function testReturnsAArrayUtilsInstance()
    {
        $this->assertInstanceOf(
            get_class(new ArrayUtils()),
            ArrayUtils::newInstance()
        );
    }
}
