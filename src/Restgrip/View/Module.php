<?php
namespace Restgrip\View;

use Phalcon\Config;
use Phalcon\Escaper;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\View\Simple as ViewSimple;
use Phalcon\Tag;
use Restgrip\Module\ModuleAbstract;

/**
 * @package   Restgrip\View
 * @author    Sarjono Mukti Aji <me@simukti.net>
 */
class Module extends ModuleAbstract
{
    /**
     * @var array
     */
    protected $defaultConfigs = [
        // make sure add ending-slash !
        'viewDir' => '/path/to/your/template/dir_WITH_ENDING_SLASH/',
        'engines' => [
            '.volt' => 'volt',
        ],
        'options' => [
            'volt' => [
                // dont forget ending slash
                'compiledPath'      => '/tmp/',
                'compiledExtension' => '-compiled.php',
                'autoescape'        => true,
                'compileAlways'     => true,
            ],
        ],
    ];
    
    /**
     * View registered in services() because it can be called from console, for example: Background email sending.
     */
    protected function services()
    {
        $app         = $this->app;
        $viewConfigs = new Config($this->defaultConfigs);
        $configs     = $this->getDI()->getShared('configs');
        if ($configs->get('view') instanceof Config) {
            $viewConfigs->merge($configs->view);
        }
        
        $configs->offsetSet('view', $viewConfigs);
        
        $this->getDI()->setShared('escaper', Escaper::class);
        $this->getDI()->setShared('tag', Tag::class);
        
        $this->getDI()->setShared(
            'view',
            function () use ($app, $configs) {
                $instance = new ViewSimple();
                $instance->setViewsDir($configs->view->viewDir);
                $instance->registerEngines($configs->view->engines->toArray());
                $instance->setEventsManager($app->getEventsManager());
                
                return $instance;
            }
        );
        
        $this->getDI()->setShared(
            'volt',
            function () use ($app, $configs) {
                if (!$configs->view->options->volt instanceof Config) {
                    throw new \InvalidArgumentException('Options volt not found');
                }
                
                $instance = new Volt($app->getDI()->getShared('view'), $app->getDI());
                $instance->setOptions($configs->view->options->volt->toArray());
                $instance->setEventsManager($app->getEventsManager());
                
                return $instance;
            }
        );
    }
}