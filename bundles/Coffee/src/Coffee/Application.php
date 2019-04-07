<?php
namespace Coffee;

use Coffee\Request;
use Coffee\Response;
use Coffee\EventManager;
use Coffee\Router;
use Coffee\ClassLoader;

class Application
{
    const EVENT_BOOTSTRAP = 'CORE.BOOTSTRAP';
    const EVENT_PRE_DISPATCH = 'CORE.PRE_DISPATCH';
    const EVENT_POST_DISPATCH = 'CORE.POST_DISPATCH';
    const EVENT_SHUTDOWN = 'CORE.SHUTDOWN';

    /**
     * @var \Coffee\Request
     */
    protected $request;

    /**
     * @var \Coffee\Response
     */
    protected $response;

    /**
     * @var \Coffee\EventManager
     */
    protected $eventManager;

    /**
     * @var \Coffee\Router
     */
    protected $router;

    /**
     * @var \Coffee\Classloader
     */
    protected $classLoader;

    public function __construct($config = [])
    {
        $this->classLoader = new ClassLoader();
        // TODO: Configure classloader

        // Load the bundles
        $this->loadBundle('Coffee');
        $this->loadBundles($config['bundles']);

        // Load basic classes
        $this->router = new Router();
        $this->request = new Request();
        $this->response = new Response();
        $this->eventManager = new EventManager();

        $this->eventManager->trigger(static::EVENT_BOOTSTRAP, $this);
        $this->eventManager->register(static::EVENT_SHUTDOWN, )
        register_shutdown_function(array($this->eventManager, 'register'), static::EVENT_SHUTDOWN);
    }

    /**
     * @param \Coffee\Router $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return \Coffee\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param \Coffee\Classloader $classLoader
     */
    public function setClassLoader($classLoader)
    {
        $this->classLoader = $classLoader;
    }

    /**
     * @return \Coffee\Classloader
     */
    public function getClassLoader()
    {
        return $this->classLoader;
    }

    /**
     * @param \Coffee\EventManager $eventManager
     */
    public function setEventManager($eventManager)
    {
        $this->eventManager = $eventManager;
    }

    /**
     * @return \Coffee\EventManager
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    /**
     * @param \Coffee\Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return \Coffee\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param \Coffee\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return \Coffee\Response
     */
    public function getResponse()
    {
        return $this->response;
    }



    public function loadBundle($bundle)
    {
        $bundle = ucfirst($bundle);

        $this->classLoader->addPath($bundle);

        include COREPATH . 'bundles/' . $bundle . '/Bootstrap.php';
        $bootstrap_class = $bundle . '_Bootstrap';
        $bootstrap = new $bootstrap_class;
        method_exists($bootstrap, 'onBootstrap') and $this->eventManager->register(static::EVENT_BOOTSTRAP, [$bootstrap, 'onBootstrap']);
    }

    public function loadBundles($bundles)
    {
        foreach ((array) $bundles as $bundle) {
            $this->loadBundle($bundle);
        }
    }

    public function run()
    {
        // Make the request
        try
        {
            $this->router->resolve($this->request);
            Core\Request::getInstance()->dispatch(Core\Router::resolve())->send();
        }
        catch (Core\HttpNotFoundException $e)
        {
            try
            {
                Core\Request::getInstance()->dispatch(Core\Config::get('routes.error_404'))->send();
            }
            catch (Core\HttpNotFoundException $e)
            {
                echo $e->getMessage();
            }
        }

        $this->event->trigger();

    }
}
