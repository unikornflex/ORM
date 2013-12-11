UNIKORNFLEX SIMPLE ORM FOR SLIM
===================================================

@TODO :

- make the packege composer compliant :
http://blog.bobbyallen.me/2013/03/23/using-composer-in-your-own-php-projects-with-your-own-git-packageslibraries/

- alter DbFactory to make it configurable

classes : 

	- DbAdapater (factory for DPO instances (singleton | not))
		attr :
			static $_instance
			- $host
			- $db
			- $user
			- $password
		// get singleton instance
		getInstance
		// get a new instance
		get($dsn, $db, $user, $password)

	- AbstractCollection
		attr : 
			ArrayObject $collection

		getOne($id) // alias get()
		getBy(array[, array])
		getAll()

	- AbstractModel
		attr :
			array $adapter (mapper between schema and object)
			// find something for join tables or let it be implemented manually

		save()
		update(array)
		delete()
		__set
		__get

	- Factory