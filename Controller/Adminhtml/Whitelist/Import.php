<?php
/**
 * Copyright (c) 2025. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Controller\Adminhtml\Whitelist;

use Hryvinskyi\Csp\Api\WhitelistRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Hryvinskyi\Csp\Api\Data\WhitelistInterfaceFactory;
use Magento\MediaStorage\Model\File\Uploader;

class Import extends Action
{
    public function __construct(
        Context $context,
        private readonly Filesystem $filesystem,
        private readonly Csv $csvProcessor,
        private readonly WhitelistInterfaceFactory $whitelistInterfaceFactory,
        private readonly WhitelistRepositoryInterface $whitelistRepository
    ) {
        parent::__construct($context);
    }
    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('hryvinskyi_csp/whitelist/index');

        try {
            if (!isset($_FILES['import_file']) || !$_FILES['import_file']['name']) {
                throw new LocalizedException(__('Please select a file to import.'));
            }

            $fileUploader = $this->_objectManager->create(
                Uploader::class,
                ['fileId' => 'import_file']
            );

            // Set allowed extensions
            $fileUploader->setAllowedExtensions(['csv', 'xml']);

            // Validate file
            $fileUploader->validateFile();

            $importPath = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->getAbsolutePath('import/');

            // Create directory if it doesn't exist
            $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR)->create('import');

            // Save uploaded file
            $result = $fileUploader->save($importPath);

            if (!$result) {
                throw new LocalizedException(__('File was not uploaded.'));
            }

            $filePath = $importPath . $result['file'];
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);

            if ($extension === 'csv') {
                $this->importCsv($filePath);
            } elseif ($extension === 'xml') {
                $this->importXml($filePath);
            }

            $this->messageManager->addSuccessMessage(__('Data has been imported successfully.'));

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error during import: %1', $e->getMessage()));
        }

        return $resultRedirect;
    }

    /**
     * Import data from CSV file
     *
     * @param string $filePath
     * @return void
     * @throws \Exception
     */
    private function importCsv(string $filePath): void
    {
        $data = $this->csvProcessor->getData($filePath);
        if (!$data || !isset($data[0])) {
            throw new LocalizedException(__('The CSV file is empty or has an invalid format.'));
        }

        // Get the headers from the first row
        $headers = $data[0];
        unset($data[0]);

        foreach ($data as $row) {
            if (count($row) !== count($headers)) {
                continue; // Skip invalid rows
            }

            $rowData = array_combine($headers, $row);
            $this->saveWhitelistData($rowData);
        }
    }

    /**
     * Import data from XML file
     *
     * @param string $filePath
     * @return void
     * @throws \Exception
     */
    private function importXml(string $filePath): void
    {
        $xmlData = simplexml_load_string(file_get_contents($filePath));

        if (!$xmlData || !isset($xmlData->whitelist)) {
            throw new LocalizedException(__('The XML file is empty or has an invalid format.'));
        }

        foreach ($xmlData->whitelist as $whitelist) {
            $rowData = [];
            foreach ($whitelist as $key => $value) {
                $rowData[(string)$key] = (string)$value;
            }
            $this->saveWhitelistData($rowData);
        }
    }

    /**
     * Save whitelist data to the database
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    private function saveWhitelistData(array $data): void
    {
        // Create a new whitelist model and set data
        $whitelist = $this->whitelistInterfaceFactory->create();
        $map = [
            'identifier' => 'Identifier',
            'status' => 'Status',
            'policy' => 'Policy',
            'value_type' => 'Value Type',
            'value_algorithm' => 'Value Algorithm',
            'value' => 'Value',
            'store_ids' => 'Store View',
        ];
        foreach ($map as $key => $value) {
            if (isset($data[$value])) {
                $data[$key] = $data[$value];
            }
        }
        $whitelist->setData($data);

        $items = $this->whitelistRepository->getWhitelistByParams(
            $whitelist->getPolicy(),
            $whitelist->getValueType(),
            $whitelist->getValue(),
            $whitelist->getValueAlgorithm()
        );

        if ($items->getTotalCount() > 0) {
            foreach ($items->getItems() as $item) {
                $item->setData($whitelist->getData());
                $this->whitelistRepository->save($item);
            }
        } else {
            $this->whitelistRepository->save($whitelist);
        }
    }

    /**
     * Check admin permissions
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Hryvinskyi_Csp::csp_whitelist');
    }
}