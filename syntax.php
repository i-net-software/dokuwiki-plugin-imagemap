<?php

use dokuwiki\Parsing\Handler\CallWriterInterface;
use dokuwiki\plugin\imagemapping\ImageMapHandler;

/**
 * Image Mapping Plugin: Syntax component
 *
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author   Gerry Weißbach <tools@inetsoftware.de>
 * @author   Michael Große <dokuwiki@cosmocode.de>
 * @author   Tom N Harris <tools@inetsoftware.de>
 *
 * Based upon the non public version by Tom N Harris
 */
class syntax_plugin_imagemapping extends DokuWiki_Syntax_Plugin
{

    /** @inheritdoc */
    function getType()
    {
        return 'container';
    }

    /** @inheritdoc */
    function getSort()
    {
        return 316;
    }

    /** @inheritdoc */
    function getPType()
    {
        return 'block';
    }

    /** @inheritdoc */
    function getAllowedTypes()
    {
        return array('formatting', 'substition', 'disabled', 'protected', 'container', 'paragraphs');
    }

    /** @inheritdoc */
    function connectTo($mode)
    {
        $this->Lexer->addEntryPattern('\{\{map>[^\}]+\}\}', $mode, 'plugin_imagemapping');
    }

    /** @inheritdoc */
    function postConnect()
    {
        $this->Lexer->addExitPattern('\{\{<map\}\}', 'plugin_imagemapping');
    }

    /** @inheritdoc */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {
        global $conf;
        global $ID;
        $args = array($state);

        switch ($state) {
            case DOKU_LEXER_ENTER:
                $img = Doku_Handler_Parse_Media(substr($match, 6, -2));
                if ($img['title']) {
                    $mapname = str_replace(':', '', cleanID($img['title']));
                    $mapname = ltrim($mapname, '0123456789._-');
                }
                if (empty($mapname)) {
                    if ($img['type'] == 'internalmedia') {
                        $src = $img['src'];
                        resolve_mediaid(getNS($ID), $src, $exists);
                        $nssep = ($conf['useslash']) ? '[:;/]' : '[:;]';
                        $mapname = preg_replace('!.*' . $nssep . '!', '', $src);
                    } else {
                        $src = parse_url($img['src']);
                        $mapname = str_replace(':', '', cleanID($src['host'] . $src['path'] . $src['query']));
                        $mapname = ltrim($mapname, '0123456789._-');
                    }
                    if (empty($mapname)) {
                        $mapname = 'imagemap' . $pos;
                    }
                }
                $args = [$state, $img['type'], $img['src'], $img['title'], $mapname,
                    $img['align'], $img['width'], $img['height'],
                    $img['cache']];

                $ReWriter = new ImageMapHandler($mapname, $handler->getCallWriter());
                $handler->setCallWriter($ReWriter);
                break;
            case DOKU_LEXER_EXIT:
                // @var ImageMapHandler $ReWriter
                $ReWriter = $handler->getCallWriter();
                $ReWriter->process();
                $handler->setCallWriter($ReWriter->getCallWriter());
                break;
            case DOKU_LEXER_MATCHED:
                break;
            case DOKU_LEXER_UNMATCHED:
                $args[] = $match;
                break;
        }
        return $args;
    }

    /** @inheritdoc */
    function render($format, Doku_Renderer $renderer, $data)
    {
        global $conf;
        global $ID;
        static $has_content = false;
        $state = $data[0];
        if (substr($format, 0, 5) == 'xhtml') {
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    list($state, $type, $src, $title, $name, $align, $width, $height, $cache) = $data;
                    if ($type == 'internalmedia') {
                        resolve_mediaid(getNS($ID), $src, $exists);
                    }
                    $renderer->doc .= '<p>' . DOKU_LF;
                    $src = ml($src, array('w' => $width, 'h' => $height, 'cache' => $cache));
                    $renderer->doc .= ' <img src="' . $src . '" class="media' . $align . ' imap" usemap="#' . $name . '"';
                    if ($align == 'right' || $align == 'left')
                        $renderer->doc .= ' align="' . $align . '"';
                    if (!is_null($title)) {
                        $title = $renderer->_xmlEntities($title);
                        $renderer->doc .= ' title="' . $title . '"';
                        $renderer->doc .= ' alt="' . $title . '"';
                    } else {
                        $renderer->doc .= ' alt=""';
                    }
                    if (!is_null($width))
                        $renderer->doc .= ' width="' . $renderer->_xmlEntities($width) . '"';
                    if (!is_null($height))
                        $renderer->doc .= ' height="' . $renderer->_xmlEntities($height) . '"';
                    $renderer->doc .= ' />' . DOKU_LF;
                    $renderer->doc .= '</p>' . DOKU_LF;
                    $renderer->doc .= '<map name="' . $name . '" id="' . $name . '">' . DOKU_LF;
                    $has_content = false;
                    break;
                case DOKU_LEXER_MATCHED:
                    if ($data[1] == 'area') {
                        @list($state, $match, $shape, $coords, $type, $title, $url, $wiki) = $data;
                        $target = '';
                        switch ($type) {
                            case 'internallink':
                                if ($url === '') $url = $ID;
                                $default = $renderer->_simpleTitle($url);
                                resolve_pageid(getNS($ID), $url, $exists);
                                $title = $renderer->_getLinkTitle($title, $default, $isImg, $url);
                                list($url, $hash) = explode('#', $url, 2);
                                if (!empty($hash)) $hash = $renderer->_headerToLink($hash);
                                $url = wl($url);
                                if ($hash) $url .= '#' . $hash;
                                $target = $conf['target']['wiki'];
                                break;
                            case 'locallink':
                                $title = $renderer->_getLinkTitle($title, $url, $isImg);
                                $url = $renderer->_headerToLink($url);
                                $url = '#' . $url;
                                break;
                            case 'externallink':
                                $title = $renderer->_getLinkTitle($title, $url, $isImg);
                                // url might be an attack vector, only allow registered protocols
                                if (is_null($this->schemes)) $this->schemes = getSchemes();
                                list($scheme) = explode('://', $url);
                                $scheme = strtolower($scheme);
                                if (!in_array($scheme, $this->schemes)) $url = '';
                                $target = $conf['target']['extern'];
                                break;
                            case 'interwikilink':
                                $title = $renderer->_getLinkTitle($title, $url, $isImg);
                                $url = $renderer->_resolveInterWiki($wiki, $url);
                                if (strpos($url, DOKU_URL) === 0)
                                    $target = $conf['target']['wiki'];
                                else
                                    $target = $conf['target']['interwiki'];
                                break;
                            case 'emaillink':
                                $url = $renderer->_xmlEntities($url);
                                $url = obfuscate($url);
                                $title = $renderer->_getLinkTitle($title, $url, $isImg);
                                if ($conf['mailguard'] == 'visible')
                                    $url = rawurlencode($url);
                                $url = 'mailto:' . $url;
                                break;
                            case 'windowssharelink':
                                $title = $renderer->_getLinkTitle($title, $url, $isImg);
                                $url = str_replace('\\', '/', $url);
                                $url = 'file:///' . $url;
                                $target = $conf['target']['windows'];
                                break;
                            case 'internalmedia':
                                list($url, $hash) = explode('#', $url, 2);
                                resolve_mediaid(getNS($ID), $url, $exists);
                                $title = $renderer->_media($url, $title, null, null, null, null, false);
                                $url = ml($url, ($extra[1] == 'direct'));
                                if ($hash)
                                    $url .= '#' . $hash;
                                break;
                        }
                        if ($url) {
                            $renderer->doc .= '<area href="' . $url . '"';
                            if (!empty($target))
                                $renderer->doc .= ' target="' . $target . '"';
                            $renderer->doc .= ' title="' . $title . '" alt="' . $title . '"';

                            $renderer->doc .= ' shape="' . $shape . '" coords="' . $coords . '"/>';
                        }
                    } elseif ($data[1] == 'divstart') {
                        $renderer->doc .= DOKU_LF . '<div class="imapcontent">' . DOKU_LF;
                        $has_content = true;
                    } elseif ($data[1] == 'divend') {
                        $renderer->doc .= DOKU_LF;//.'</div>'.DOKU_LF;
                    }
                    break;
                case DOKU_LEXER_EXIT:
                    if ($has_content) $renderer->doc .= '</div>' . DOKU_LF;
                    $renderer->doc .= '</map>';
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $renderer->_xmlEntities($data[1]);
                    break;
            }
            return true;
        } elseif ($format == 'metadata') {
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    list($state, $type, $src, $title, $name) = $data;
                    if ($type == 'internalmedia') {
                        resolve_mediaid(getNS($ID), $src, $exists);
                        $renderer->meta['relation']['media'][$src] = $exists;
                    }
                    if (is_null($title))
                        $title = $name;
                    if ($renderer->capture && $title)
                        $renderer->doc .= '[' . $title . ']';
                    break;
                case DOKU_LEXER_EXIT:
                    break;
                case DOKU_LEXER_UNMATCHED:
                    if ($renderer->capture)
                        $renderer->doc .= $data[1];
                    break;
            }
            return true;
        }
        return false;
    }

}

