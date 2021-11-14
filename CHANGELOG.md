# Changelog
All notable changes to this project will be documented in this file. The format is (loosely) based on [Keep a 
Changelog]. As this project is considered to be a final product, it **does not** adhere to the [Semantic Versioning] 
scheme - instead version numbers are based on `<year>.<release>.<patch>` scheme. Public APIs however are versioned 
separately and adhere to the [Semantic Versioning].  

## [Unreleased]

### Added
 - API: `nelmio/cors-bundle` for CORS policy management
 - API: `CORS_ALLOW_ORIGIN` environment variable, that controls allowed origins for CORS calls, `https://.+` by default
 - API: Synchronization is now triggered by the scheduler daily at 2AM
 - API: Synchronization is now automatically triggered after migrations
 - Build: Scheduler (cron) is now available in the `cojedzie/standalone` image
 - Load Tester: Utility to stress test servers
 - Front: Support for hot reload for development
 - Front: Templates are now precompiled to render function using `vue-loader`
 - Front: Add ability to independently control relative time display for non realtime departures
 - Front: Add ability to limit relative time display only for closest departures
 - Front: Add help boxes for all settings

### Changed
 - Front: Updated vue framework to version 3.x from 2.x
 - Front: Components have now at least two word names, as per vue [style guide](https://v3.vuejs.org/style-guide/)
 - Build: Front now uses yarn with `--frozen-lockfile` flag

### Fixed 
 - API: Errors are now properly reported respecting [RFC 7807](https://datatracker.ietf.org/doc/html/rfc7807)
 - API: Validation of `server_id` in `api_v1_federation_connect` endpoint.

## [2021.1.3] - 2021-07-12

### Fixed
- API: Fixed duplicated entry error due to non-unique `busServiceName` (ZTM Gdańsk)


## [2021.1.2] - 2021-06-04

### Added
- API: `/api/v1/network/nodes` should return authorization cookie

### Changed
- Build: PHP docker image layers should be now more reusable

## [2021.1.1] - 2021-06-03

### Added
 - `.utils/bump-version.sh` utility to change version in package meta files

### Changed
 - Front: Version in frontend server is now obtained from `package.json`

### Fixed 
 - API: Undefined column in migration `Version20210429144033`

## [2021.1.0] - 2021-06-03
First tracked release of cojedzie

### Added
 - This `CHANGELOG.md` file
 - `/api/v1/federation` and `/api/v1/network` APIs

### Changed 
 - Project is now licensed under the AGPLv3 license terms

[Unreleased]: https://github.com/cojedzie/cojedzie/compare/v2021.1.3...HEAD
[2021.1.3]: https://github.com/cojedzie/cojedzie/compare/v2021.1.2...v2021.1.3
[2021.1.2]: https://github.com/cojedzie/cojedzie/compare/v2021.1.1...v2021.1.2
[2021.1.1]: https://github.com/cojedzie/cojedzie/compare/v2021.1.0...v2021.1.1
[2021.1.0]: https://github.com/cojedzie/cojedzie/tree/v2021.1.0

[Keep a Changelog]: https://keepachangelog.com/en/1.0.0/
[Semantic Versioning]: https://semver.org/spec/v2.0.0.html 
