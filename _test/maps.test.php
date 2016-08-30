<?php

/**
 * General tests for the imagemap plugin
 *
 * @group plugin_imagemap
 * @group plugins
 */
class imagemap_maps_test extends DokuWikiTest {

    public function setUp() {
        parent::setUp();

    }

    protected $pluginsEnabled = array('imagemapping');

    public function test_simple_map() {
        $parser_response = p_get_instructions('{{map>:512px-catstalkprey.jpg|Bild1422436366012}}   * [[foo|bar@ 354,185,437,251]] {{<map}}');
        $expected_response = array(
            0 => array(
                0 => 'document_start',
                1 => Array(),
                2 => 0,
            ),
            1 => array(
                0 => 'plugin',
                1 => array(
                    0 => 'imagemapping',
                    1 => array(
                        0 => 1,
                        1 => 'internalmedia',
                        2 => ':512px-catstalkprey.jpg',
                        3 => 'Bild1422436366012',
                        4 => 'bild1422436366012',
                        5 => '',
                        6 => '',
                        7 => '',
                        8 => 'cache',
                    ),

                    2 => 1,
                    3 => '{{map>:512px-catstalkprey.jpg|Bild1422436366012}}',
                ),

                2 => 1,
            ),

            2 => array(
                0 => 'plugin',
                1 => array(
                    0 => 'imagemapping',
                    1 => array(
                        0 => 2,
                        1 => 'area',
                        2 => 'rect',
                        3 => '354,185,437,251',
                        4 => 'internallink',
                        5 => 'bar',
                        6 => 'foo',
                        7 => '',
                    ),

                    2 => 2,
                ),

                2 => 1,
            ),

            3 => Array
            (
                0 => 'plugin',
                1 => Array
                (
                    0 => 'imagemapping',
                    1 => Array
                    (
                        0 => 2,
                        1 => 'divstart',
                    ),

                    2 => 2,
                ),

                2 => 1,
            ),

            4 => Array
            (
                0 => 'cdata',
                1 => Array
                (
                    0 =>"\n",

                ),

                2 => 50,
            ),

            5 => Array
            (
                0 => 'plugin',
                1 => Array
                (
                    0 => 'imagemapping',
                    1 => Array
                    (
                        0 => '3',
                        1 => '   * ',
                        'title' =>null,
                    ),

                    2 => '3',
                    3 => '   * ',
                ),

                2 => 50,
            ),

            6 => Array
            (
                0 => 'internallink',
                1 => Array
                (
                    0 => 'foo',
                    1 => 'bar',
                ),

                2 => 55,
            ),

            7 => Array
            (
                0 => 'plugin',
                1 => Array
                (
                    0 => 'imagemapping',
                    1 => Array
                    (
                        0 => 3,
                        1 =>' ',
                        'title' =>null,
                    ),

                    2 => 3,
                    2 => 3,
                    3 =>' ',
                ),

                2 => 83,
            ),

            8 => Array
            (
                0 => 'plugin',
                1 => Array
                (
                    0 => 'imagemapping',
                    1 => Array
                    (
                        0 => 2,
                        1 => 'divend',
                    ),

                    2 => 2,
                ),

                2 => 83,
            ),

            9 => Array
            (
                0 => 'plugin',
                1 => Array
                (
                    0 => 'imagemapping',
                    1 => Array
                    (
                        0 => 4,
                    ),

                    2 => 4,
                    3 => '{{<map}}',
                ),

                2 => 84,
            ),

            10 => Array
            (
                0 => 'document_end',
                1 => Array
                (
                ),

                2 => 84,
            ),

        );
        $this->assertEquals($expected_response, $parser_response);
    }
}
