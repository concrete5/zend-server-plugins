<?php

namespace Concrete5;

class Zray
{

    public function start()
    {
        $cms = \Core::make('app');
        $cms->bindShared('zray/statistics', function() {
            return new ZrayStatistics();
        });
    }

    public function onAfterGetPage($context, &$storage)
    {
        // store data to storage that later on will be displayed in panels
        if ($context['returnValue']) {
            $c = $context['returnValue'];
            $cms = \Core::make('app');
            $statistics = $cms['zray/statistics'];
            $statistics->recordPage($c);
        }
    }

    public function onBeforeBlockRender($context, &$storage)
    {
        $data = $context['this']->getScopeItems();

        if ($data['b']) {
            $b = new RenderedBlock();
            $b->setID($data['b']->getBlockID());
            $b->setType($data['bt']->getBlockTypeHandle());
            $b->setStart(microtime(true));
            $b->setAreaHandle($data['b']->getAreaHandle());

            $cms = \Core::make('app');
            $statistics = $cms['zray/statistics'];
            $statistics->recordRenderedBlock($b);
        }
    }

    public function onAfterBlockRender($context, &$storage)
    {
        $end = microtime(true);
        $data = $context['this']->getScopeItems();

        if ($data['b']) {

            $cms = \Core::make('app');
            $statistics = $cms['zray/statistics'];
            $block = $statistics->getBlockByID($data['b']->getBlockID());
            $block->setEnd(microtime(true));
            $block->setUsedCache($context['this']->usedBlockCacheDuringRender());

            $controller = $data['b']->getController();
            if (is_object($controller) && method_exists($controller, 'getSearchableContent')) {
                $block->setContent($controller->getSearchableContent());
            }
        }
    }

    public function onAfterGetConfigValue($context, &$storage)
    {
        $cms = \Core::make('app');
        $statistics = $cms['zray/statistics'];
        $statistics->recordConfigGet($context['functionArgs'][0], (string) $context['returnValue']);
    }

    public function onBeforePageRender($context, &$storage)
    {
        $cms = \Core::make('app');
        $statistics = $cms['zray/statistics'];
        $data = $context['this']->getScopeItems();
        $c = $data['c'];
        if (is_object($c)) {
            $statistics->recordBlocksRetrievedForPage($c->getBlockIDs());
        }
    }

    public function onAfterDispatch($context, &$storage)
    {
        $cms = \Core::make('app');
        $statistics = $cms['zray/statistics'];
        $c = \Page::getCurrentPage();

        foreach($statistics->getRequestedPages() as $page) {
            $storage['pageRequests'][] = array(
                'ID' => $page->getCollectionID(),
                'Path' => $page->getCollectionPath(),
                'Name' => $page->getCollectionName(),
                'Total' => $page->getCount()
            );
        }

        foreach($statistics->getRequestedConfigs() as $config) {
            $storage['configRequests'][] = array(
                'Key' => $config->getKey(),
                'Value' => $config->getValue(),
                'Total' => $config->getCount()
            );
        }

        $u = new \User();
        foreach($u->getUserAccessEntityObjects() as $entity) {
            $storage['yourAccessEntities'][] = array(
                'ID' => $entity->getAccessEntityID(),
                'Type' => $entity->getAccessEntityTypeHandle(),
                'Detail' => $entity->getAccessEntityLabel()
            );
        }

        $cms = \Core::make('app');
        $statistics = $cms['zray/statistics'];
        $allBlocks = $statistics->getBlocksRetrievedForPage(); // all blocks, not just those rendered

        $renderedBlocks = array();
        foreach($statistics->getBlocks() as $block) {
            $renderedBlocks[] = $block->getID();
            $storage['blockRender'][] = array(
                'bID' => $block->getID(),
                'type' => $block->getType(),
                'area' => $block->getAreaHandle(),
                'cache' => $block->getUsedCache(),
                'content' => $block->getContent(),
                'time' => $block->getDisplayRenderTime(),
                'rendered' => true
            );
        }

        foreach($allBlocks as $row) {
            if (!in_array($row['bID'], $renderedBlocks)) {
                $b = \Block::getByID($row['bID'], $c, $row['arHandle']);
                if (is_object($b)) {
                    $storage['blockRender'][] = array(
                        'bID' => $b->getBlockID(),
                        'type' => $b->getBlockTypeHandle(),
                        'area' => $b->getAreaHandle(),
                        'rendered' => false
                    );
                }
            }
        }

        if (is_object($c)) {
            $cp = new \Permissions($c);
            $assignments = $cp->getAllAssignmentsForPage();
            foreach($assignments as $assignment) {
                $pk = $assignment->getPermissionKeyObject();
                $obj = $pk->getPermissionObject();
                if ($obj && (!isset($lastobj) || $lastobj != $obj)) {
                    $storage['customPagePermissions'][] = array(
                        'Type' => $obj->getPermissionObjectKeyCategoryHandle(),
                        'Object' => $obj->getPermissionObjectIdentifier()
                    );
                }
                $lastobj = $obj;
            }
        }
    }

}

require __DIR__ . '/objects.php';

// Create new extension - disabled
$zre = new \ZRayExtension('concrete5');

$concrete5 = new Zray();

// set additional data such as logo
$zre->setMetadata(array(
    'logo' => __DIR__ . DIRECTORY_SEPARATOR . 'logo.png',
));

// start tracing only when 'your_application_initial_method' is called, e.g. 'Mage::run()'
$zre->setEnabledAfter('Concrete\Core\Application\Application::checkPageCache');

// start everything
$zre->traceFunction('Concrete\Core\Application\Application::checkPageCache', array($concrete5, 'start'), function() {});


// trace config values checked and what their value was
$zre->traceFunction('Illuminate\Config\Repository::get', function() {}, array($concrete5, 'onAfterGetConfigValue'));

// trace blocks
$zre->traceFunction('Concrete\Core\Page\View\PageView::startRender', array($concrete5, 'onBeforePageRender'), function() {});

// trace block render time
$zre->traceFunction('Concrete\Core\Block\View\BlockView::start',
    array($concrete5, 'onBeforeBlockRender'), function() {}
);
$zre->traceFunction('Concrete\Core\Block\View\BlockView::finishRender',
    function() {}, array($concrete5, 'onAfterBlockRender')
);

// trace page requests
$zre->traceFunction('Concrete\Core\Page\Page::getByID', function() {}, array($concrete5, 'onAfterGetPage'));
$zre->traceFunction('Concrete\Core\Page\Page::getByPath', function() {}, array($concrete5, 'onAfterGetPage'));

// Finish method - aggregates things that need aggregating
$zre->traceFunction('Symfony\Component\HttpFoundation\Response::send', array($concrete5, 'onAfterDispatch'), function() {});
