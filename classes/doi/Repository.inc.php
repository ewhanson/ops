<?php

namespace APP\doi;

use APP\core\Request;
use APP\facades\Repo;
use APP\plugins\PubIdPlugin;
use APP\preprint\PreprintGalley;
use APP\publication\Publication;
use APP\submission\Submission;
use PKP\context\Context;
use PKP\core\DataObject;
use PKP\services\PKPSchemaService;
use PKP\submission\Representation;

class Repository extends \PKP\doi\Repository
{
    public const TYPE_PUBLICATION = 'publication';
    public const TYPE_REPRESENTATION = 'representation';

    public function __construct(DAO $dao, Request $request, PKPSchemaService $schemaService)
    {
        parent::__construct($dao, $request, $schemaService);
    }

    /**
     * Create a DOI for the given publication
     */
    public function mintPublicationDoi(Publication $publication, Submission $submission, Context $context): ?int
    {
        // TODO: #doi Implement
    }

    /**
     * Create a DOI for the given galley
     */
    public function mintGalleyDoi(PreprintGalley $galley, Publication $publication, Submission $submission, Context $context): ?int
    {
        // TODO: #doi Implement
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
            case self::SUFFIX_DEFAULT_PATTERN:
                $doiSuffix = PubIdPlugin::generateDefaultPattern($context, $submission, $representation);
                break;
            case self::SUFFIX_CUSTOM_PATTERN:
                $pubIdSuffixPattern = $this->getPubIdSuffixPattern($object, $context);
                $publication = $submission !== null ? Repo::publication()->get($submission->getData('currentPublicationId')) : null;
                $doiSuffix = PubIdPlugin::generateCustomPattern($context, $pubIdSuffixPattern, $object, $submission, $publication, $representation);
                break;
            case self::CUSTOM_SUFFIX_MANUAL:
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
        // TODO: #doi Implement
    }

    /**
     *  Gets legacy, user-generated suffix pattern associated with object type and context
     *
     * @return mixed|null
     */
    private function getPubIdSuffixPattern(DataObject $object, Context $context)
    {
        if ($object instanceof Representation) {
            return $context->getData(Repo::doi()::LEGACY_CUSTOM_REPRESENTATION_PATTERN);
        } else {
            return $context->getData(Repo::doi()::LEGACY_CUSTOM_PUBLICATION_PATTERN);
        }
    }
}
