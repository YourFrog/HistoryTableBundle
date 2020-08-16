### Support database
- MySQL - Completed
- Postgresql - in the futures

### Step 1: Use composer and enable Bundle

```bash
composer require yourfrog/symfony-bundle_history-table
```

Now, Composer will automatically download all required files, and install them
for you. All that is left to do is to update your ``AppKernel.php`` file, and
register the new bundle:

```php
<?php

// in AppKernel::registerBundles()
$bundles = array(
    // ...
    new HistoryTableBundle\HistoryTableBundle(),
    // ...
);
```


### Step2: Add command to your composer.json under "scripts" section for example
```json
"scripts": {
	"rebuild": [
	    "php bin/console doctrine:schema:drop --force --full-database",
	    "php bin/console doctrine:schema:update --force",
	    "php bin/console app:triggers",
	    "php bin/console doctrine:fixtures:load --no-interaction"
	]
}
```


### Information
After running command "app:triggers" bundle will create new table and history in your database. 
## For MYSQL
This bundle will create one history table per normal table with prefix "history_" and 3 triggers on "after insert", "after update and "before delete". 
