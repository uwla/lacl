# DEVELOPMENT

## Installing

1. Clone the repository:
    ```shell
    git clone https://github.com/uwla/lacl
    ```
2. Install dependencies:
    ```shell
    composer install
    ```
3. Refresh composer:
    ```shell
    composer dump-autoload
    ```

Or, in a single line:

```shell
git clone https://github.com/uwla/lacl && cd lacl && composer install && composer dump-autoload
```

## Project structure

- `database/migration/`: database migration files
- `src/`: source files
- `src/Contracts`: interfaces used in the package
- `src/Models`: database eloquent models
- `src/Traits`: traits provided by the package
- `src/AclServiceProvider.php`: this Service Provider publishes migration files
- `tests`: test files
- `tests/app`: sample `app` for mocking tests
- `tests/Feature`: integration tests
-
## Testing

To run the the tests you need `pdo_sqlite` extension installed and enabled in `php.ini`.

* Run all tests:
    ```shell
    composer test
    ```
* Run a single test (for example, `HasPermissionTest`:
    ```shell
    composer test ./tests/Feature/HasPermissionTest.php
    ```
