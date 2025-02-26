# Advanced Content Security Policy (CSP) module for Magento 2

## Overview

The `Hryvinskyi_Csp` module is a Magento 2 extension that provides additional Content Security Policy (CSP) configurations.
This module allows administrators to manage CSP whitelists from the Magento admin panel 

## Features
 1. CSP Whitelist Management: Administrators can manage CSP whitelists directly from the Magento admin panel.
 2. Module provides store view specific CSP configuration.
 3. Violation Reports: The module collects and displays CSP violation reports, helping administrators identify and address security issues.
 4. Possibility to convert violation reports to whitelist rule with one click. 
 5. Store URL Collector: Automatically collects and adds all storefront URLs to the CSP whitelist. 
 6. Flexible Configuration: The module provides various configuration options to enable or disable specific CSP features. 
 7. Admin Panel Integration: The module integrates with the Magento admin panel, providing a user-friendly interface for managing CSP settings.

## Installation

To install the `Hryvinskyi_Csp` module, use Composer:

```sh
composer require hryvinskyi/magento2-csp
```

After installing the module, enable it and run the necessary setup commands:

```sh
bin/magento module:enable Hryvinskyi_Csp
bin/magento setup:upgrade
bin/magento cache:clean
```

