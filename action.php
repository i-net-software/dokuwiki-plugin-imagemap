<?php
/**
 * DokuWiki Plugin imagemap (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Weggerle <AlexanderWeggerle.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_imagemap extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_start');
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar_define');

    }

    public function handle_start(Doku_Event &$event, $param) {
        global $JSINFO;

        $JSINFO['plugin_imagemap_mldummy'] = ml(':wiki:dokuwiki-128.png','', true);
    }

    public function handle_toolbar_define(Doku_Event &$event, $param) {

        $event->data[] = array (
            'type' => 'imagemap',
            'title' => 'imagemap',
            'icon' => '../../plugins/imagemap/map.png',
            'list' => array(),
            );

    }

}

// vim:ts=4:sw=4:et:
