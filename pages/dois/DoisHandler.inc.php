<?php

/**
 * @file /pages/dois/DoiManagementHandler.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DoisHandler
 * @ingroup pages_doi
 *
 * @brief Handle requests for DOI management functions.
 */

use APP\components\forms\context\DoiSetupSettingsForm;
use APP\components\listPanels\DoiListPanel;
use PKP\components\forms\context\PKPDoiRegistrationSettingsForm;
use PKP\components\forms\context\PKPDoiSetupSettingsForm;

import('lib.pkp.pages.dois.PKPDoisHandler');

class DoisHandler extends PKPDoisHandler
{
    /**
     * @param array $args
     * @param \PKP\handler\PKPRequest $request
     */
    public function index($args, $request)
    {
        $this->setupTemplate($request);

        $context = $request->getContext();
        $contextId = $context->getId();

        $enabledDoiTypes = $context->getData(Context::SETTING_ENABLED_DOI_TYPES);

        $templateMgr = TemplateManager::getManager($request);

        $commonArgs = [
            'doiPrefix' => $context->getData(Context::SETTING_DOI_PREFIX),
            'doiApiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'dois'),
            'lazyLoad' => true,
            'enabledDoiTypes' => $enabledDoiTypes,
            'registrationAgencyInfo' => $this->_getRegistrationAgencyInfo($context),
        ];

        HookRegistry::call('DoisHandler::setListPanelArgs', [&$commonArgs]);

        $stateComponents = [];

        if (!empty($enabledDoiTypes)) {
            $submissionDoiListPanel = new DoiListPanel(
                'submissionDoiListPanel',
                __('plugins.pubIds.doi.manager.preprintDois'),
                array_merge(
                    $commonArgs,
                    [
                        'apiUrl' => $request->getDispatcher()->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'submissions'),
                        'getParams' => [
                            'stageIds' => [WORKFLOW_STAGE_ID_PUBLISHED, WORKFLOW_STAGE_ID_PRODUCTION],
                        ],
                        'itemType' => 'submission'
                    ]
                )
            );
            $stateComponents[$submissionDoiListPanel->id] = $submissionDoiListPanel->getConfig();
        }

        // DOI settings
        $router = $request->getRouter();
        $dispatcher = $request->getDispatcher();

        $contextApiUrl = $dispatcher->url($request, PKPApplication::ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());
        $supportedFormLocales = $context->getSupportedFormLocales();
        $localeNames = AppLocale::getAllLocales();
        $locales = array_map(function ($localeKey) use ($localeNames) {
            return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
        }, $supportedFormLocales);

        $doiSetupSettingsForm = new DoiSetupSettingsForm($contextApiUrl, $locales, $context);
        $doiRegistrationSettingsForm = new PKPDoiRegistrationSettingsForm($contextApiUrl, $locales, $context);
        $stateComponents[PKPDoiSetupSettingsForm::FORM_DOI_SETUP_SETTINGS] = $doiSetupSettingsForm->getConfig();
        $stateComponents[PKPDoiRegistrationSettingsForm::FORM_DOI_REGISTRATION_SETTINGS] = $doiRegistrationSettingsForm->getConfig();

        $templateMgr->setState(['components' => $stateComponents]);

        $templateMgr->assign([
            'pageTitle' => __('plugins.pubIds.doi.manager.displayName'),
            'displayPreprintsTab' => !empty($enabledDoiTypes),
        ]);

        $templateMgr->display('management/dois.tpl');
    }
}
