<?php

namespace App\Serializer;

use CrEOF\Spatial\PHP\Types\Geometry\Point;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class PointNormalizer extends ObjectNormalizer
{
    protected $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?PropertyTypeExtractorInterface $propertyTypeExtractor = null
    ) {
        $this->entityManager = $entityManager;
        parent::__construct($classMetadataFactory, $nameConverter, $propertyAccessor, $propertyTypeExtractor);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return (strpos($type, 'CrEOF\\Spatial\\PHP\\Types\\Geometry\\Point') === 0) && (is_array($data) && isset($data[0]) && isset($data[1]));
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $point = new Point([$data[0], $data[1]]);

        return $point;
    }

    public function supportsNormalization($data, $format = null)
    {
        if($data instanceof Point){
            return true;
        }

        return false;
    }

    /**
     * @param Point $object
     * @param null $format
     * @param array $context
     * @return array|bool|float|int|string
     */
    public function normalize($object, $format = null, array $context = [])
    {
        return [$object->getLatitude(), $object->getLongitude()];
    }
}
