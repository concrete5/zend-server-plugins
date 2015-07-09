<?php

namespace Concrete5;

class Module extends \ZRay\ZRayModule {

    public function config() {
        return array(
            'extension' => array(
                'name' => 'concrete5',
            ),
            'defaultPanels' => array(
                'blockRender' => false
            ),
            // configure all custom panels
            'panels' => array(
                'blocks' => array(
                    'display'       => true,
                    'logo'          => 'logo.png',
                    'menuTitle' 	=> 'Blocks',
                    'panelTitle'	=> 'Block Rendering',
                    'searchId' 		=> 'block-table-search',
                    'pagerId'		=> 'block-table-pager'
                )
            )
        );
    }
}