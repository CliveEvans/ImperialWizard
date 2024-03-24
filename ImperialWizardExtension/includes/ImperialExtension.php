<?php

namespace MediaWiki\Extension\ImperialWizardExtension;

use Html;
use MediaWiki\MediaWikiServices;
use Parser;
use PPFrame;
use StripState;
use Xml;
use Sanitizer;

class ImperialExtension
{

    public static function ic($input, array $args, Parser $parser, PPFrame $frame)
    {
        echo "rendering an ic tag";
        $out = '<div class="ic"><div class="ic-inner">';
        $out .= $parser->recursiveTagParse($input);
        $out .= '</div></div>';
        return $out;
    }

    public static function parentpage($input, array $args, Parser $parser, PPFrame $frame)
    {
        if (defined("PD_PARENTPAGE")) {
            return PD_PARENTPAGE;
        } else {
            return $parser->getTitle();
        }
    }

    public static function historylink($input, array $args, Parser $parser, PPFrame $frame)
    {
        if (defined("PD_PARENTPAGE")) {
            $link = PD_PARENTPAGE;
        } else {
            $link = $parser->getTitle();
        }
        return '<a href="/mediawiki-public/index.php?action=history&title=' . $link . '">' . $input . '</a>';
    }

    public static function box($input, array $args, Parser $parser, PPFrame $frame)
    {
        $out = '<div class="box"><div class="box-inner">';
        $out .= $parser->recursiveTagParse($input);
        $out .= '</div></div>';
        return $out;
    }

    public static function song($input, array $args, Parser $parser, PPFrame $frame)
    {
        $out = '<div class="song">';
        $out .= $parser->recursiveTagParse($input);
        $out .= '</div>';
        return $out;
    }

    public static function label($input, array $args, Parser $parser, PPFrame $frame)
    {
        $out = '<span class="label';
        if ($args['type']) {
            $out .= ' label-' . $args['type'];
        }
        $out .= '">' . $parser->recursiveTagParse($input) . '</span>';
        return $out;
    }

    public static function herounit($input, array $args, Parser $parser, PPFrame $frame)
    {
        $out = '<div class="hero-unit"';
        if ($args['image']) {
            $img = wfFindFile(Title::makeTitle(NS_IMAGE, $args['image']));
            if ($img) {
                $url = $img->getURL();
                $out .= " style=\"background-image:url('" . $url . "');\"";
            }
        }
        $out .= ">";
        $out .= $parser->recursiveTagParse($input);
        $out .= "</div>";
        return $out;
    }

    public static function navdropdown($input, array $args, Parser $parser, PPFrame $frame)
    {
        $out = '<li class="dropdown">';
        $out .= '<a class="dropdown-toggle" href="#" data-toggle="dropdown">' . $args['title'] . '<b class="caret"></b></a>';
        $out .= '<ul class="dropdown-menu">';
        $out .= $parser->recursiveTagParse($input);
        $out .= "</ul></li>";
        return $out;
    }

    public static function quote($input, array $args, Parser $parser, PPFrame $frame)
    {
        if (isset($args['by']))
            return '<div class="ic"><div class="ic-inner quote"><p>' . $parser->recursiveTagParse($input) . '</p><small>' . $args['by'] . '</small></div></div>';
        return '<div class="ic"><div class="ic-inner quote"><p>' . $parser->recursiveTagParse($input) . '</p></div></div>';
    }

    public static function quoteright($input, array $args, Parser $parser, PPFrame $frame)
    {
        if (isset($args['by']))
            return '<div class="ic"><div class="ic-inner quote" style="text-align:right;"><p>' . $parser->recursiveTagParse($input) . '</p><small>' . $args['by'] . '</small></div></div>';
        return '<div class="ic"><div class="ic-inner quote" style="text-align:right;"><p>' . $parser->recursiveTagParse($input) . '</p></div></div>';
    }


    public static function navlist($input, array $args, Parser $parser, PPFrame $frame)
    {
        return '<ul class="nav nav-list">' . $parser->recursiveTagParse($input) . '</ul>';
    }

    public static function navitem($input, array $args, Parser $parser, PPFrame $frame)
    {
        return '<li>' . $parser->recursiveTagParse($input) . '</li>';
    }

    public static function navheader($input, array $args, Parser $parser, PPFrame $frame)
    {
        return '<li class="nav-header">' . $parser->recursiveTagParse($input) . '</li>';
    }

    public static function __callStatic($name, $fargs)
    {
        $parser = MediaWikiServices::getInstance()->getParser();

        $input = $fargs[0];

        $class = FALSE;
        if (is_array($fargs[1])) {
            if (array_key_exists('class', $fargs[1])) {
                $class = $fargs[1]['class'];
            }
        }

        return '<div class="' . $name . ($class ? ' ' . $class : '') . '">' . $parser->recursiveTagParse($input) . '</div>';
    }

    public static function onSkinEditSectionLinks($skin, $title, $section, $tooltip, &$links, $lang)
    {
        $links['editsection']['attribs']['class'] = 'hidden';

        $links['editicon'] = [
            'text' => '',
            'targetTitle' => $title,
            'attribs' => ['class' => 'icon-edit', 'aria-hidden' => 'true'],
            'query' => ['action' => 'edit', 'section' => $section],
            'options' => ['noclasses', 'known']
        ];

        $links['topicon'] = [
            'text' => '',
            'targetTitle' => $title,
            'attribs' => ['class' => 'icon-arrow-up', 'aria-hidden' => 'true'],
            'query' => ['action' => 'view', 'section' => '0'],
            'options' => ['noclasses', 'known']
        ];
    }

    private static function ModifyLink(&$text, &$attribs, $isExternal = 0)
    {
        if (preg_match('/^(.+)\(\((.*)\)\)$/', $text, $matches)) {
            $text = trim($matches[1]);
            $rels = preg_split('/\s+/', $matches[2]);

            foreach ($rels as $r) {
                if ($isExternal && (strtolower($r) == '-nofollow'))
                    continue; # Not allowed!!

                if ((substr($r, 0, 2) == '-~' || substr($r, 0, 2) == '~-') && isset($attribs['rev']))
                    $attribs['rev'] = str_ireplace(substr($r, 2), '', $attribs['rev']);
                elseif ((substr($r, 0, 2) == '-.' || substr($r, 0, 2) == '.-') && isset($attribs['class']))
                    $attribs['class'] = str_ireplace(substr($r, 2), '', $attribs['class']);
                elseif ((substr($r, 0, 1) == '-') && isset($attribs['rel']))
                    $attribs['rel'] = str_ireplace(substr($r, 1), '', $attribs['rel']);
                elseif (substr($r, 0, 1) == '~')
                    $attribs['rev'] .= ' ' . substr($r, 1);
                elseif (substr($r, 0, 1) == '.')
                    $attribs['class'] .= ' ' . substr($r, 1);
                else
                    $attribs['rel'] .= ' ' . $r;
            }

            if (isset($attribs['rel']))
                $attribs['rel'] = trim(preg_replace('/\s+/', ' ', $attribs['rel']));
            if (isset($attribs['rev']))
                $attribs['rev'] = trim(preg_replace('/\s+/', ' ', $attribs['rev']));
            if (isset($attribs['class']))
                $attribs['class'] = trim(preg_replace('/\s+/', ' ', $attribs['class']));
        }
    }

    public static function renderInternalLink(&$url, &$text, &$link, &$attribs, $linktype)
    {
        self::ModifyLink($text, $attribs);
        return true;
    }

    public static function renderExternalLink(&$url, &$text, &$link, &$attribs, $linktype)
    {
        $attribsText = Html::expandAttributes(array('class' => 'external ' . $linktype));
        $mergedattribs = array_merge($attribs, Sanitizer::decodeTagAttributes($attribsText));

        self::ModifyLink($text, $mergedattribs, 1);
        if ($mergedattribs)
            $attribsText = Xml::expandAttributes($mergedattribs);

        $link = sprintf('<a href="%s"%s>%s<i class="icon-share-alt"></i></a>', $url, $attribsText, $text);

        return false;
    }

    public static function onGetDoubleUnderscoreIDs(&$ids)
    {
        $ids[] = 'NOTITLE';
    }

    public static function onParserAfterParse(Parser $parser, &$text, StripState $stripState)
    {
        if ($parser->getOutput()->getPageProperty('NOTITLE') !== null) {
            $parser->getOutput()->addHeadItem('<style type="text/css">/*<![CDATA[*/ #page-title { display:none; } /*]]>*/</style>');
        }
        return true;
    }
}

