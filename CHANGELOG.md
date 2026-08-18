# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.3] - 2026-08-18

### Fixed

- Fall back to the latest stored release while a newly published GitHub release
  does not yet contain platform binaries.

## [1.2.2] - 2026-08-18

### Fixed

- Fetch cumulative release notes from the published release tag, falling back to the
  `release` branch, so unreleased changelog entries from `main` are not returned.
