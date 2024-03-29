<?php

namespace dokuwiki\plugin\imagemap\test;

/**
 * General tests for the imagemap plugin
 *
 * @group plugin_imagemap
 * @group plugins
 */
class MapsTest extends \DokuWikiTest
{

    protected $pluginsEnabled = ['imagemapping'];

    public function test_simple_map()
    {
        $parser_response = p_get_instructions('{{map>:512px-catstalkprey.jpg|Bild1422436366012}}   * [[foo|bar@ 354,185,437,251]] {{<map}}');
        $expected_response = [
            0 => [
                0 => 'document_start',
                1 => [],
                2 => 0,
            ],
            1 => [
                0 => 'plugin',
                1 => [
                    0 => 'imagemapping',
                    1 => [
                        0 => DOKU_LEXER_ENTER,
                        1 => 'internalmedia',
                        2 => ':512px-catstalkprey.jpg',
                        3 => 'Bild1422436366012',
                        4 => 'bild1422436366012',
                        5 => '',
                        6 => '',
                        7 => '',
                        8 => 'cache',
                    ],

                    2 => DOKU_LEXER_ENTER,
                    3 => '{{map>:512px-catstalkprey.jpg|Bild1422436366012}}',
                ],

                2 => 1,
            ],

            2 => [
                0 => 'plugin',
                1 => [
                    0 => 'imagemapping',
                    1 => [
                        0 => DOKU_LEXER_MATCHED,
                        1 => 'area',
                        2 => 'rect',
                        3 => '354,185,437,251',
                        4 => 'internallink',
                        5 => 'bar',
                        6 => 'foo',
                        7 => '',
                    ],

                    2 => DOKU_LEXER_MATCHED,
                ],

                2 => 1,
            ],

            3 =>
                [
                    0 => 'plugin',
                    1 =>
                        [
                            0 => 'imagemapping',
                            1 =>
                                [
                                    0 => DOKU_LEXER_MATCHED,
                                    1 => 'divstart',
                                ],

                            2 => DOKU_LEXER_MATCHED,
                        ],

                    2 => 1,
                ],

            4 =>
                [
                    0 => 'cdata',
                    1 =>
                        [
                            0 => "\n",

                        ],

                    2 => 50,
                ],

            5 =>
                [
                    0 => 'plugin',
                    1 =>
                        [
                            0 => 'imagemapping',
                            1 =>
                                [
                                    0 => DOKU_LEXER_UNMATCHED,
                                    1 => '   * ',
                                ],

                            2 => DOKU_LEXER_UNMATCHED,
                            3 => '   * ',
                        ],

                    2 => 50,
                ],

            6 =>
                [
                    0 => 'internallink',
                    1 =>
                        [
                            0 => 'foo',
                            1 => 'bar',
                        ],

                    2 => 55,
                ],

            7 =>
                [
                    0 => 'plugin',
                    1 =>
                        [
                            0 => 'imagemapping',
                            1 =>
                                [
                                    0 => DOKU_LEXER_UNMATCHED,
                                    1 => ' ',
                                ],

                            2 => DOKU_LEXER_UNMATCHED,
                            3 => ' ',
                        ],

                    2 => 83,
                ],

            8 =>
                [
                    0 => 'plugin',
                    1 =>
                        [
                            0 => 'imagemapping',
                            1 =>
                                [
                                    0 => DOKU_LEXER_MATCHED,
                                    1 => 'divend',
                                ],

                            2 => DOKU_LEXER_MATCHED,
                        ],

                    2 => 83,
                ],

            9 =>
                [
                    0 => 'plugin',
                    1 =>
                        [
                            0 => 'imagemapping',
                            1 =>
                                [
                                    0 => DOKU_LEXER_EXIT,
                                ],

                            2 => DOKU_LEXER_EXIT,
                            3 => '{{<map}}',
                        ],

                    2 => 84,
                ],

            10 =>
                [
                    0 => 'document_end',
                    1 =>
                        [
                        ],

                    2 => 84,
                ],

        ];
        $this->assertEquals($expected_response, $parser_response);
    }
}
