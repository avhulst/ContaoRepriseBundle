<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests\Dca;

use PHPUnit\Framework\TestCase;

class TlLayoutTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAddsTheFieldToBothPalettes(): void
    {
        // Palettes as shipped by contao/core-bundle 5.7
        $GLOBALS['TL_DCA']['tl_layout']['palettes'] = [
            'default' => '{title_legend},name,type;{header_legend},rows;{column_legend},cols;{sections_legend:hide},sections;{image_legend:hide},lightboxSize,defaultImageDensities;{style_legend},framework,external,combineScripts;{modules_legend},modules;{script_legend},scripts,analytics,externalJs,script;{jquery_legend:hide},addJQuery;{mootools_legend:hide},addMooTools;{static_legend:hide},static;{expert_legend:hide},template,minifyMarkup,viewport,titleTag,cssClass,onload,head',
            'modern' => '{title_legend},name,type;{image_legend:hide},lightboxSize,defaultImageDensities;{modules_legend},template,modules',
        ];

        include __DIR__.'/../../contao/dca/tl_layout.php';

        $dca = $GLOBALS['TL_DCA']['tl_layout'];

        $this->assertSame('checkboxWizard', $dca['fields']['reprise_entrypoints']['inputType']);
        $this->assertTrue($dca['fields']['reprise_entrypoints']['eval']['multiple']);
        $this->assertSame(['type' => 'blob', 'length' => 65535, 'notnull' => false], $dca['fields']['reprise_entrypoints']['sql']);

        $this->assertStringContainsString('{style_legend},framework,external,reprise_entrypoints,combineScripts;', $dca['palettes']['default']);
        $this->assertStringContainsString(';{reprise_legend},reprise_entrypoints;{modules_legend},template,modules', $dca['palettes']['modern']);
    }
}
