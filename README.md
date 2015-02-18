#Nette Proxy Generator#
This Nette Framework extension allows you to hide services in proxy classes. Seldom used yet still initialized services 
are hidden, eventual expensive object initiation is postponed. The real service class gets instantiated as soon as 
the proxy class property or method is accessed.

##Instructions##
- Have composer install the source code

```
composer.phar require radekdvorak/nette-proxy-service:0.*
```

- Enable the extension in your neon config

```
extensions:
	proxyService: NetteProxyService\DI\ProxyServiceExtension
```

- Configure directory for proxy class cache


```
proxyService:
	cacheDir: some/directory
	# disable in production
	autogenerateProxyClasses: true
```

- Tag selected services with `proxyService.lazy` (the prefix depends on the extension registration name)

- Setup an autoloader for the cacheDir (see [RobotLoader](http://doc.nette.org/en/2.2/configuring#toc-class-auto-loading) if you are not sure what to do)

##Notes:##
-  This extension is quite new therefore consider it a bit experimental
