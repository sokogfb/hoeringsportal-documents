<?php

/*
 * This file is part of hoeringsportal-sync-files.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Form\Type;

use Opis\JsonSchema\Schema;
use Opis\JsonSchema\Validator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

class YamlType extends AbstractType
{
    /** @var array */
    private $options;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->options = $options;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $e) {
            $this->validateData($e->getData(), $e->getForm());
        });
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

    private function validateData($input, Form $form)
    {
        if (!empty($this->options['schema'])) {
            try {
                $data = Yaml::parse($input, YAML::PARSE_OBJECT_FOR_MAP);
                $schemaContent = file_get_contents($this->options['schema']);
                $schemaData = (0 === strpos($schemaContent, '{')) ? json_decode($schemaContent) : Yaml::parse($schemaContent, YAML::PARSE_OBJECT_FOR_MAP);
                $schema = new Schema($schemaData);
                $validator = new Validator();
                $result = $validator->schemaValidation($data, $schema);

                if (!$result->isValid()) {
                    foreach ($result->getErrors() as $error) {
                        $form->addError(new FormError(json_encode([$error->keyword(), $error->keywordArgs()])));
                    }
                }
            } catch (\Exception $exception) {
                $form->addError(new FormError($exception->getMessage()));
            }
        }
    }
}
