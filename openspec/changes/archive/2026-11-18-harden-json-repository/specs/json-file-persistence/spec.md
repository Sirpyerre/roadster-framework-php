## Purpose

Defines the reliability guarantees of the JSON file storage that repositories use, so data survives concurrent workers, crashes and bad files without silent loss.

## ADDED Requirements

### Requirement: Concurrent writers do not lose updates
The storage SHALL serialize read-modify-write operations on the same file so that concurrent saves and deletes from multiple workers are all applied.

#### Scenario: Parallel saves
- **WHEN** several processes each save distinct items to the same file at the same time
- **THEN** the file afterwards contains every saved item exactly once

#### Scenario: Save during delete
- **WHEN** one process deletes an item while another saves a different item
- **THEN** the deleted item is absent and the saved item is present

### Requirement: Writes are atomic
The storage SHALL replace the file contents atomically so that a reader, or a process interrupted mid-write, never observes a partially written file.

#### Scenario: Interrupted write
- **WHEN** a write fails or is interrupted before completing
- **THEN** the previous file contents remain intact and valid

#### Scenario: Read during write
- **WHEN** a read happens while another process is writing
- **THEN** the read returns either the complete old data or the complete new data

### Requirement: Corrupt data is reported, never overwritten
The storage SHALL raise an error when the file exists with content that is not valid JSON or is not a list, and SHALL NOT replace that content as a side effect of reading or saving.

#### Scenario: Invalid JSON on read
- **WHEN** the file contains `{not json`
- **THEN** reading raises an error and the file content is unchanged

#### Scenario: Save over corrupt file
- **WHEN** a save is attempted while the file contains invalid JSON
- **THEN** the save raises an error and the file content is unchanged

#### Scenario: API request with corrupt file
- **WHEN** an API request needs data from a corrupt file
- **THEN** the response is a 500 JSON error with the generic message unless debug is enabled

### Requirement: Failed writes are reported
The storage SHALL raise an error when encoding or writing data fails, instead of reporting success.

#### Scenario: Unwritable location
- **WHEN** the target directory is not writable
- **THEN** the save raises an error and does not report the item as saved

### Requirement: Missing or empty file is an empty collection
The storage SHALL treat a missing or zero-length file as an empty collection and SHALL create it on first successful write.

#### Scenario: First use
- **WHEN** the file does not exist and the collection is read
- **THEN** an empty collection is returned

#### Scenario: First save
- **WHEN** an item is saved and the file does not exist
- **THEN** the file is created containing that item
