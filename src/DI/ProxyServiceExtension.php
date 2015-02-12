<?php


namespace NetteProxyService\DI;


use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DirectoryNotFoundException;

/**
 * Loads the extension into Nette Framework
 */
class ProxyServiceExtension extends CompilerExtension
{
    const LAZY = 'proxyService';

    private $defaults = array(
        'cacheDir' => '%tempDir%/proxyService',
    );

    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);

        if (!is_dir($config['cacheDir'])) {
            $message = sprintf("Proxy classes cache directory '%s' does not exist", $config['cacheDir']);
            throw new DirectoryNotFoundException($message);
        }

        $builder->addDefinition($this->prefix('configuration'))
            ->setClass('ProxyManager\Configuration')
            ->addSetup('setProxiesTargetDir', array($config['cacheDir']));

        $builder->addDefinition($this->prefix('proxyFactory'))
            ->setClass('ProxyManager\Factory\LazyLoadingValueHolderFactory',
                array('@' . $this->prefix('configuration')));

        $builder->addDefinition($this->prefix('serviceFactory'))
            ->setClass('NetteProxyService\ServiceFactory', array('@' . $this->prefix('proxyFactory')));
    }

    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();


        foreach (array_keys($builder->findByTag(static::LAZY)) as $serviceName) {
            $definition = $builder->getDefinition($serviceName);
            $builder->removeDefinition($serviceName);

            $hiddenServiceName = $this->prefix($serviceName);
            $builder->addDefinition($serviceName)
                ->setClass($definition->getClass())
                ->setFactory('@' . $this->prefix('serviceFactory') . '::create',
                    array('@' . ContainerBuilder::THIS_CONTAINER, $hiddenServiceName, $definition->getClass()));

            $builder->addDefinition($hiddenServiceName, $definition)
                ->setAutowired(false);
        }
    }
}
