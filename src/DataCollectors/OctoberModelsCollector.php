<?php namespace October\Debugbar\DataCollectors;

use October\Rain\Database\Model;
use DebugBar\DataCollector\ObjectCountCollector;

/**
 * OctoberModelsCollector tracks October model instantiation counts
 */
class OctoberModelsCollector extends ObjectCountCollector
{
    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct('models');

        Model::extend(function ($model) {
            $model->bindEvent('model.afterFetch', function () use ($model) {
                $this->countClass($model);
            });
        });
    }
}
