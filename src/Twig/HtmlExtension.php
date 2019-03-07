<?php

declare(strict_types=1);
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\Environment;
use Parsedown;

/**
 * Defines a set of functions for handling HTML content in Twig templates
 */
class HtmlExtension extends AbstractExtension {
    public function getFilters(): array {
        return array(
            new TwigFilter('html_entity_decode', array(__CLASS__, 'html_entity_decodeFilter')),
            new TwigFilter('br2nl', array(__CLASS__, 'br2nlFilter')),
            new TwigFilter('submenu_item', array(__CLASS__, 'submenu_itemFilter'), array('needs_environment' => true, 'is_safe' => array('html'), 'pre_escape' => 'html')),
            new TwigFilter('urlify', array(__CLASS__, 'urlifyFilter')),
            new TwigFilter('parsedown', array(__CLASS__, 'parsedownFilter'), array('is_safe' => array('html'), 'pre_escape' => 'html')),
        );
    }

    public function getName(): string {
        return 'html_extension';
    }

    public static function html_entity_decodeFilter(string $string): string {
        return html_entity_decode($string, ENT_COMPAT, "UTF-8");
    }

    public static function br2nlFilter(string $string): string {
        return str_replace(array("<br>\r\n", "<br>\n", "<br>", "<br />\r\n", "<br />\n", "<br />", "<br/>\r\n", "<br/>\n", "<br/>"), "\n", $string);
    }

    public static function submenu_itemFilter(Environment $env, string $text, ?string $icon, ?string $icon_alt, $route_match = null, ?string $add_class = null, ?string $link = null): string {
        $match = false;
        if ($route_match === true) {
            $match = true;
        } else if ($route_match) {
            $env_globals = $env->getGlobals();
            $match = preg_match("/$route_match/", $env_globals['app']->getRequest()->attributes->get('_route'));
        }
        if (!$icon) {
            $icon = 'angle-right';
        }
        $str = '';
        if ($link) {
            $str .= '<a href="' . $link . '"';
        } else {
            $str = '<div';
        }
        $str .= ' class="submenuItem';
        if ($match) {$str .= ' selected_submenu';}
        if ($add_class) $str .= ' ' . $add_class;
        $str .= '">';
        $str .= '<span class="fas fa-' . $icon . '" title="' . $icon_alt . '"></span>';
        $str .= '<span>' . $text . '</span></';
        if ($link) {
            $str .= 'a>';
        } else {
            $str .= 'div>';
        }
        return $str;
    }

    public static function urlify(string $str): string {
        // Convert to ascii (eg ä -> a)
        $str = transliterator_transliterate('Any-Latin;Latin-ASCII;', $str);
        // Convert to lowercase
        $str = strtolower($str);
        // Throw away all characters NOT a-z, 0-9, _, -, . or space
        //    eg: that's -> thats
        $str = preg_replace('/[^a-z0-9_\-. ]/', '', $str);
        // Replace all characters (_, -, ., space) around a - or space with a single -
        //    eg: abc- .def -> abc-def
        //    eg: abc.def -> abc.def
        $str = preg_replace('/[^a-z0-9]*[\- ][^a-z0-9]*/', '-', $str);
        // Remove all non a-z, 0-9 characters from the beginning and end of the string
        $str = trim($str, "_-.");
        return $str;
    }

    public static function urlifyFilter(string $str): string {
        return self::urlify($str);
    }

    public static function parsedownFilter(string $str): string {
        $pd = new Parsedown();
        return $pd->text($str);
    }
}
