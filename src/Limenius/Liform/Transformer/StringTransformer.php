<?php

/*
 * This file is part of the Limenius\Liform package.
 *
 * (c) Limenius <https://github.com/Limenius/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Limenius\Liform\Transformer;

use Limenius\Liform\FormUtil;
use Symfony\Component\Form\FormInterface;

/**
 * @author Nacho Martín <nacho@limenius.com>
 */
class StringTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function transform(FormInterface $form, array $extensions = [], $widget = null)
    {
        $schema = ['type' => 'string'];
        $schema = $this->addCommonSpecs($form, $schema, $extensions, $widget);
        $schema = $this->addMaxLength($form, $schema);
        $schema = $this->addMinLength($form, $schema);

        return $schema;
    }

    /**
     * @param FormInterface $form
     * @param array         $schema
     *
     * @return array
     */
    protected function addMaxLength(FormInterface $form, array $schema)
    {
        $maxLength = $this->validatorGuesser->guessMaxLength($form);

        if ($maxLength) {
            $schema['maxLength'] = $maxLength;
        }

        return $schema;
    }

    /**
     * @param FormInterface $form
     * @param array         $schema
     *
     * @return array
     */
    protected function addMinLength(FormInterface $form, array $schema)
    {
        $minLength = $this->validatorGuesser->guessMinLength($form);

        if ($minLength) {
            $schema['minLength'] = $minLength;
        }

        return $schema;
    }
}
