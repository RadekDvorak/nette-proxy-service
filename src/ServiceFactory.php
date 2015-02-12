<?php


namespace NetteProxyService;


use Nette\DI\Container;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;

/**
 * Proxy class instantiator
 */
class ServiceFactory
{

    /** @var LazyLoadingValueHolderFactory */
    private $proxyFactory;

    public function __construct(LazyLoadingValueHolderFactory $proxyFactory)
    {
        $this->proxyFactory = $proxyFactory;
    }

    public function create(Container $container, $hiddenServiceName, $proxyClassName)
    {
        /**
         * @var object $wrappedObject the instance (passed by reference) of the wrapped object,
         *                             set it to your real object
         * @var object $proxy the instance proxy that is being initialized
         * @var string $method the name of the method that triggered lazy initialization
         * @var string $parameters an ordered list of parameters passed to the method that
         *                             triggered initialization, indexed by parameter name
         * @var \Closure $initializer a reference to the property that is the initializer for the
         *                             proxy. Set it to null to disable further initialization
         *
         * @return bool true on success
         */
        $initializer = function (& $wrappedObject, $proxy, $method, $parameters, & $initializer) use (
            $container,
            $hiddenServiceName
        ) {
            $wrappedObject = $container->getService($hiddenServiceName);
            $initializer = null;

            return true;
        };

        return $this->proxyFactory->createProxy($proxyClassName, $initializer);
    }
}
