<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_layout']['fields']['reprise_entrypoints'] = [
	'inputType' => 'checkboxWizard',
	'eval' => ['multiple' => true, 'tl_class' => 'clr'],
	'sql' => ['type' => 'blob', 'length' => 65535, 'notnull' => false],
];

PaletteManipulator::create()
	->addField('reprise_entrypoints', 'external', PaletteManipulator::POSITION_AFTER)
	->applyToPalette('default', 'tl_layout')
;

PaletteManipulator::create()
	->addLegend('reprise_legend', 'modules_legend', PaletteManipulator::POSITION_BEFORE)
	->addField('reprise_entrypoints', 'reprise_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('modern', 'tl_layout')
;
