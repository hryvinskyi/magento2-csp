# Advanced Content Security Policy (CSP) module for Magento 2

[![Latest Stable Version](https://poser.pugx.org/hryvinskyi/magento2-csp/v/stable)](https://packagist.org/packages/hryvinskyi/magento2-csp)
[![Total Downloads](https://poser.pugx.org/hryvinskyi/magento2-csp/downloads)](https://packagist.org/packages/hryvinskyi/magento2-csp)
[![License](https://poser.pugx.org/hryvinskyi/magento2-csp/license)](https://packagist.org/packages/hryvinskyi/magento2-csp)

`Hryvinskyi_Csp` manages the Content Security Policy of a Magento 2 store from the admin: a whitelist of allowed
sources per store view and area, collection of the violations browsers report, one-click conversion of reports into
whitelist entries, and post-processing of the policy header that keeps it small without changing what it allows.

Upgrading from 1.x? Read the [2.0.0 upgrade notes](CHANGELOG.md#200---2026-10-05) first.

## Features

- **Whitelist** of hosts, schemes and hashes per directive, store view and area (storefront, admin or both).
  Entries are validated: no keywords, no `*`, no CSP3 sub-directives, no stores that do not exist.
- **Violation reports** from the browser's legacy `report-uri` format and the Reporting API, grouped by governing
  directive, blocked value, store view and area; size-limited and rate-limited per client.
- **Conversion** of report groups into whitelist entries. Inline code, eval, `*` and `data:`/`blob:` in directives that
  load code are refused. An existing entry with the same directive, value and area gains the store instead of being
  duplicated.
- **Import** of whitelist entries from CSV (including the grid's own export) and XML.
- **Header optimisation**: duplicate sources, hosts covered by a wildcard, `https://` on secure pages and directives
  that allow exactly what their fallback allows are removed. Every step is checked against an independent CSP3
  evaluator in the test suite. Replacing subdomains with a wildcard is the one opt-in step that widens the policy, and
  only for domains you list as trusted.
- **Header splitting** for policies above a size limit: the parts together allow exactly what the single header allows.
  When that is impossible the header is sent whole and the admin is told.
- **Script hashes**: a console command renders CMS pages, CMS blocks and configuration values as the storefront does
  and allows their inline scripts by hash.
- **Report cleanup** by age or by count, nightly and from the console.
- **Template API**: allow hosts or hashes for the current page from a template.

## Requirements

- Magento 2.4.6 or later (developed against 2.4.6-p13 and later patch releases)
- PHP 8.1 – 8.4 with the `dom`, `intl`, `json`, `libxml` and `mbstring` extensions

## Installation

```bash
composer require hryvinskyi/magento2-csp
bin/magento module:enable Hryvinskyi_Csp
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

## Admin

**System > Content Security Policy** has three pages:

- **Whitelist** – add, edit, import, export and delete entries. Each entry has a directive, a value (a host or scheme
  source, or a base64 digest with its algorithm), store views and an area. The grid flags duplicates, entries covered
  by a wildcard in the same scope, and hashes that do not match their script.
- **Violation Reports** – report groups with their reports. *Convert to Whitelist* allows the value for the group's
  store view and area; *Skip* keeps recording without an alert; *Deny* stops recording.
- **Configuration** – **Stores > Configuration > Security > Content Security Policy**.

Permissions are under **System > Permissions > User Roles > Role Resources > System > Content Security Policy**:
viewing and editing the whitelist, deleting and importing entries, viewing reports, changing their status, and
converting them (converting also needs the whitelist edit permission).

## Violation reports

With **Collect violation reports** on, the report URI of every page points to this module: storefront pages report to
`/csp_report_watch` of their own store view, admin pages to `/csp_report_watch/admin/index` of the admin host. This
replaces the Report URI set in core's Mode groups while it is on. A report is recorded only when its page belongs to
the store view (or admin) that receives it; extension-injected resources are ignored, and query strings and fragments
are removed from stored URLs.

The rate limit counts violations per client IP address. **Behind a proxy or CDN** every request comes from the proxy
unless Magento knows the client's address header; configure it, for example in `app/etc/di.xml`:

```xml
<type name="Magento\Framework\HTTP\PhpEnvironment\RemoteAddress">
    <arguments>
        <argument name="alternativeHeaders" xsi:type="array">
            <item name="x-forwarded-for" xsi:type="string">HTTP_X_FORWARDED_FOR</item>
        </argument>
    </arguments>
</type>
```

## Allowing sources from templates

`Hryvinskyi\Csp\ViewModel\DynamicCspProvider` adds sources to the policy of the page being rendered:

```xml
<block name="map" template="Vendor_Module::map.phtml">
    <arguments>
        <argument name="csp" xsi:type="object">Hryvinskyi\Csp\ViewModel\DynamicCspProvider</argument>
    </arguments>
</block>
```

```php
$block->getData('csp')->addScriptSrc(['https://maps.example.com']);
$block->getData('csp')->addFrameSrc(['https://maps.example.com']);
```

Services can use `Hryvinskyi\Csp\Api\DynamicPolicyRegistryInterface::allow($directive, $hosts, $hashes, $self)`.
Only the directives Magento renders are accepted (`script-src`, `style-src`, `img-src`, `frame-src`, …), never a CSP3
sub-directive such as `script-src-elem`: adding one would stop it falling back to its parent.

Sources added this way apply to the page that rendered the template. Magento keeps them with the cached block HTML,
and Varnish keeps the whole header with the page. **With the built-in full-page cache**, a cache hit renders the
policy again without rendering blocks, so sources added from templates are missing on cached pages; whitelist such
sources in the admin instead.

For inline scripts prefer `Magento\Csp\Api\InlineUtilInterface` / `SecureHtmlRenderer` (core) or, on Hyvä,
`HyvaCsp::registerInlineScript()`. `Hryvinskyi\Csp\ViewModel\CspNonceProvider::getNonce()` returns a per-request nonce
and must only be used on pages that are never cached.

## Script hashes

```bash
bin/magento hryvinskyi:csp:generate-script-hashes [--store=1] [--type=page --type=block --type=config] [--yes]
```

The command renders each active CMS page and block with the CMS template filter of the store view, and reads the
configuration values that apply to it. Every inline script that a hash can allow (no `src`, no `nonce`, a JavaScript
type, no form key or other per-request value) is shown with its hash and, after confirmation or with `--yes`, allowed
for that store view on the storefront. Add `-v` to print each script.

## Header optimisation and splitting

All steps are off by default and work on the final header Magento renders.

| Setting | Effect |
|---|---|
| Optimize policy headers | Master switch; removes duplicate sources |
| Remove hosts covered by a wildcard | `www.example.com` goes when `*.example.com` in the same directive covers it (same scheme, port and path) |
| Remove https:// from hosts on secure pages | `https://cdn.example.com/p` becomes `cdn.example.com/p` on HTTPS pages only |
| Remove redundant directives | A fetch directive goes when its fallback allows exactly the same for every kind of request |
| Replace subdomains of trusted domains with a wildcard | **Widens the policy**: N subdomains of a listed trusted domain become `*.domain` |
| Split large policy headers | Splits above the size limit into headers that together allow exactly the same |

Splitting repeats `report-uri` in every header and copies `default-src` into the directives that fell back to it, so
the headers together are larger than the single header. If one group of related directives alone exceeds the limit,
the header is sent unsplit and **System Messages** shows it: check the response header size against your web server
and proxy limits (`curl -sI https://your-store/ | wc -c`).

## Report cleanup

Enabled by default: every night reports not seen for 30 days are deleted, together with pending report groups left
without reports. Change the mode (by date or by record count) and threshold under **Violation Report Cleanup**.

```bash
bin/magento hryvinskyi:csp:report:clean                        # configured mode and threshold
bin/magento hryvinskyi:csp:report:clean --mode=count -t 1000    # keep the 1000 most recently seen
bin/magento hryvinskyi:csp:report:clean --dry-run               # only count
```

## Support

Open an issue on [GitHub](https://github.com/hryvinskyi/magento2-csp/issues).

## License

MIT

## Author

Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
