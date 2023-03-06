<?php

/**
 * DokuWiki Plugin imagemap (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Alexander Weggerle <AlexanderWeggerle.de>
 */
class action_plugin_imagemapping extends DokuWiki_Action_Plugin
{

    public function register(Doku_Event_Handler $controller)
    {

        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'handle_start');
        // $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar_define');

    }

    public function handle_start(Doku_Event $event, $param)
    {
        global $JSINFO;

        $JSINFO['plugin_imagemap_mldummy'] = ml(':wiki:dokuwiki-128.png', '', true);
    }

    public function handle_toolbar_define(Doku_Event &$event, $param)
    {

        $event->data[] = [
            'type' => 'imagemap',
            'title' => 'imagemapping',
            'icon' => '../../plugins/imagemapping/map.png',
            'list' => [],
        ];
    }

}
