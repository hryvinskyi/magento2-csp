# Advanced Content Security Policy (CSP) module for Magento 2

[![Latest Stable Version](https://poser.pugx.org/hryvinskyi/magento2-csp/v/stable)](https://packagist.org/packages/hryvinskyi/magento2-csp)
[![Total Downloads](https://poser.pugx.org/hryvinskyi/magento2-csp/downloads)](https://packagist.org/packages/hryvinskyi/magento2-csp)
[![License](https://poser.pugx.org/hryvinskyi/magento2-csp/license)](https://packagist.org/packages/hryvinskyi/magento2-csp)

## Overview

The `Hryvinskyi_Csp` module is a Magento 2 extension that provides additional Content Security Policy (CSP) configurations.
This module allows administrators to manage CSP whitelists from the Magento admin panel 

## Features
 1. **CSP Whitelist Management**: Administrators can manage CSP whitelists directly from the Magento admin panel.
 2. **Store-Specific Configuration**: Module provides store view specific CSP configuration.
 3. **Violation Reports**: The module collects and displays CSP violation reports, helping administrators identify and address security issues.
 4. **One-Click Conversion**: Possibility to convert violation reports to whitelist rule with one click.
 5. **Mass Convert Reports**: Bulk conversion of multiple CSP report groups to whitelist entries with automatic cleanup.
 6. **Automatic URL Collection**: Automatically collects and adds all storefront URLs to the CSP whitelist.
 7. **CSP Header Splitting**: Automatically splits large CSP headers into multiple smaller ones to prevent issues with header size limits.
 8. **CSP Value Optimization**: Removes duplicate entries and redundant wildcard-covered values from CSP headers to reduce header size.
 9. **Flexible Configuration**: The module provides various configuration options to enable or disable specific CSP features.
10. **Admin Panel Integration**: The module integrates with the Magento admin panel, providing a user-friendly interface for managing CSP settings.
11. **Import/Export**: Support for importing and exporting whitelist rules.
12. **Automatic Script Hash Generation**: Command-line tool to scan CMS pages/blocks and configs for inline scripts and generate CSP hashes
13. **Visual Hash Validation**: See at a glance if your script hashes are valid
14. **Template Nonce Provider**: ViewModel class for easy CSP nonce generation in templates
15. **Enhanced Caching**: Improved CSP policy caching with better serialization and cache management
16. **Report Grouping**: Organized CSP violation reports into logical groups for better management
17. **Redundancy Detection**: Visual indicators showing duplicate and redundant whitelist entries
18. **Advanced Grid Filtering**: Filter whitelist entries by hash validation status and redundancy status
19. **Advanced Grid Sorting**: Sort whitelist entries by computed columns (hash validation, redundancy)

## Requirements

- Magento 2.4.4 or higher
- PHP 8.1 or higher

## Installation

### Composer (recommended)

```bash
composer require hryvinskyi/magento2-csp
bin/magento module:enable Hryvinskyi_Csp
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

### Manual Installation

1. Download the module and upload it to `app/code/Hryvinskyi/Csp`
2. Enable the module and update the database:

```bash
bin/magento module:enable Hryvinskyi_Csp
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

## Usage

**Admin Panel Navigation**

The module adds a new menu item in the admin panel:

 1. **Content Security Policy**: Main menu item providing access to CSP features
   - **Whitelist**: Manage CSP whitelist rules
   - **Violation Report**: View and manage CSP violation reports
   - **Configuration**: Configure CSP settings

### Managing Whitelist Rules

 1. Navigate to **System** > **Content Security Policy** > **Whitelist**
 2. Click **Add** to create a whitelist entry manually
 3. Fill in required fields:
    - **Identifier**: Unique name for the rule 
    - **Policy**: CSP directive (e.g., script-src, style-src)
    - **Value Type**: Type of value (URL, Domain, etc.)
    - **Value**: The actual value to whitelist 
    - **Store Views**: Select applicable store views 
    - **Status**: Enable or disable the rule

### Using CSP Nonces in Templates

The module provides a CspNonceProvider ViewModel for easy nonce generation in templates:

```xml
<!-- In your layout XML -->
<block name="your.block" template="Your_Module::template.phtml">
    <arguments>
        <argument name="cspNonceProviderViewModel" xsi:type="object">Hryvinskyi\Csp\ViewModel\CspNonceProvider</argument>
    </arguments>
</block>
```

In your template (template.phtml)

```php
<?php

$cspNonceProviderViewModel = $block->getData('cspNonceProviderViewModel')

$nonce = '';
if ($cspNonceProviderViewModel) {
    $nonce = $cspNonceProviderViewModel->getNonce();
}
?>

<script<?= $nonce !== '' ? ' nonce="' . $nonce .'"' : '' ?>>
    // Your inline script here
</script>
```
### Generating Script Hashes

To make inline scripts work with CSP, you must generate cryptographic SHA hashes and add them to your whitelist. 
The module provides a console tool that lets you review each script and approve the addition of its hash to your CSP configuration.
Use the built-in CLI tool:

```bash
bin/magento hryvinskyi:csp:generate-script-hashes --type=page --type=block --store=1
```

Options:
 - `--type`: Specify which entity types to scan (page, block, config)
 - `--store`: Specify store ID (default is all stores)

### Screenshots
![console-screenshot-1.jpg](docs/images/console-screenshot-1.jpg)
![final_summary.jpg](docs/images/final_summary.jpg)

### Configuration
Navigate to **System** > **Content Security Policy** > **Configuration** or **Stores** > **Configuration** > **Security** > **Content Security Policy** to access module settings.

### CSP Header Splitting

CSP headers can grow large, especially when many domains are whitelisted. Some servers and proxies have limits on header sizes, which can cause issues with security policy enforcement.

This module includes CSP header splitting functionality that automatically splits large CSP headers into multiple smaller headers to ensure proper delivery.

To configure header splitting:

1. Go to **Stores** > **Configuration** > **Security** > **Content Security Policy**
2. In the **General** section, you'll find:
    - **Enable CSP header splitting**: Toggle to enable/disable the feature
    - **Max CSP header size (bytes)**: Specify the maximum size for a single header before splitting occurs (default: 4096 bytes)

When enabled, the module will monitor CSP header sizes and automatically split them if they exceed the configured maximum size.

### CSP Value Optimization

Over time, CSP headers can accumulate duplicate entries and redundant values that are already covered by wildcard patterns. This increases header size unnecessarily.

The module includes CSP value optimization that can:
- **Remove exact duplicates**: Eliminates entries like `data:` appearing multiple times in the same directive
- **Remove wildcard-covered entries**: Removes specific domains when a wildcard already covers them (e.g., removes `www.example.com` when `*.example.com` exists)
- **Detect redundant wildcards**: Removes wildcards covered by broader wildcards (e.g., `*.sub.example.com` when `*.example.com` exists)
- **Warn about unrestricted wildcards**: Logs a warning when `*` is used, which makes all other entries redundant

To configure value optimization:

1. Go to **Stores** > **Configuration** > **Security** > **Content Security Policy**
2. In the **General** section, you'll find:
    - **Enable CSP value optimization**: Toggle to enable/disable duplicate removal
    - **Enable redundant wildcard removal**: Toggle to enable/disable wildcard coverage analysis (requires optimization to be enabled)

**Example optimization:**

Before:
```
script-src 'self' data: *.example.com www.example.com api.example.com data: 'unsafe-inline'
```

After (with both options enabled):
```
script-src 'self' 'unsafe-inline' data: *.example.com
```

The optimization removes:
- Duplicate `data:` entry
- `www.example.com` and `api.example.com` (covered by `*.example.com`)

When debug mode is enabled, the module logs details about removed entries and bytes saved.

### Redundancy Detection

The whitelist grid includes visual indicators to help identify duplicate and redundant entries:

**Status Indicators:**
- **Unique** (green): The entry is unique within its policy directive
- **Duplicate** (yellow): An exact duplicate of another entry exists
- **Redundant** (orange): The entry is covered by a wildcard pattern (e.g., `www.example.com` when `*.example.com` exists)
- **N/A** (gray): Not applicable for non-host value types (hash, nonce, keyword)

**Filtering and Sorting:**

Both the **Hash Validation** and **Redundancy Status** columns support:
- **Filtering**: Use the dropdown filter to show only entries with a specific status
- **Sorting**: Click the column header to sort entries by their status

This helps you quickly identify and clean up redundant whitelist entries to keep your CSP configuration optimized.

**Example Use Cases:**
1. Filter by "Duplicate" to find and remove duplicate entries
2. Filter by "Redundant" to find entries that can be safely removed because they're covered by wildcards
3. Sort by "Hash Validation" to group invalid hashes together for review

## Support
If you encounter any issues or have questions, please contact the author or open an issue on GitHub.

## License
This module is licensed under the MIT License - see the LICENSE file for details.

## Author

Volodymyr Hryvinskyi  
Email: volodymyr@hryvinskyi.com  
GitHub: https://github.com/hryvinskyi
