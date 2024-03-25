<?php

namespace MediaWiki\Extension\ImperialWizardExtension;

use MediaWiki\Hook\ParserFirstCallInitHook;

class Hooks implements
    ParserFirstCallInitHook {

    public function onParserFirstCallInit($parser)
    {

        for ($i = 1; $i <= 12; $i++)
            $parser->setHook('span' . $i, [ImperialExtension::class, 'span' . $i]);

        $parser->setHook('row', [ImperialExtension::class, 'row']);
        $parser->setHook('row-fluid', [ImperialExtension::class, 'row-fluid']);
        $parser->setHook('btn-group', [ImperialExtension::class, 'btn-group']);
        $parser->setHook('hero-unit', [ImperialExtension::class, 'herounit']);
        $parser->setHook('nav-list', [ImperialExtension::class, 'navlist']);
        $parser->setHook('nav-item', [ImperialExtension::class, 'navitem']);
        $parser->setHook('nav-header', [ImperialExtension::class, 'navheader']);
        $parser->setHook('nav-dropdown', [ImperialExtension::class, 'navdropdown']);
        $parser->setHook('quote', [ImperialExtension::class, 'quote']);
        $parser->setHook('quote-right', [ImperialExtension::class, 'quoteright']);
        $parser->setHook('label', [ImperialExtension::class, 'label']);
        $parser->setHook('box', [ImperialExtension::class, 'box']);
        $parser->setHook('ic', [ImperialExtension::class, 'ic']);
        $parser->setHook('song', [ImperialExtension::class, 'song']);
        $parser->setHook('parentpage', [ImperialExtension::class, 'parentpage']);
        $parser->setHook('historylink', [ImperialExtension::class, 'historylink']);
    }

}