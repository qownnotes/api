# Repository Guide

## Purpose

This repository contains the QOwnNotes Web API. It provides release and update
information to QOwnNotes clients, including download URLs and release notes. It
also provides the release RSS feed and compatibility endpoints for older clients.

## Versioning

- The API version is defined in `config/packages/api_platform.yaml`.
- Make version bumps in that file.
- Commit every version bump in its own dedicated commit. Do not combine a version
  bump with feature, fix, refactoring, or documentation changes.
