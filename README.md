# Component Bulk Modify

Utility to change properties of components in Keboola Connection Developer Portal. 

Run the application with:

Set environment variables:

```bash
export api_token=eyJraW...
export api_url=https://apps-api.keboola.com
```

The token must be an admin token. If you don't know how to get it, you should not touch this application. Hush!

Run the application in dry run to verify the changes to be made:

## Usage
```bash
php .\bin\console app:set-branch-features 
```

If you are ok with the proposed changes, force them: 
```bash
php .\bin\console app:set-branch-features --force=1
```

## Development
Set environment variables:
```bash

export test_admin_token=eyJraW...
export test_api_url=https://apps-api...
export test_app=keboola-test.app-acme-anvil-service

```

or use the `set-env.sh.teplate` to create a `set-env.sh` file.

- With the above setup, you can run tests:

    ```bash
    docker-compose build
    source ./set-env.sh && docker-compose run tests
    ```

- To run tests with local code use:

    ```bash
    docker-compose run tests-local composer install
    source ./set-env.sh && docker-compose run tests-local
    ```

## License

MIT licensed, see [LICENSE](./LICENSE) file.
