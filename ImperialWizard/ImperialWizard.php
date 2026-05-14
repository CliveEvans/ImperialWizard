<?php

if (!defined('MEDIAWIKI')) {
    die(-1);
}

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Logger\LoggerFactory;


class SkinImperialWizard extends SkinTemplate
{
}

class ImperialWizardTemplate extends BaseTemplate
{
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
                $out .= '<li class="nav-item"><span class="navbar-text">' . $topItem['section'] . '</span></li>';
                continue;
            }
            $link = $topItem['title'];
            $this->breakTitle($link, $title);
            $pageTitle = Title::newFromText($link);
            if (array_key_exists('sublinks', $topItem)) {
                $out .= '<li class="nav-item dropdown">';
                $out .= '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">' . $title . '</a>';
                $out .= '<ul class="dropdown-menu">';
                foreach ($topItem['sublinks'] as $subLink) {
                    if ($subLink == 'sep') {
                        $out .= '<li><hr class="dropdown-divider"></li>';
                        continue;
                    }
                    if ($subLink[0] == '=') {
                        $out .= '<li><h6 class="dropdown-header">' . substr($subLink, 1) . '</h6></li>';
                        continue;
                    }
                    $this->breakTitle($subLink, $title);
                    $pageTitle = Title::newFromText($subLink);
                    $out .= '<li><a class="dropdown-item" href="' . $pageTitle->getLocalURL() . '">' . $title . '</a></li>';
                }
                $out .= '</ul>';
                $out .= '</li>';
            } else {
                if (is_object($pageTitle)) {
                    $isActive = $pageTitle->equals($this->getSkin()->getTitle());
                    $activeClass = $isActive ? ' active' : '';
                    $out .= '<li class="nav-item"><a class="nav-link' . $activeClass . '" href="' . $pageTitle->getLocalURL() . '">' . $title . '</a></li>';
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
        $isLoggedIn = $this->getSkin()->getUser()->isRegistered();

        $requestedAction = $this->getSkin()->getRequest()->getVal('action', 'view');

        $isEditing = (strcmp($requestedAction, 'edit') == 0);

        $title = $this->getSkin()->getTitle();

        if (strpos($title, '/') === false) {
            $this->data['ImpWiztitle'] = $title;
        } else {
            $this->data['ImpWiztitle'] = strrchr($title, '/');
        }

        // Output HTML Page
        $html = $this->getNavbarContent($isLoggedIn);

        $html .= Html::openElement('div', ['id' => 'article', 'class' => 'container-fluid']);
        $html .= Html::openElement('div', ['class' => 'row']); // outer row
        $html .= Html::openElement('div', ['id' => 'leftbar', 'class' => 'col-md-2']);

        // Below the md breakpoint the sidebar stacks full-width above the
        // content; collapse it behind a toggle button so it doesn't dominate
        // the page on phones. d-md-block keeps the content always visible at
        // md+ regardless of the collapse state.
        $html .= Html::rawElement('button', [
            'class' => 'btn btn-secondary d-md-none w-100 mb-2',
            'type' => 'button',
            'data-bs-toggle' => 'collapse',
            'data-bs-target' => '#leftbar-content',
            'aria-expanded' => 'false',
            'aria-controls' => 'leftbar-content',
        ], 'Show navigation');

        $html .= Html::openElement('div', ['id' => 'leftbar-content', 'class' => 'collapse d-md-block']);

        $logo = MediaWikiServices::getInstance()->getRepoGroup()->findFile(Title::makeTitle(NS_FILE, 'Logo.jpg'));
        if ($logo) {
            $html .= Html::rawElement('div', ['id' => 'logo'], Html::rawElement('img', ['src' => $logo->getUrl()]));
        }

        if ($isLoggedIn) {
            $html .= Html::rawElement('div', ['id' => 'pageButtons'], $this->renderPageButtons($isEditing));
        }

        $html .= Html::rawElement('div', ['class' => 'well sidebar-nav'], $this->includePage('Imperial:LeftBar'));

        $html .= Html::closeElement('div'); // leftbar-content

        $html .= Html::closeElement('div'); // col-md-2

        $html .= Html::openElement('div', ['class' => 'col-md-10']);

        $html .= $this->getCategories();

        $html .= $this->data['sitenotice'] ? Html::rawElement('div', ['class' => 'alert alert-warning'], $this->data['sitenotice']) : '';

        $html .= Html::openElement('div', ['id' => 'page-title', 'class' => 'page-header']);

        $html .= Html::rawElement('h1', [],
            $this->data['ImpWiztitle'] .
            Html::rawElement('small', [], $this->html('subtitle'))
        );

        if (isset($this->data['breadcrumbs'])) {
            $html .= Html::rawElement('ol', ['class' => 'breadcrumb'], $this->getBreadcrumbs());
        }

        $html .= Html::closeElement('div'); // page-header
        $html .= '<!-- end page-header -->';

        // the actual page content ...

        $html .= $this->get('bodytext');
        $html .= Html::rawElement('hr');
        $html .= Html::rawElement('small', [], $this->getCredits());

        $html .= Html::closeElement('div'); // col-md-10

        $html .= Html::closeElement('div'); // outer row
        $html .= Html::closeElement('div'); // container-fluid

        $html .= Html::rawElement('div', ['id' => 'footer', 'class' => 'container-fluid'], $this->includePage('Imperial:Footer'));

        $html .= $this->html('dataAfterContent');

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
            $page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle($pageTitle);
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
            return '';
        $action = $this->data['content_actions'][$key];
        return '<a class="btn" href="' . htmlspecialchars($action['href']) . '" title="' . htmlspecialchars($action['text']) . '"><i class="' . $icon . '"></i></a>';
    }

    function renderPageButtons($isEditing)
    {
        if (count($this->data['content_actions']) == 0)
            return '';

        $out = '<div class="btn-group" role="group">';
        if (!$isEditing)
            $out .= $this->renderPageButton('edit', 'bi bi-pencil');
        $out .= $this->renderPageButton('history', 'bi bi-clock-history');
        $out .= $this->renderPageButton('delete', 'bi bi-trash');
        $out .= $this->renderPageButton('move', 'bi bi-arrows-move');
        $out .= $this->renderPageButton('protect', 'bi bi-lock');
        $out .= $this->renderPageButton('watch', 'bi bi-eye');
        $out .= $this->renderPageButton('unwatch', 'bi bi-eye-slash');
        $out .= $this->renderPageButton('talk', 'bi bi-chat');
        $out .= '</div>';

        return $out;

    }

    public function getNavbarContent($isLoggedIn): string
    {
        $html = Html::openElement('nav', ['class' => 'navbar navbar-expand-lg fixed-top']);
        $html .= Html::openElement('div', ['class' => 'container-fluid']);

        $html .= Html::rawElement('a', ['class' => 'navbar-brand', 'href' => $this->data['nav_urls']['mainpage']['href']], 'Empire');

        $html .= Html::rawElement('button', [
            'class' => 'navbar-toggler',
            'type' => 'button',
            'data-bs-toggle' => 'collapse',
            'data-bs-target' => '#navbar-collapse',
            'aria-controls' => 'navbar-collapse',
            'aria-expanded' => 'false',
            'aria-label' => 'Toggle navigation',
        ], Html::rawElement('span', ['class' => 'navbar-toggler-icon']));

        $html .= Html::openElement('div', ['class' => 'collapse navbar-collapse', 'id' => 'navbar-collapse']);

        $html .= Html::rawElement('ul', ['class' => 'navbar-nav me-auto'], $this->parseMenu('Imperial:TitleBar'));

        if ($isLoggedIn) {
            $html .= $this->getUserDropdown();
        }

        $html .= Html::rawElement('form', ['class' => 'd-flex navbar-search', 'action' => $this->get('wgScript'), 'id' => 'searchform', 'role' => 'search'],
            Html::hidden('title', $this->get('searchtitle')) .
            $this->makeSearchInput(['id' => 'searchInput', 'class' => 'form-control'])
        );

        $html .= Html::closeElement('div'); // navbar-collapse
        $html .= Html::closeElement('div'); // container-fluid
        $html .= Html::closeElement('nav');
        return $html;
    }

    function getCategories()
    {
        $catlinks = $this->getCategoryLinks();
        if (!empty($catlinks)) {
            return '<div id="pageCategories"><ul class="pager">' . $catlinks . '</ul></div>';
        }
        return '';
    }

    function getCategoryLinks()
    {
        $allCats = $this->getSkin()->getOutput()->getCategoryLinks();
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
        if (count($this->data['personal_urls']) === 0) {
            return '';
        }

        $items = '';
        foreach ($this->data['personal_urls'] as $item) {
            $linkClass = 'dropdown-item' . (isset($item['class']) ? ' ' . $item['class'] : '');
            $items .= Html::rawElement('li', [],
                Html::element('a', ['href' => $item['href'], 'class' => $linkClass], $item['text'])
            );
        }

        return Html::rawElement('ul', ['class' => 'navbar-nav'],
            Html::rawElement('li', ['class' => 'nav-item dropdown'],
                Html::rawElement('a', [
                    'class' => 'nav-link dropdown-toggle',
                    'href' => '#',
                    'role' => 'button',
                    'data-bs-toggle' => 'dropdown',
                    'aria-expanded' => 'false',
                ], $this->getSkin()->getUser()->getName())
                . Html::rawElement('ul', ['class' => 'dropdown-menu dropdown-menu-end'], $items)
            )
        );
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

