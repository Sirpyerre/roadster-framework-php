## ADDED Requirements

### Requirement: SQLite is the default connection
With no database variables set, the application SHALL use a SQLite database file at `storage/database.sqlite`.

#### Scenario: Zero configuration
- **WHEN** no `DB_*` variables are set
- **THEN** the connection parameters use the SQLite driver with path `storage/database.sqlite` under the project root

### Requirement: Connection is configured from the environment
`config/database.php` SHALL read `DB_CONNECTION` (`sqlite`, `mysql` or `pgsql`), `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`, and map them to connection parameters.

#### Scenario: MySQL configuration
- **WHEN** `DB_CONNECTION=mysql` and host, port, database, username and password are set
- **THEN** the parameters use the MySQL driver with those values

#### Scenario: PostgreSQL configuration
- **WHEN** `DB_CONNECTION=pgsql` and the connection variables are set
- **THEN** the parameters use the PostgreSQL driver with those values

#### Scenario: Relative SQLite path
- **WHEN** `DB_CONNECTION=sqlite` and `DB_DATABASE` is a relative path
- **THEN** the path is resolved against the project root

#### Scenario: In-memory SQLite
- **WHEN** `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`
- **THEN** the parameters request an in-memory database

#### Scenario: Unsupported driver
- **WHEN** `DB_CONNECTION` is not one of `sqlite`, `mysql` or `pgsql`
- **THEN** building the connection fails with an error naming the invalid value and the supported ones

### Requirement: The container provides a shared connection
The container SHALL resolve `Doctrine\DBAL\Connection` from the configured parameters, connect lazily, and return the same instance on every request for it.

#### Scenario: Resolve without connecting
- **WHEN** the connection is resolved from the container
- **THEN** no database connection is opened until a query is executed

#### Scenario: Same instance
- **WHEN** the connection is requested twice
- **THEN** the same object is returned

### Requirement: Health check reports database status
`GET /health` SHALL include `checks.database` and run a trivial query against the connection. When it succeeds the response SHALL be 200 with `status: healthy` and `checks.database: ok`. When it fails the response SHALL be 503 with `status: unhealthy` and `checks.database: error`.

#### Scenario: Database reachable
- **WHEN** `GET /health` is called and the database answers
- **THEN** the response is 200 with `status` `healthy`, `checks.database` `ok` and a `timestamp`

#### Scenario: Database unreachable
- **WHEN** `GET /health` is called and the query throws
- **THEN** the response is 503 with `status` `unhealthy` and `checks.database` `error`

#### Scenario: No connection details leaked
- **WHEN** the database check fails
- **THEN** the response body contains no host, user name, password, DSN or exception message

### Requirement: Environment is documented
`.env.example` SHALL list the database variables with the SQLite default active and MySQL and PostgreSQL examples commented.

#### Scenario: Example file
- **WHEN** `.env.example` is read
- **THEN** it documents `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD`
