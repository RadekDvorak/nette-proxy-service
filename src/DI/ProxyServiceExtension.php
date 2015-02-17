<?php


namespace NetteProxyService\DI;


use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DirectoryNotFoundException;
use ProxyManager\Generator\ClassGenerator;
use ProxyManager\ProxyGenerator\LazyLoadingValueHolderGenerator;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;

/**
 * Loads the extension into Nette Framework
 */
class ProxyServiceExtension extends CompilerExtension
{
    const LAZY = 'lazy';

    private $defaults = array(
        'cacheDir' => '%tempDir%/proxyService',
        'autogenerateProxyClasses' => true,
    );

    /** @var ContainerBuilder */
    private $builder;

    /** @var ProxyGeneratorInterface */
    private $proxyGenerator;

    public function loadConfiguration()
    {
        $this->builder = $this->getContainerBuilder();
        $config = $this->getConfig($this->defaults);

        if (!is_dir($config['cacheDir'])) {
            $message = sprintf("Proxy classes cache directory '%s' does not exist", $config['cacheDir']);
            throw new DirectoryNotFoundException($message);
        }

        if ($config['autogenerateProxyClasses']) {
            $this->builder->addDefinition($this->prefix('configuration'))
                ->setClass('ProxyManager\Configuration')
                ->addSetup('setProxiesTargetDir', array($config['cacheDir']));

            $this->builder->addDefinition($this->prefix('proxyFactory'))
                ->setClass('ProxyManager\Factory\LazyLoadingValueHolderFactory',
                    array('@' . $this->prefix('configuration')));

            $this->builder->addDefinition($this->prefix('lazyServiceFactory'))
                ->setClass('NetteProxyService\LazyServiceFactory')
                ->setArguments(array('@' . $this->prefix('proxyFactory'), '@' . ContainerBuilder::THIS_CONTAINER));
        } else {
            $this->builder->addDefinition($this->prefix('eagerServiceFactory'))
                ->setClass('NetteProxyService\EagerServiceFactory', array('@' . ContainerBuilder::THIS_CONTAINER));
        }
    }

    public function beforeCompile()
    {
        $config = $this->getConfig($this->defaults);
        $cacheDirectory = $config['cacheDir'] . "/";

        $tag = $this->prefix(static::LAZY);
        if ($config['autogenerateProxyClasses']) {
            foreach (array_keys($this->builder->findByTag($tag)) as $serviceName) {
                $this->createLazyProxy($serviceName);
            }
        } else {
            $this->proxyGenerator = new LazyLoadingValueHolderGenerator();
            foreach (array_keys($this->builder->findByTag($tag)) as $serviceName) {
                $this->createEagerProxy($serviceName, $cacheDirectory);
            }
        }

    }

    /**
     * @param string $serviceName
     */
    private function createLazyProxy($serviceName)
    {
        $definition = $this->builder->getDefinition($serviceName);
        $this->builder->removeDefinition($serviceName);

        $hiddenServiceName = $this->prefix($serviceName);
        $this->builder->addDefinition($serviceName)
            ->setClass($definition->getClass())
            ->setFactory('@' . $this->prefix('lazyServiceFactory') . '::create',
                array($hiddenServiceName, $definition->getClass()));

        $this->builder->addDefinition($hiddenServiceName, $definition)
            ->setAutowired(false);
    }

    /**
     * @param string $serviceName
     * @param string $cacheDirectory
     */
    private function createEagerProxy($serviceName, $cacheDirectory)
    {
        $definition = $this->builder->getDefinition($serviceName);
        $this->builder->removeDefinition($serviceName);

        $hiddenServiceName = $this->prefix($serviceName);
        $proxyClassName = $definition->getClass() . md5(serialize($definition));
        $this->builder->addDefinition($serviceName)
            ->setClass($proxyClassName)
            ->setFactory('@' . $this->prefix('eagerServiceFactory') . '::create',
                array($hiddenServiceName, $proxyClassName));

        $this->builder->addDefinition($hiddenServiceName, $definition)
            ->setAutowired(false);

        if (!class_exists($proxyClassName)) {
            $classGenerator = new ClassGenerator();
            $this->proxyGenerator->generate(new \ReflectionClass($definition->getClass()), $classGenerator);
            $classGenerator->setName($proxyClassName);

            $code = '<?php' . ClassGenerator::LINE_FEED . $classGenerator->generate();
            $tempPath = $cacheDirectory . uniqid() . ".php";
            $backslashPos = strrpos($proxyClassName, '\\');
            $newPath = $cacheDirectory . substr($proxyClassName, $backslashPos ? $backslashPos + 1 : 0) . ".php";

            file_put_contents($tempPath, $code, LOCK_EX);
            rename($tempPath, $newPath);
            require_once $newPath;
        }
    }
}
