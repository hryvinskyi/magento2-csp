<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\Csp\Model\Admin;

use Magento\Framework\Escaper;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;

/**
 * Admin messages of the module, HTML-escaped before they are queued.
 *
 * Message text often quotes what a browser reported (a blocked URI, a directive) or what the database said about it,
 * and anyone can send a report. The text is escaped here, once, so the admin page never depends on how a message
 * renderer is configured. Escaping does not encode an entity twice, so a renderer that escapes again shows it as is.
 */
class EscapedMessages
{
    /**
     * @param ManagerInterface $messageManager
     * @param Escaper $escaper
     */
    public function __construct(
        private readonly ManagerInterface $messageManager,
        private readonly Escaper $escaper
    ) {
    }

    /**
     * Tell the admin something succeeded.
     *
     * @param Phrase|string $text
     * @return void
     */
    public function success(Phrase|string $text): void
    {
        $this->messageManager->addSuccessMessage($this->escape($text));
    }

    /**
     * Tell the admin something worth knowing that is not a problem.
     *
     * @param Phrase|string $text
     * @return void
     */
    public function notice(Phrase|string $text): void
    {
        $this->messageManager->addNoticeMessage($this->escape($text));
    }

    /**
     * Warn the admin that something needs their attention.
     *
     * @param Phrase|string $text
     * @return void
     */
    public function warning(Phrase|string $text): void
    {
        $this->messageManager->addWarningMessage($this->escape($text));
    }

    /**
     * Tell the admin something failed.
     *
     * @param Phrase|string $text
     * @return void
     */
    public function error(Phrase|string $text): void
    {
        $this->messageManager->addErrorMessage($this->escape($text));
    }

    /**
     * Tell the admin why an action failed.
     *
     * @param \Exception $exception
     * @return void
     */
    public function failure(\Exception $exception): void
    {
        $this->error($exception->getMessage());
    }

    /**
     * Text safe to print in HTML.
     *
     * @param Phrase|string $text
     * @return string
     */
    private function escape(Phrase|string $text): string
    {
        // For a string, escapeHtmlAttr() without single-quote handling runs the same htmlspecialchars() call as
        // escapeHtml(), and is typed string on every Magento version (escapeHtml() is string|array up to 2.4.8).
        return $this->escaper->escapeHtmlAttr((string)$text, false);
    }
}
