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

        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'handle_toolbar_define');

    }



    public function handle_toolbar_define(Doku_Event &$event, $param)
    {

        $event->data[] = [
            'type' => 'imagemap',
            'title' => $this->getLang('js')['title'],
            'icon' => '../../plugins/imagemapping/map.png',
        ];
    }

}
