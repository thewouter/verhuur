<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Utils;

use HtmlSanitizer\SanitizerInterface;

/**
 * This class is a light interface between an external Markdown parser library
 * and the application. It's generally recommended to create these light interfaces
 * to decouple your application from the implementation details of the third-party library.
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class Markdown {
    private $parser;
    private $sanitizer;

    public function __construct(SanitizerInterface $sanitizer) {
        $this->parser = new \Parsedown();
        $this->sanitizer = $sanitizer;
    }

    public function toHtml(string $text): string {
        $html = $this->parser->text($text);
        $safeHtml = $this->sanitizer->sanitize($html);

        return $safeHtml;
    }
}
