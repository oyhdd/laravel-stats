<?php

namespace Oyhdd\StatsCenter\Extensions;

use Encore\Admin\Admin;
use Encore\Admin\Grid\Displayers\Actions as BaseActions;

class Actions extends BaseActions
{

    /**
     * Render view action.
     *
     * @return string
     */
    protected function renderView()
    {
        return <<<EOT
<a href="{$this->getResource()}/{$this->getRouteKey()}" class="{$this->grid->getGridRowName()}-view">
&nbsp;<span class='label label-info'>查看</span>&nbsp;
</a>
EOT;
    }

    /**
     * Render edit action.
     *
     * @return string
     */
    protected function renderEdit()
    {
        return <<<EOT
<a href="{$this->getResource()}/{$this->getRouteKey()}/edit" class="{$this->grid->getGridRowName()}-edit">
&nbsp;<span class='label label-primary'>编辑</span>&nbsp;
</a>
EOT;
    }

    /**
     * Render delete action.
     *
     * @return string
     */
    protected function renderDelete()
    {
        $this->setupDeleteScript();

        return <<<EOT
<a href="javascript:void(0);" data-id="{$this->getKey()}" class="{$this->grid->getGridRowName()}-delete">
&nbsp;<span class='label label-danger'>删除</span>&nbsp;
</a>
EOT;
    }
}
