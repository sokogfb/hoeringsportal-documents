<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class YamlType extends AbstractType implements DataTransformerInterface
{
    /** @var array */
    private $options;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;
        $builder->addViewTransformer($this);
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        try {
            $data = Yaml::parse($value);
            if (!\is_array($data)) {
                throw new \UnexpectedValueException('data must be an array');
            }
            if (isset($this->options['schema'])) {
                $this->validateData($data, $this->options['schema']);
            }
        } catch (\Exception $ex) {
            throw new TransformationFailedException($ex->getMessage(), $ex->getCode(), $ex);
        }

        return $value;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'schema' => null,
        ]);
    }

    public function getParent()
    {
        return TextareaType::class;
    }

    private function validateData(array $data, string $schema)
    {
        try {
            $schema = Yaml::parseFile($schema);
            // @TODO Validate schema.
        } catch (\Exception $ex) {
        }
    }
}
