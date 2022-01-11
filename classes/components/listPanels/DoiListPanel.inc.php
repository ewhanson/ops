<?php

/**
 * @file classes/components/listPanels/DoiListPanel.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoiListPanel
 * @ingroup classes_components_list
 *
 * @brief A ListPanel component for viewing and editing DOIs
 */

namespace APP\components\listPanels;

use APP\core\Application;
use APP\i18n\AppLocale;
use APP\template\TemplateManager;
use PKP\components\listPanels\PKPDoiListPanel;
use PKP\plugins\HookRegistry;
use PKP\submission\PKPSubmission;

class DoiListPanel extends PKPDoiListPanel
{
    public function getConfig()
    {
        $config = parent::getConfig();

        $config['executeActionApiUrl'] = $this->doiApiUrl . '/submissions';
        $config['filters'][] = [
            'heading' => 'Publication Status',
            'filters' => [
                [
                    'title' => 'Published',
                    'param' => 'status',
                    'value' => (string) PKPSubmission::STATUS_PUBLISHED
                ],
                [
                    'title' => 'Unpublished',
                    'param' => 'status',
                    'value' => PKPSubmission::STATUS_QUEUED . ', ' . PKPSubmission::STATUS_SCHEDULED
                ]
            ]
        ];
        // Provide required locale keys
        AppLocale::requireComponents(
            [
                LOCALE_COMPONENT_APP_MANAGER
            ]
        );
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setLocaleKeys([
            'manager.dois.formatIdentifier.file'
        ]);

        HookRegistry::call('DoiListPanel::setConfig', [&$config]);

        // Check added here in case hook adds additional getParams
        $config['getParams'] = empty($config['getParams']) ? new \stdClass() : $config['getParams'];

        return $config;
    }
}
