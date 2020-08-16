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
After running command "app:triggers" bundle will create new history table and triggers in your database.

## For MYSQL
- single table with prefix "history_" per entity
- Trigger on "after insert" per entity with name "history_trigger_after_insert_{{ table_name }}"
- Trigger on "after update" per entity with name "history_trigger_before_delete_{{ table_name }}"
- Trigger on "before delete" per entity with name "history_trigger_after_update_{{ table_name }}"


### Disable history table for entity
For disable create history table u must write annotations on ur entity "@DisableHistoryTable". I think this behavior will be best for application becouse u never missing create history table.
