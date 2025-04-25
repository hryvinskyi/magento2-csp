# Changelog

All notable changes to the Hryvinskyi_Csp module will be documented in this file.

## [1.0.8]
### Added
- Added CSP header splitting functionality to handle large CSP headers
- Added Laminas HTTP integration for proper multi-header handling
- Created new interfaces: CspHeaderProcessorInterface, CspHeaderSplitterInterface, LaminasPluginRegistrarInterface
- Added custom logging capability using hryvinskyi/magento2-logger module
- Added configuration options for CSP header splitting
- Added diagnostics and detailed logging of header splitting operations

### Fixed
- Fixed issue with large CSP headers being truncated or rejected by servers

## [1.0.7]
### Added
- Added support statuses for reports (Pending, Denied, Skipped)