<?php

namespace APP\doi;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\PubIdPlugin;
use APP\publication\Publication;
use APP\server\ServerDAO;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\core\DataObject;
use PKP\galley\Galley;
use PKP\services\PKPSchemaService;
use PKP\submission\Representation;

class Repository extends \PKP\doi\Repository
{
    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        parent::__construct($dao, $request, $schemaService);
    }

    /**
     * Create a DOI for the given publication
     */
    public function mintPublicationDoi(Publication $publication, Submission $submission, Context $context): ?int
    {
        if ($context->getData(Context::SETTING_DOI_SUFFIX_TYPE) === Repo::doi()::SUFFIX_DEFAULT) {
            $doiSuffix = $this->generateDefaultSuffix();
        } else {
            $doiSuffix = $this->generateSuffixPattern($publication, $context, $context->getData(Context::SETTING_DOI_SUFFIX_TYPE), $submission);
        }

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Create a DOI for the given galley
     */
    public function mintGalleyDoi(Galley $galley, Publication $publication, Submission $submission, Context $context): ?int
    {
        if ($context->getData(Context::SETTING_DOI_SUFFIX_TYPE) === Repo::doi()::SUFFIX_DEFAULT) {
            $doiSuffix = $this->generateDefaultSuffix();
        } else {
            $doiSuffix = $this->generateSuffixPattern($galley, $context, $context->getData(Context::SETTING_DOI_SUFFIX_TYPE), $submission, $galley);
        }

        return $this->mintAndStoreDoi($context, $doiSuffix);
    }

    /**
     * Generate a suffix using a provided pattern type
     *
     * @param string $patternType Repo::doi()::CUSTOM_SUFFIX_* constants
     *
     */
    protected function generateSuffixPattern(
        DataObject $object,
        Context $context,
        string $patternType,
        ?Submission $submission = null,
        ?Representation $representation = null
    ): string {
        $doiSuffix = '';
        switch ($patternType) {
            case self::SUFFIX_CUSTOM_PATTERN:
                $pubIdSuffixPattern = $this->getPubIdSuffixPattern($object, $context);
                $publication = $submission !== null ? Repo::publication()->get($submission->getData('currentPublicationId')) : null;
                $doiSuffix = PubIdPlugin::generateCustomPattern($context, $pubIdSuffixPattern, $object, $submission, $publication, $representation);
                break;
            case self::SUFFIX_MANUAL:
                break;
        }

        return $doiSuffix;
    }

    /**
     * Get app-specific DOI type constants to check when scheduling deposit for submissions
     */
    protected function getValidSubmissionDoiTypes(): array
    {
        return [
            self::TYPE_PUBLICATION,
            self::TYPE_REPRESENTATION
        ];
    }

    /**
     * Gets all DOIs associated with an issue
     * NB: Assumes only enabled DOI types are allowed
     *
     */
    public function getDoisForSubmission(int $submissionId): array
    {
        $doiIds = [];

        $submission = Repo::submission()->get($submissionId);
        /** @var Publication[] $publications */
        $publications = [$submission->getCurrentPublication()];

        /** @var ServerDAO $contextDao */
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($submission->getData('contextId'));

        foreach ($publications as $publication) {
            $publicationDoiId = $publication->getData('doiId');
            if (!empty($publicationDoiId) && $context->isDoiTypeEnabled(self::TYPE_PUBLICATION)) {
                $doiIds[] = $publicationDoiId;
            }

            // Galleys
            $galleys = Repo::galley()->getMany(
                Repo::galley()
                    ->getCollector()
                    ->filterByPublicationIds(['publicationIds' => $publication->getId()])
            );

            foreach ($galleys as $galley) {
                $galleyDoiId = $galley->getData('doiId');
                if (!empty($galleyDoiId) && $context->isDoiTypeEnabled(self::TYPE_REPRESENTATION)) {
                    $doiIds[] = $galleyDoiId;
                }
            }
        }

        return $doiIds;
    }

    /**
     * Retrieves an application specific pubObject based on the type as a string
     *
     * @param string $pubObjectType The object type name passed down from the front end. Will be application specific, e.g. 'article' or 'preprint' instead of 'publication'
     *
     * @return DataObject|null Will be one of the application specific pubObjects, e.g. Publication, ArticleGalley, etc.
     */
    public function getPubObjectFromType(string $pubObjectType, int $pubObjectId): ?DataObject
    {
        return match ($pubObjectType) {
            'preprint' => Repo::publication()->get($pubObjectId),
            'galley' => Repo::galley()->get($pubObjectId),
            default => null,
        };
    }

    /**
     * Updates the DOI reference for an application-specific pubObject. Can be removed by setting doiId to null
     *
     * @param DataObject $pubObject Application-specific pubObject, e.g. Publication, ArticleGalley, etc.
     * @param string $pubObjectType The object type name passed down from the front end. Will be application specific, e.g. 'article' or 'preprint' instead of 'publication'
     * @param ?int $doiId Setting to null will remove doiId association
     */
    public function updatePubObjectDoiFromType(DataObject $pubObject, string $pubObjectType, ?int $doiId): void
    {
        $params = ['doiId' => $doiId];

        switch ($pubObjectType) {
            case 'preprint':
                Repo::publication()->edit($pubObject, $params);
                break;
            case 'galley':
                Repo::galley()->edit($pubObject, $params);
                break;
        }
    }

    /**
     *  Gets legacy, user-generated suffix pattern associated with object type and context
     *
     * @return mixed|null
     */
    private function getPubIdSuffixPattern(DataObject $object, Context $context)
    {
        if ($object instanceof Representation) {
            return $context->getData(Repo::doi()::CUSTOM_REPRESENTATION_PATTERN);
        } else {
            return $context->getData(Repo::doi()::CUSTOM_PUBLICATION_PATTERN);
        }
    }
}
