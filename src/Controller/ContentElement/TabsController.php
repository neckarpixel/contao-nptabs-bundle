<?php

declare(strict_types=1);

namespace Neckarpixel\NpTabsBundle\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tabs als verschachteltes Inhaltselement (gleiches Prinzip wie das Core-Accordion
 * ab Contao 5.7): Das Eltern-Element "Tabs" enthält beliebig viele Kind-Elemente.
 * Jedes Kind wird zu einem Tab, die "Abschnittsüberschrift" (sectionHeadline)
 * des Kindes ist die Beschriftung des Tabs.
 */
#[AsContentElement(type: 'nptabs', category: 'miscellaneous', nestedFragments: true)]
class TabsController extends AbstractContentElementController
{
    private const HEADING_TAGS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly TranslatorInterface $translator,
        private readonly bool $loadDefaultCss,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $fallback = $this->translator->trans('MSC.nptabs_fallback', [], 'contao_default');
        $elements = [];

        /** @var ContentElementReference $reference */
        foreach ($template->get('nested_fragments') ?? [] as $reference) {
            $nestedModel = $reference->getContentModel();

            if (!$nestedModel instanceof ContentModel) {
                $nestedModel = $this->framework->getAdapter(ContentModel::class)->findById($nestedModel);
            }

            if (!$nestedModel instanceof ContentModel) {
                continue;
            }

            $header = StringUtil::deserialize($nestedModel->sectionHeadline, true);
            $text = trim((string) ($header['value'] ?? ''));
            $unit = (string) ($header['unit'] ?? 'h2');
            $number = \count($elements) + 1;

            $elements[] = [
                'tab_id' => 'nptabs-tab-'.$nestedModel->id,
                'panel_id' => 'nptabs-panel-'.$nestedModel->id,
                'header' => '' !== $text ? $text : sprintf($fallback, $number),
                'header_tag' => \in_array($unit, self::HEADING_TAGS, true) ? $unit : 'h2',
                'reference' => $reference,
                'is_active' => false,
            ];
        }

        // Aktiver Tab beim Laden (1-basiert im Backend, 0-basiert intern)
        if ([] !== $elements) {
            $active = max(1, (int) $model->nptabs_activeTab);
            $active = min($active, \count($elements)) - 1;
            $elements[$active]['is_active'] = true;
            $template->set('active_index', $active);
        }

        $template->set('elements', $elements);
        $template->set('tabs_label', $this->buildLabel($model));
        $template->set('load_default_css', $this->loadDefaultCss);

        return $template->getResponse();
    }

    /**
     * Zugänglicher Name der Tab-Liste: eigene Bezeichnung, sonst die Überschrift des Elements.
     */
    private function buildLabel(ContentModel $model): string
    {
        $label = trim((string) $model->nptabs_label);

        if ('' === $label) {
            $headline = StringUtil::deserialize($model->headline, true);
            $label = trim((string) ($headline['value'] ?? ''));
        }

        return trim(strip_tags(StringUtil::decodeEntities($label)));
    }
}
