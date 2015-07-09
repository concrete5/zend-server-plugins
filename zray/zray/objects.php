<?php

namespace Concrete5;

class ZrayStatistics
{

    protected $pages = array();
    protected $configs = array();
    protected $blocks = array();
    protected $blocksRetrievedForPage = array();

    public function recordPage($c)
    {
        $page = new ZrayPage($c);
        $this->pages[] = $page;
    }

    public function recordBlocksRetrievedForPage($data)
    {
        $this->blocksRetrievedForPage = $data;
    }

    public function getBlocksRetrievedForPage()
    {
        return $this->blocksRetrievedForPage;
    }

    public function recordConfigGet($key, $value)
    {
        $config = new ZrayConfig($key, $value);
        $this->configs[] = $config;
    }

    public function recordRenderedBlock(RenderedBlock $block)
    {
        $this->blocks[$block->getID()] = $block;
    }

    public function getBlockByID($bID)
    {
        return $this->blocks[$bID];
    }

    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * Loop through all the requested pages and aggregate them by count, returning just an array of ZrayRequestedPage
     * objects (with counts)
     * @return array
     */
    public function getRequestedPages()
    {
        $requestedPages = array();
        foreach($this->pages as $page) {
            if (isset($requestedPages[$page->getCollectionID()])) {
                $zrayPage = $requestedPages[$page->getCollectionID()];
                $zrayPage->setCount($zrayPage->getCount() + 1);
            } else {
                $requestedPages[$page->getCollectionID()] = new ZrayRequestedPage($page);
            }
        }
        return $requestedPages;
    }

    /**
     * Loop through all the requested configs and aggregate them by count, returning just an array of ZrayRequestedConfig
     * objects (with counts)
     * @return array
     */
    public function getRequestedConfigs()
    {
        $requestedConfigs = array();
        foreach($this->configs as $config) {
            if (isset($requestedConfigs[$config->getKey()])) {
                $zrayConfig = $requestedConfigs[$config->getKey()];
                $zrayConfig->setCount($zrayConfig->getCount() + 1);
            } else {
                $requestedConfigs[$config->getKey()] = new ZrayRequestedConfig($config->getKey(), $config->getValue());
            }
        }
        return $requestedConfigs;
    }

}

class RenderedBlock
{
    protected $id;
    protected $handle;
    protected $start;
    protected $end;
    protected $usedCache;
    protected $content;
    protected $areaHandle;

    /**
     * @return mixed
     */
    public function getAreaHandle()
    {
        return $this->areaHandle;
    }

    /**
     * @param mixed $areaHandle
     */
    public function setAreaHandle($areaHandle)
    {
        $this->areaHandle = $areaHandle;
    }

    /**
     * @return mixed
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @param mixed $handle
     */
    public function setHandle($handle)
    {
        $this->handle = $handle;
    }

    /**
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->handle;
    }

    /**
     * @param mixed $handle
     */
    public function setType($handle)
    {
        $this->handle = $handle;
    }

    /**
     * @return mixed
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param mixed $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return mixed
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param mixed $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * @return mixed
     */
    public function getUsedCache()
    {
        return $this->usedCache;
    }

    /**
     * @param mixed $usedCache
     */
    public function setUsedCache($usedCache)
    {
        $this->usedCache = $usedCache;
    }

    public function getDisplayRenderTime()
    {
        $diff = $this->end - $this->start;
        return $diff * 1000;
    }
}

class ZrayPage
{
    protected $cID;
    protected $name;
    protected $path;

    /**
     * @return mixed
     */
    public function getCollectionID()
    {
        return $this->cID;
    }

    /**
     * @return mixed
     */
    public function getCollectionName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getCollectionPath()
    {
        return $this->path;
    }

    public function __construct($c)
    {
        $this->cID = $c->getCollectionID();
        $this->name = $c->getCollectionName();
        $this->path = $c->getCollectionPath();
    }
}

class ZrayConfig
{
    protected $key;
    protected $value;

    /**
     * @return mixed
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}


class ZrayRequestedConfig extends ZrayConfig
{
    protected $count = 1;

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }
}

class ZrayRequestedPage extends ZrayPage
{
    protected $count = 1;

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }
}