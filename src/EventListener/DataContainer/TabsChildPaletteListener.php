<?php

declare(strict_types=1);

namespace Neckarpixel\NpTabsBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Stellt sicher, dass Kind-Elemente eines Tabs-Elements das Feld "sectionHeadline"
 * (= Tab-Beschriftung, im Backend "Bereichsüberschrift") in ihrer Palette haben –
 * unabhängig vom Elementtyp.
 *
 * Die Position entspricht dem Core-Accordion: eine eigene Legende
 * "Bereichseinstellungen" (section_legend) ganz oben, vor dem Elementtyp.
 *
 * Ist das Feld bereits in der Palette, passiert nichts.
 */
#[AsCallback(table: 'tl_content', target: 'config.onload')]
class TabsChildPaletteListener
{
    public function __construct(
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(DataContainer|null $dc = null): void
    {
        if (null === $dc || !$dc->id) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        // $dc->id ist nur beim Bearbeiten die ID des Elements selbst (in der Liste die des Eltern-Elements)
        if (null === $request || 'edit' !== $request->query->get('act')) {
            return;
        }

        $row = $this->connection->fetchAssociative('SELECT ptable, pid FROM tl_content WHERE id=?', [$dc->id]);

        if (!$row || 'tl_content' !== $row['ptable']) {
            return;
        }

        $parentType = $this->connection->fetchOne('SELECT type FROM tl_content WHERE id=?', [$row['pid']]);

        if ('nptabs' !== $parentType) {
            return;
        }

        foreach (array_keys($GLOBALS['TL_DCA']['tl_content']['palettes'] ?? []) as $palette) {
            if ('__selector__' === $palette || !\is_string($GLOBALS['TL_DCA']['tl_content']['palettes'][$palette])) {
                continue;
            }

            if (str_contains($GLOBALS['TL_DCA']['tl_content']['palettes'][$palette], 'sectionHeadline')) {
                continue;
            }

            try {
                // Wie beim Core-Accordion: Legende "Bereichseinstellungen" vor dem Elementtyp
                PaletteManipulator::create()
                    ->addLegend('section_legend', 'type_legend', PaletteManipulator::POSITION_BEFORE)
                    ->addField('sectionHeadline', 'section_legend', PaletteManipulator::POSITION_APPEND)
                    ->applyToPalette($palette, 'tl_content')
                ;
            } catch (\Throwable) {
                // Palette ohne "type_legend": Legende ganz an den Anfang setzen
                try {
                    PaletteManipulator::create()
                        ->addLegend('section_legend', null, PaletteManipulator::POSITION_PREPEND)
                        ->addField('sectionHeadline', 'section_legend', PaletteManipulator::POSITION_APPEND)
                        ->applyToPalette($palette, 'tl_content')
                    ;
                } catch (\Throwable) {
                    // Palette bleibt unverändert
                }
            }
        }
    }
}
