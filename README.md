EXPERIMENTAL lazy services for Nette Framework
=========================

Replaces selected services with proxy classes.

- Enable the extension in your neon config

```yml
extensions:
	proxyService: NetteProxyService\DI\ProxyServiceExtension
```

- Configure directory for proxy class cache

```yml
proxyService:
	cacheDir: some/directory
```

- Tag selected services with proxyService.lazy

- Setup an autoloader for the cacheDir (see [RobotLoader](http://doc.nette.org/en/2.2/configuring#toc-class-auto-loading))
