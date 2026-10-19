<?php

declare(strict_types=1);

// Eltern-Element "Tabs": gleiche Struktur wie das Core-Accordion
$GLOBALS['TL_DCA']['tl_content']['palettes']['tabs'] = '{type_legend},type,headline,title;{nptabs_legend},nptabs_label,nptabs_activeTab;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['nptabs_label'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['nptabs_activeTab'] = [
    'inputType' => 'text',
    'default' => 1,
    'eval' => ['rgxp' => 'natural', 'minval' => 1, 'maxlength' => 3, 'tl_class' => 'w50'],
    'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 1],
];
