<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\LanguageType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\WeekType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Twig\Environment;

#[AsController]
final readonly class SettingsRequestHandler
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private Environment $twig,
    )
    {
    }

    #[Route(path: '/settings', methods: ['GET', 'POST'], priority: 2)]
    public function handle(Request $request): Response
    {
        $formBuilder = $this->formFactory->createBuilder();

        $form = $formBuilder
            ->add('AppUrl', UrlType::class, [
                'label' => 'App URL',
                'attr' => [
                    'placeholder' => 'http://localhost:8080/',
                ],
                'help' => 'The URL on which the app will be hosted. This URL will be used in the manifest file. This will allow you to install the web app as a native app on your device.',
                'required' => false,
                'default_protocol' => 'http',
                'constraints' => [
                    new Url(),
                    new NotBlank()
                ],
            ])
            /*->add('BirthdayType', BirthdayType::class, [
                'label' => 'BirthdayType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('CheckboxType', CheckboxType::class, [
                'label' => 'CheckboxType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('SelectType', ChoiceType::class, [
                'label' => 'SelectType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
                'choices' => [
                    'Maybe' => null,
                    'Yes' => true,
                    'No' => false,
                ],
            ])
            ->add('RadiosType', ChoiceType::class, [
                'label' => 'RadiosType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
                'expanded' => true,
                'multiple' => false,
                'choices' => [
                    'Maybe' => null,
                    'Yes' => true,
                    'No' => false,
                ],
            ])
            ->add('CheckboxesType', ChoiceType::class, [
                'label' => 'CheckboxesType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
                'expanded' => true,
                'multiple' => true,
                'choices' => [
                    'Maybe' => null,
                    'Yes' => true,
                    'No' => false,
                ],
            ])
            ->add('ColorType', ColorType::class, [
                'label' => 'ColorType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('CountryType', CountryType::class, [
                'label' => 'CountryType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('CurrencyType', CurrencyType::class, [
                'label' => 'CurrencyType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('DateIntervalType', DateIntervalType::class, [
                'label' => 'DateIntervalType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('DateTimeType', DateTimeType::class, [
                'label' => 'DateTimeType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('DateType', DateType::class, [
                'label' => 'DateType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('EmailType', EmailType::class, [
                'label' => 'EmailType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('FileType', FileType::class, [
                'label' => 'FileType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('IntegerType', IntegerType::class, [
                'label' => 'IntegerType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('LanguageType', LanguageType::class, [
                'label' => 'LanguageType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('LocaleType', LocaleType::class, [
                'label' => 'LocaleType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('MoneyType', MoneyType::class, [
                'label' => 'MoneyType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('NumberType', NumberType::class, [
                'label' => 'NumberType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('PasswordType', PasswordType::class, [
                'label' => 'PasswordType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('PercentType', PercentType::class, [
                'label' => 'PercentType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('RadioType', RadioType::class, [
                'label' => 'RadioType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('RangeType', RangeType::class, [
                'label' => 'RangeType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('RepeatedType', RepeatedType::class, [
                'label' => 'RepeatedType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('SearchType', SearchType::class, [
                'label' => 'SearchType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('TelType', TelType::class, [
                'label' => 'TelType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('TextType', TextType::class, [
                'label' => 'TextType',
                'help' => 'We’ll never share your details. Read our Privacy Policy.',
                'required' => true,
                'constraints' => [new Length(min: 3)],
            ])
            ->add('TextareaType', TextareaType::class, [
                'label' => 'TextareaType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('TimeType', TimeType::class, [
                'label' => 'TimeType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('TimezoneType', TimezoneType::class, [
                'label' => 'TimezoneType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])
            ->add('WeekType', WeekType::class, [
                'label' => 'WeekType',
                'help' => 'We’ll RangeType share your details. Read our Privacy Policy.',
                'required' => true,
            ])*/
            ->add('submit', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // ... perform some action, such as saving the task to the database

            return new RedirectResponse('/', Response::HTTP_FOUND);
        }

        return new Response(
            content: $this->twig->render('html/settings/general.html.twig', [
                'form' => $form->createView(),
            ]),
            status: Response::HTTP_OK
        );
    }
}
