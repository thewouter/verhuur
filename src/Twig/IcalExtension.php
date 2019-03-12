<?php

declare(strict_types=1);
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Allows transforming text to ICAL content. This handles the ICAL file encoding.
 * Also see https://icalendar.org/
 */
class IcalExtension extends AbstractExtension {
    public function getFilters(): array {
        return array(
             new TwigFilter('ical_escape', array(__CLASS__, 'ical_escapeFilter'), array('is_safe' => array('html'))),
             new TwigFilter('ical_wrap', array(__CLASS__, 'ical_wrapFilter'), array('is_safe' => array('html'))),
        );
    }

    public function getName(): string {
        return 'ical_extension';
    }

    public static function ical_escapeFilter(?string $str, string $to = 'txt'): string {
        if (empty($str)) {
            return '';
        }

        if ($to == 'txt') {
            $str = str_replace(array("<br>\r\n", "<br>\n", "<br>", "<br />\r\n", "<br />\n", "<br />", "<br/>\r\n", "<br/>\n", "<br/>"), "\n", $str);
            $str = strip_tags($str);
            $str = trim($str);
            $str = html_entity_decode($str, ENT_COMPAT, "UTF-8");
        } else {
            if ($to == 'html') {
                $str = trim($str);
            } else {
                throw new \Exception("Invalid ical convert to type \"$to\"");
            }
        }
        $str = str_replace(array("\\", "\r\n", "\n", ',', ';'), array("\\\\", '\n', '\n', '\,', '\;'), $str);
        return $str;
    }

    public static function ical_wrapFilter(string $str): string {
        //ical requires maximum 80 characters per line, so to be safe we choose 75 characters
        //the php wordwrap function does not work correctly in all cases (because of UTF-8), so this mostly does
        //what wordwrap also does.

        //note: ical requires dos line endings(\r\n)
        $lines = explode("\n", $str);
        foreach ($lines as &$line) {
            if (!(bool) preg_match('//u', $line)) {
                //Only if it does not validate as UTF-8
                $line = wordwrap($str, 73, "\r\n ", true);
                continue;
            }
            $rest = $line;
            $line = '';
            while (strlen($rest) > 75) {
                $split = substr($rest, 0, 73);
                //remove characters until it is a valid UTF-8 sequence
                while (!(bool) preg_match('//u', $split)) {
                    $split = substr($split, 0, strlen($split) - 1);
                }
                if (substr($split, strlen($split) - 1, 1) == '\\') {
                    //last character is \, so need to check if escaping is valid
                    //it is only valid when the string ends with \\, so we need
                    //to count the number of \ characters.
                    $num = 1;
                    while (substr($rest, strlen($split) - $num - 1, 1) == '\\') {
                        ++$num;
                    }
                    if (($num % 2) == 1) {
                        $split = substr($split, 0, strlen($split) - 1);
                    } //invalid, so move back one spot
                }
                $line .= $split . "\r\n";
                $rest = ' ' . substr($rest, strlen($split));
            }
            $line .= $rest;
        }
        return implode("\r\n", $lines);
    }
}
