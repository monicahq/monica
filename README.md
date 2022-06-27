## Run locally

1. `composer install` and `yarn run watch`
1. Optional: make the search work:
   1. Install and run meilisearch locally
   1. Configure and run a queue (`php artisan queue:listen`)

## Configuring search

Search in production is done with Meilisearch. You can use other drivers if you want to.
That being said, if you want to index new data, or add additional data to search data for, you need to indicate all the indexes of this new data in SetupApplication.php – otherwise, Meilisearch won't know the indexes and you will end up with an error message saying that data is not filterable.
