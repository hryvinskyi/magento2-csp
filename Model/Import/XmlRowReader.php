<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Import;

use Magento\Framework\DomDocument\DomDocumentFactory;

/**
 * Reads XML files with one `<whitelist>` element per row whose child elements name the fields.
 *
 * External entities and network access are never resolved.
 */
class XmlRowReader implements WhitelistRowReaderInterface
{
    /**
     * @param DomDocumentFactory $domDocumentFactory
     */
    public function __construct(private readonly DomDocumentFactory $domDocumentFactory)
    {
    }

    /**
     * @inheritDoc
     */
    public function supports(string $extension): bool
    {
        return $extension === 'xml';
    }

    /**
     * @inheritDoc
     */
    public function read(string $path): array
    {
        $contents = is_readable($path) ? file_get_contents($path) : false;
        if (!is_string($contents) || $contents === '') {
            throw new \InvalidArgumentException('The XML file cannot be read.');
        }
        $previous = libxml_use_internal_errors(true);
        $document = $this->domDocumentFactory->create();
        $loaded = $document->loadXML($contents, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (!$loaded || $document->documentElement === null) {
            throw new \InvalidArgumentException('The XML file is not well-formed.');
        }

        $rows = [];
        foreach ($document->documentElement->childNodes as $element) {
            if (!$element instanceof \DOMElement || $element->nodeName !== 'whitelist') {
                continue;
            }
            $row = [];
            foreach ($element->childNodes as $field) {
                if ($field instanceof \DOMElement) {
                    $row[$field->nodeName] = $field->textContent;
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
