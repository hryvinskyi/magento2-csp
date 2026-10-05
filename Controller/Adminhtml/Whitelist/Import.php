<?php
/**
 * Copyright (c) 2025-2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Model\Import\WhitelistImporter;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;

/**
 * Imports whitelist entries from an uploaded CSV or XML file, read in place from PHP's upload directory.
 */
class Import extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_Csp::whitelist_import';
    public const MAX_FILE_BYTES = 2097152;
    private const FILE_FIELD = 'import_file';
    private const MAX_REPORTED_ERRORS = 10;

    /**
     * @param Context $context
     * @param WhitelistImporter $importer
     */
    public function __construct(
        Context $context,
        private readonly WhitelistImporter $importer
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            [$path, $extension] = $this->uploadedFile();
            $result = $this->importer->import($path, $extension);
            $this->messageManager->addSuccessMessage((string)__(
                '%1 whitelist entry(ies) created, %2 updated.',
                $result['created'],
                $result['updated']
            ));
            foreach (array_slice($result['errors'], 0, self::MAX_REPORTED_ERRORS) as $error) {
                $this->messageManager->addErrorMessage($error);
            }
            if (count($result['errors']) > self::MAX_REPORTED_ERRORS) {
                $this->messageManager->addErrorMessage((string)__(
                    '%1 more row(s) could not be imported.',
                    count($result['errors']) - self::MAX_REPORTED_ERRORS
                ));
            }
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
    }

    /**
     * Temporary path and extension of the uploaded file.
     *
     * @return array{string, string}
     * @throws LocalizedException
     */
    private function uploadedFile(): array
    {
        $request = $this->getRequest();
        $file = $request instanceof HttpRequest ? $request->getFiles(self::FILE_FIELD) : null;
        $tmpName = is_array($file) ? ($file['tmp_name'] ?? null) : null;
        $name = is_array($file) ? ($file['name'] ?? null) : null;
        $size = is_array($file) ? ($file['size'] ?? null) : null;
        if (!is_array($file) || ($file['error'] ?? null) !== UPLOAD_ERR_OK
            || !is_string($tmpName) || !is_string($name) || !is_uploaded_file($tmpName)
        ) {
            throw new LocalizedException(__('Choose a file to import.'));
        }
        if (!is_numeric($size) || (int)$size > self::MAX_FILE_BYTES) {
            throw new LocalizedException(__('The file is larger than %1 MB.', self::MAX_FILE_BYTES / 1048576));
        }

        return [$tmpName, strtolower(pathinfo($name, PATHINFO_EXTENSION))];
    }
}
