<?php

namespace App\Serializer;

use App\Entity\Company;
use App\Utils\UploaderHelper;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class LogoNormalizer implements ContextAwareNormalizerInterface
{
    private $normalizer;
    private $uploaderHelper;

    public function __construct(ObjectNormalizer $normalizer, UploaderHelper $uploaderHelper)
    {
        $this->normalizer = $normalizer;
        $this->uploaderHelper = $uploaderHelper;
    }

    public function normalize($company, $format = null, array $context = [])
    {
        if($company->getLogo())
            $company->setLogo($this->uploaderHelper->getPublicPath($company->getLogo()));

        $data = $this->normalizer->normalize($company, $format, $context);

        return $data;
    }

    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof Company;
    }
}