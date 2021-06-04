# Changelog
All notable changes to this project will be documented in this file. The format is (loosely) based on [Keep a 
Changelog]. As this project is considered to be a final product, it **does not** adhere to the [Semantic Versioning] 
scheme - instead version numbers are based on `<year>.<release>.<patch>` scheme. Public APIs however are versioned 
separately and adhere to the [Semantic Versioning].  

## [Unreleased]

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

[Unreleased]: https://github.com/cojedzie/cojedzie/compare/v2021.1.1...HEAD
[2021.1.1]: https://github.com/cojedzie/cojedzie/compare/v2021.1.0...v2021.1.1
[2021.1.0]: https://github.com/cojedzie/cojedzie/tree/v2021.1.0

[Keep a Changelog]: https://keepachangelog.com/en/1.0.0/
[Semantic Versioning]: https://semver.org/spec/v2.0.0.html 
