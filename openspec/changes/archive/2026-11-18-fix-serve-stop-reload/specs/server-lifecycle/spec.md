## ADDED Requirements

### Requirement: Server records its process id
The `serve` command SHALL start RoadRunner so that a `.pid` file is created in the project root while the server runs.

#### Scenario: Pid file exists while serving
- **WHEN** `roadster serve` is running
- **THEN** a `.pid` file exists in the project root

### Requirement: Lifecycle actions are separate commands
The CLI SHALL provide `serve:stop`, `serve:reload` and `serve:status` commands, and `serve` SHALL NOT accept `--stop` or `--reload`.

#### Scenario: Commands are registered
- **WHEN** the command list is displayed
- **THEN** `serve`, `serve:stop`, `serve:reload` and `serve:status` appear

#### Scenario: Old flags are gone
- **WHEN** `roadster serve --stop` or `roadster serve --reload` is executed
- **THEN** the command fails with an unknown option error and nothing is stopped or reloaded

### Requirement: Stop a running server
`roadster serve:stop` SHALL stop the server started by `roadster serve` for the same project, from any working directory, and exit successfully.

#### Scenario: Stop a running server
- **WHEN** the server is running and `roadster serve:stop` is executed
- **THEN** the server process exits and the command exits with status 0

#### Scenario: Stop when no server is running
- **WHEN** no server is running and `roadster serve:stop` is executed
- **THEN** the command prints that no server is running, suggests `roadster serve`, and exits with a non-zero status

### Requirement: Reload workers of a running server
`roadster serve:reload` SHALL restart the workers of the running server without stopping it, and exit successfully.

#### Scenario: Reload a running server
- **WHEN** the server is running and `roadster serve:reload` is executed
- **THEN** workers are reset, the server keeps listening, and the command exits with status 0

#### Scenario: Reload when no server is running
- **WHEN** no server is running and `roadster serve:reload` is executed
- **THEN** the command prints that no server is running, suggests `roadster serve`, and exits with a non-zero status

### Requirement: Report server status
`roadster serve:status` SHALL report whether the server is running, with its process id, and exit with status 0 when running and non-zero when not.

#### Scenario: Status of a running server
- **WHEN** the server is running and `roadster serve:status` is executed
- **THEN** the output states the server is running and shows its pid, and the command exits with status 0

#### Scenario: Status with no server
- **WHEN** no server is running and `roadster serve:status` is executed
- **THEN** the output states the server is not running and the command exits with a non-zero status

### Requirement: Stale pid files are not treated as running
A `.pid` file whose process no longer exists SHALL be treated as "not running" and removed.

#### Scenario: Stale pid file
- **WHEN** `.pid` contains the pid of a process that does not exist and `roadster serve:status` is executed
- **THEN** the output states the server is not running and the `.pid` file is removed

### Requirement: RPC is local only
The RoadRunner configuration SHALL expose RPC only on the loopback interface, on the port given by `RR_RPC_PORT` (default 6001).

#### Scenario: Default RPC address
- **WHEN** `.rr.yaml` is read without `RR_RPC_PORT` set
- **THEN** RPC listens on `127.0.0.1:6001`

### Requirement: Documentation matches the CLI
README and `CLAUDE.md` SHALL document `serve:stop`, `serve:reload` and `serve:status`, and SHALL NOT mention `serve --stop` or `serve --reload`. `ROADMAP.md` SHALL record `serve:restart` as a planned follow-up.

#### Scenario: No removed options in docs
- **WHEN** README and `CLAUDE.md` are searched for `serve --stop` and `serve --reload`
- **THEN** no matches are found
