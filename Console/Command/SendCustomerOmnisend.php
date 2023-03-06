<?php

namespace Omnisend\Omnisend\Console\Command;

use Omnisend\Omnisend\Model\ResourceModel\OmnisendContact as OmnisendContactResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendCustomerOmnisend extends Command
{
    const COMMAND_NAME = 'omnisend:contacts:send-customer';
    const COMMAND_DESCRIPTION = 'Send specific customer to omnisend.';
    const CUSTOMERID_ARGUMENT = 'customer';

    protected \Omnisend\Omnisend\Model\EntityDataSender\Customer $omnisendContactResource;

    protected \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository;

    public function __construct(
        \Omnisend\Omnisend\Model\EntityDataSender\Customer $omnisendContactResource,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        $name = null
    ) {
        parent::__construct($name);
        $this->omnisendContactResource = $omnisendContactResource;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->setDefinition([
                new InputArgument(
                    self::CUSTOMERID_ARGUMENT,
                    InputArgument::REQUIRED,
                    'Customer entity_id (can be comma-separated)',
                ),
            ]);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $customerIds = $input->getArgument(self::CUSTOMERID_ARGUMENT);
            $output->writeln('Sync customer to omnisend...');
            foreach (\explode(',', $customerIds) as $customerId) {
                $output->write($customerId);
                $customer = $this->customerRepository->getById($customerId);
                $this->omnisendContactResource->send($customer);
                $output->writeln(' done');
            }
        } catch (\Magento\Framework\Exception\AggregateExceptionInterface $refreshException) {
            foreach ($refreshException->getErrors() as $error) {
                $output->error($error->getMessage());
            }
        } catch (\Exception $exception) {
            $output->error($exception->getMessage());
        }
    }
}
