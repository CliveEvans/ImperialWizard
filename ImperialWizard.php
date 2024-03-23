<?php
/**
 * A MediaWiki skin to use Twitter's Bootstrap.
 * Loosely based on the Bootstrap skin by Aaron Parecki <aaron@parecki.com>
 * but completely rewritten to support Bootstrap 2.0
 * and with a load of additional features.
 *
 * @Version 0.1.0
 * @Author Ian Thomas <ian@wildwinter.net>
 */

if (!defined('MEDIAWIKI')) {
    die(-1);
}

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Logger\LoggerFactory;

/**
 * Inherit main code from SkinTemplate, set the CSS and template filter.
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class SkinImperialWizard extends SkinTemplate
{
}

/**
 * @todo document
 * @package MediaWiki
 * @subpackage Skins
 */
class ImperialWizardTemplate extends BaseTemplate
{
    /**
     * @var Cached skin object
     */
    var $skin;

    function breakTitle(&$link, &$title)
    {
        if (preg_match('/(.+)\|(.+)/', $link, $match)) {
            $link = $match[1];
            $title = $match[2];
        } else {
            $title = $link;
        }
    }

    function getCredits()
    {
        return $this->data['credits'];
    }

    function parseMenu($pageTitle)
    {
        $nav = array();
        $data = $this->getPageRawText($pageTitle);
        foreach (explode("\n", $data) as $line) {
            if (trim($line) == '') continue;

            if (preg_match('/^\*\s*\[\[(.+)\]\]/', $line, $match)) {
                $nav[] = array('title' => $match[1], 'link' => $match[1]);
            } elseif (preg_match('/\*\*\s*\[\[(.+)\]\]/', $line, $match)) {
                $nav[count($nav) - 1]['sublinks'][] = $match[1];
            } elseif (preg_match('/\*\*\s*\-\-/', $line, $match)) {
                $nav[count($nav) - 1]['sublinks'][] = 'sep';
            } elseif (preg_match('/\*\*\s*=\s*(.+)\s*\=/', $line, $match)) {
                $nav[count($nav) - 1]['sublinks'][] = '=' . $match[1];
            } elseif (preg_match('/^\*\s*(.+)/', $line, $match)) {
                $nav[] = array('title' => $match[1]);
            } elseif (preg_match('/=\s*(.+)\s*=/', $line, $match)) {
                $nav[] = array('section' => $match[1]);
            }
        }

        $out = "";

        foreach ($nav as $topItem) {
            if (array_key_exists('section', $topItem)) {
                $out .= '<li class="nav-header">' . $topItem['section'] . '</li>';
                continue;
            }
            $link = $topItem['title'];
            $this->breakTitle($link, $title);
            $pageTitle = Title::newFromText($link);
            if (array_key_exists('sublinks', $topItem)) {
                $out .= '<li class="dropdown">';
                $out .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">' . $title . '<b class="caret"></b></a>';
                $out .= '<ul class="dropdown-menu">';
                foreach ($topItem['sublinks'] as $subLink) {
                    if ($subLink == 'sep') {
                        $out .= '<li class="divider"> </li>';
                        continue;
                    }
                    if ($subLink[0] == '=') {
                        $out .= '<li class="nav-header">' . substr($subLink, 1) . '</li>';
                        continue;
                    }
                    $this->breakTitle($subLink, $title);
                    $pageTitle = Title::newFromText($subLink);
                    $out .= '<li><a href="' . $pageTitle->getLocalURL() . '">' . $title . '</a>';
                }
                $out .= '</ul>';
                $out .= '</li>';
            } else {
                if (is_object($pageTitle)) {
                    $out .= '<li' . ($this->data['title'] == $link ? ' class="active"' : '') . '><a href="' . $pageTitle->getLocalURL() . '">' . $title . '</a></li>';
                }
            }
        }
        return $out;
    }

    /**
     * Template filter callback for Bootstrap skin.
     * Takes an associative array of data set from a SkinTemplate-based
     * class, and a wrapper for MediaWiki's localization database, and
     * outputs a formatted page.
     *
     * @access private
     */
    public function execute()
    {
        global $wgRequest;

        $isLoggedIn = $this->getSkin()->getUser()->isRegistered();

        $requestedAction = $wgRequest->getVal('action', 'view');

        $isEditing = (strcmp($requestedAction, 'edit') == 0);

        $this->skin = $this->data['skin'];

        $title = $this->getSkin()->getTitle();
        if (strpos($title, '/') === false) {
            $this->data['ImpWiztitle'] = $title;
        } else {
            $this->data['ImpWiztitle'] = strrchr($title, '/');
        }

        // Output HTML Page
        $html = $this->getNavbarContent($isLoggedIn);

        $html .= Html::openElement('div', ['id' => 'article', 'class' => 'container-fluid']);
        $html .= Html::openElement('div', ['class' => 'row-fluid']); // row-fluid outer
        $html .= Html::openElement('div', ['id' => 'leftbar', 'class' => 'span2']);

        $logo = MediaWikiServices::getInstance()->getRepoGroup()->findFile(Title::makeTitle(NS_FILE, 'Logo.jpg'));
        if ($logo) {
            $html .= Html::rawElement('div', ['id' => 'logo'], Html::rawElement('img', ['src' => $logo . getURL()]));
        }

        if ($isLoggedIn) {
            $html .= Html::rawElement('div', ['id' => 'pageButtons'], $this->renderPageButtons($isEditing));
        }

        $html .= Html::rawElement('div', ['class' => 'well sidebar-nav'], $this->includePage('Imperial:LeftBar'));

        $html .= Html::closeElement('div'); // span2

        $html .= Html::openElement('div', ['class' => 'span10']);

        $html .= $this->getCategories();

        $html .= Html::openElement('div', ['class' => 'row-fluid']);

        $html .= $this->data['sitenotice'] ? Html::rawElement('div', ['class' => 'alert alert-block alert-message warning'], $this->data['sitenotice']) : '';

        $html .= Html::openElement('div', ['id' => 'page-title', 'class' => 'page-header']);

        $html .= Html::rawElement('h1', [],
            $this->data['ImpWiztitle'] .
            Html::rawElement('small', [], $this->html('subtitle'))
        );

        if (isset($this->data['breadcrumbs'])) {
            $html .= Html::rawElement('ul', ['class' => 'breadcrumb'], $this->getBreadcrumbs());
        }

        $html .= Html::closeElement('div'); // page-header
        $html .= '<!-- end page-header -->';
        $html .= Html::closeElement('div'); // row-fluid

        // the actual page content ...

        $html .= Html::rawElement('div', ['class' => 'row-fluid'],
            $this->get('bodytext') .
            Html::rawElement('hr') .
            Html::rawElement('small', [], $this->getCredits())
        );

        $html .= Html::closeElement('div'); // span10

        $html .= Html::closeElement('div'); // row-fluid outer
        $html .= Html::closeElement('div'); // container-fluid

//        $html .= Html::rawElement('div', ['id' => 'footer', 'class' => 'container-fluid'], $this->includePage('Imperial:Footer'));

        $html .= $this->html('dataAfterContent');
        $html .= $this->getTrail();


        // srsly people? This is how we do this?
        echo $html;
    }

    function getPageRawText($title)
    {
        $pageTitle = Title::newFromText($title);
        $page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle($pageTitle);
        if (!$pageTitle->exists()) {
            return 'Create the page [[' . $title . ']]';
        } else {
            // $page = $this->getSkin()->getWikiPage();
            $revision = $page->getRevisionRecord();
            $content = $revision->getContent(SlotRecord::MAIN, RevisionRecord::RAW);
            return $content->getText();
        }
    }

    function includePage($title)
    {
        $parser = MediaWikiServices::getInstance()->getParser();
        $pageTitle = Title::newFromText($title);
        if (!$pageTitle->exists()) {
            return 'The page [[' . $title . ']] was not found.';
        } else {
            $page = WikiPage::factory($pageTitle);
            $revision = $page->getRevisionRecord();
            $user = $this->getSkin()->getUser();
            $content = $revision->getContent(SlotRecord::MAIN, RevisionRecord::FOR_THIS_USER, $this->getSkin()->getAuthority());

            $wgParserOptions = new ParserOptions($user);
            $parserOutput = $parser->parse($content->getText(), $pageTitle, $wgParserOptions);
            return $parserOutput->getText();
        }
    }

    function renderPageButton($key, $icon)
    {
        if (!array_key_exists($key, $this->data['content_actions']))
            return;
        $action = $this->data['content_actions'][$key];
        echo '<a class="btn" href="' . htmlspecialchars($action['href']) . '" title="' . htmlspecialchars($action['text']) . '"><i class="' . $icon . '"></i></a>';
    }

    function renderPageButtons($isEditing)
    {
        if (count($this->data['content_actions']) == 0)
            return false;

        echo '<div class="btn-group">';
        if (!$isEditing)
            $this->renderPageButton('edit', 'icon-edit');
        $this->renderPageButton('history', 'icon-time');
        $this->renderPageButton('delete', 'icon-trash');
        $this->renderPageButton('move', 'icon-move');
        $this->renderPageButton('protect', 'icon-lock');
        $this->renderPageButton('watch', 'icon-eye-open');
        $this->renderPageButton('unwatch', 'icon-eye-close');
        $this->renderPageButton('talk', 'icon-comment');
        echo '</div>';

        return true;

    }

    public function getNavbarContent($isLoggedIn): string
    {
        $html = Html::openElement('div', ['class' => 'navbar navbar-fixed-top']);
        $html .= Html::openElement('div', ['class' => 'navbar-inner']);

        $html .= Html::openElement('div', ['class' => 'container-fluid']);

        $html .= Html::rawElement('a', ['class' => 'btn btn-navbar', 'data-toggle' => 'collapse', 'data-target' => '.nav-collapse'],
            Html::rawElement('i', ['class' => 'icon-search icon-white'])
        );

        $html .= Html::rawElement('a', ['class' => 'brand', 'href' => $this->data['nav_urls']['mainpage']['href']], 'Empire');

        $html .= Html::openElement('div', ['class' => 'nav-collapse']);
        $html .= Html::rawElement('form', ['class' => 'pull-right navbar-search', 'action' => $this->text('wgScript'), 'id' => 'searchform'],
            $this->makeSearchInput(['id' => 'searchInput']),
            Html::hidden('title', $this->get('searchtitle')) .
            $this->makeSearchButton(
                'fulltext',
                ['id' => 'mw-searchButton', 'class' => 'searchButton mw-fallbackSearchButton']
            ) .
            $this->makeSearchButton(
                'go',
                ['id' => 'searchButton', 'class' => 'searchButton']
            )
        );
        $html .= Html::rawElement('ul', ['class' => 'nav'], $this->parseMenu('Imperial:TitleBar'));

        if ($isLoggedIn) {
            $html .= $this->getUserDropdown();
        }

        $html .= Html::closeElement('div'); // nav-collapse
        $html .= Html::closeElement('div'); // navbar-inner
        $html .= Html::closeElement('div'); // container-fluid
        $html .= Html::closeElement('div');
        return $html;
    }

    function getCategories()
    {
        $catlinks = $this->getCategoryLinks();
        if (!empty($catlinks)) {
            return '<div id="pageCategories"><ul class="pager">' . $catlinks . '</ul></div>';
        }
    }

    function getCategoryLinks()
    {
        global $wgOut;

        $out = $wgOut;

        $allCats = $out->getCategoryLinks();
        if (count($allCats) == 0) {
            return '';
        }

        $embed = "<li>";
        $pop = "</li>";

        if (!empty($allCats['normal'])) {
            return $embed . implode("{$pop}{$embed}", $allCats['normal']) . $pop;
        }

        return '';
    }

    public function getUserDropdown(): string
    {
        $listAttributes = $this->html('userlangattributes');
        $listAttributes['class'] = 'nav pull-right';
        $html = Html::openElement('ul', $listAttributes);
        if (count($this->data['personal_urls']) > 0) {
            $html .= Html::openElement('li', ['class' => 'dropdown']);
            $html .= Html::rawElement('a', ['class' => 'dropdown-toggle', 'href' => '#', 'data-toggle' => 'dropdown'],
                $this->getSkin()->getUser()->getName() .
                Html::rawElement('b', ['class' => 'caret'])
            );

            $html .= Html::openElement('ul', ['class' => 'dropdown-menu']);
            foreach ($this->data['personal_urls'] as $item) {
                $html .= Html::openElement('li', $item['attributes'] ?? '');
                $linkAttributes = ['href' => htmlspecialchars($item['href']), 'class', $item['class'] ?? ''];
                $html .= Html::rawElement('a', $linkAttributes, htmlspecialchars($item['text']));
                $html .= Html::closeElement('li');
            }

            $html .= Html::closeElement('ul');
            $html .= Html::closeElement('li');
        }
        $html .= Html::closeElement('ul');
        return $html;
    }
    public function getBreadcrumbs(): string
    {
        $bc = $this->data['breadcrumbs'];
        $bc = str_replace('<a', '<li><a', $bc);
        $bc = str_replace('/a> &gt;', '/a><span class="divider">/</span></li>', $bc);
        $bc = str_replace('<strong', '<li><strong', $bc);
        $bc = preg_replace('/\/strong\>(.*)$/', '/strong></li>', $bc);
        return $bc;
    }
}

