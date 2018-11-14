<?php

/*
 * This file is part of the Limenius\Liform package.
 *
 * (c) Limenius <https://github.com/Limenius/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Limenius\Liform\Guesser;

use Symfony\Component\Form\Extension\Validator\ValidatorTypeGuesser;
use Symfony\Component\Form\Guess\Guess;
use Symfony\Component\Form\Guess\ValueGuess;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;
use Limenius\Liform\FormUtil;

/**
 * @author Nacho Martín <nacho@limenius.com>
 */
class ValidatorGuesser
{
    private $metadataFactory;
    private $validatorTypeGuesser;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
        $this->validatorTypeGuesser = new ValidatorTypeGuesser($this->metadataFactory);
    }

    private function getAllConstraints(FormInterface $form)
    {
        $constraints = array();

        // get constraints defined on the class (if exists)
        $class = FormUtil::findDataClass($form);
        if (null !== $class) {
            $property = $form->getName();
            $classMetadata = $this->metadataFactory->getMetadataFor($class);

            if ($classMetadata instanceof ClassMetadataInterface && $classMetadata->hasPropertyMetadata($property)) {
                foreach ($classMetadata->getPropertyMetadata($property) as $memberMetadata) {
                    $constraints = array_merge($constraints, $memberMetadata->getConstraints());
                }
            }
        }

        // get constraints defined on the form
        $constraints = array_merge($constraints, $form->getConfig()->getOption('constraints', []));

        return $constraints;
    }

    private function guess($constraints, \Closure $closure, $defaultValue = null)
    {
        $guesses = array();
        foreach ($constraints as $constraint) {
            if ($guess = $closure($constraint)) {
                $guesses[] = $guess;
            }
        }

        if (null !== $defaultValue) {
            $guesses[] = new ValueGuess($defaultValue, Guess::LOW_CONFIDENCE);
        }

        return Guess::getBestGuess($guesses);
    }

    public function guessMaxLength(FormInterface $form)
    {
        $defaultValue = null;
        if ($attr = $form->getConfig()->getOption('attr')) {
            if (isset($attr['maxlength'])) {
                $defaultValue = $attr['maxlength'];
            }
        }

        $maxLengthGuess = $this->guess($this->getAllConstraints($form), function (Constraint $constraint) {
            return $this->validatorTypeGuesser->guessMaxLengthForConstraint($constraint);
        }, $defaultValue);

        $maxLength = $maxLengthGuess ? $maxLengthGuess->getValue() : null;
        
        return $maxLength;
    }
    
    public function guessMinLength(FormInterface $form)
    {
        $minLengthGuess = $this->guess($this->getAllConstraints($form), function (Constraint $constraint) {
            switch (get_class($constraint)) {
                case 'Symfony\Component\Validator\Constraints\Length':
                    if (is_numeric($constraint->min)) {
                        return new ValueGuess($constraint->min, Guess::HIGH_CONFIDENCE);
                    }
                    break;
                case 'Symfony\Component\Validator\Constraints\Type':
                    if (in_array($constraint->type, array('double', 'float', 'numeric', 'real'))) {
                        return new ValueGuess(null, Guess::MEDIUM_CONFIDENCE);
                    }
                    break;
                case 'Symfony\Component\Validator\Constraints\Range':
                    if (is_numeric($constraint->min)) {
                        return new ValueGuess(strlen((string) $constraint->min), Guess::LOW_CONFIDENCE);
                    }
                    break;
            }
        });

        $minLength = $minLengthGuess ? $minLengthGuess->getValue() : null;

        return $minLength;
    }
}
