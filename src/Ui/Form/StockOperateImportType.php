<?php

namespace xVer\MiCartera\Ui\Form;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use xVer\MiCartera\Application\Command\Stock\StockOperateCommand;
use xVer\MiCartera\Application\EntityObjectRepositoryLoader;
use xVer\MiCartera\Application\Query\Account\AccountQuery;
use xVer\Symfony\Bundle\BaseAppBundle\Ui\Controller\ExceptionTranslatorTrait;

class StockOperateImportType extends AbstractType implements DataMapperInterface
{
    use ExceptionTranslatorTrait;

    private readonly string $accountIdentifier;

    public function __construct(
        TokenStorageInterface $token,
        private readonly ManagerRegistry $managerRegistry,
        private readonly TranslatorInterface $translator
    ) {
        /** @psalm-suppress PossiblyNullReference */
        $this->accountIdentifier = $token->getToken()->getUser()->getUserIdentifier();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $accountQuery = new AccountQuery(
            EntityObjectRepositoryLoader::doctrine($this->managerRegistry)
        );
        $account = $accountQuery->byIdentifier($this->accountIdentifier);
        $builder
            ->add('csv', FileType::class, [
                'label' => new TranslatableMessage(
                    'csvTransactionFormat',
                    [
                        'timezone' => $account->getTimeZone()->getName(),
                        'currency' => $account->getCurrency()->getSymbol()
                    ],
                    'messages'
                ),
                'label_html' => true,
                'required' => true,
                'mapped' => false
            ])
            ->add('upload', SubmitType::class, ['label' => 'import'])
            ->setDataMapper($this);
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
    }

    /**
     * {@inheritDoc}
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        // @codeCoverageIgnoreStart
        // there is no data yet, so nothing to prepopulate
        if (!$forms instanceof \Traversable) {
            return;
        }
        // @codeCoverageIgnoreEnd

        $forms = \iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        try {
            /** @var UploadedFile */
            $file = $forms['csv']->getData();
            /**
             * @psalm-suppress RedundantConditionGivenDocblockType
             */
            if (
                $file instanceof UploadedFile
                && UPLOAD_ERR_OK === $file->getError()
                && $file->isValid()
                && $file->isFile()
                && in_array($file->getMimeType(), ['text/csv','text/plain','application/csv'])
                && false !== ($fp = fopen($file->getRealPath(), 'r'))
            ) {
                $command = new StockOperateCommand(
                    EntityObjectRepositoryLoader::doctrine($this->managerRegistry)
                );
                $lineNumber = 1;
                while (false !== ($line = fgetcsv($fp))) {
                    $numCols = count($line);
                    if (6 != $numCols) {
                        throw new \DomainException(
                            $this->translator->trans(
                                'csvInvalidColumnCount',
                                [
                                    'row' => $lineNumber,
                                    'expected' => 6,
                                    'got' => $numCols
                                ],
                                'validators'
                            )
                        );
                    }
                    /**
                     * @psalm-var array{0: string,1: string,2: string,3: numeric-string,4: int,5: numeric-string} $line
                     */
                    $command->import($lineNumber, $line, $this->accountIdentifier);
                    $lineNumber++;
                }
                fclose($fp);
            } else {
                throw new \DomainException($this->translator->trans('invalidUploadedFile', [], 'validators'));
            }
        } catch (\DomainException $de) {
            throw $this->getTranslatedException($de, $this->translator);
        }
    }
}
