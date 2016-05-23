<?php

namespace Eway\Rapid;

use Eway\Rapid\Contract\Client as ClientContract;
use Eway\Rapid\Contract\Http\ResponseInterface;
use Eway\Rapid\Contract\HttpService as HttpServiceContract;
use Eway\Rapid\Enum\ApiMethod;
use Eway\Rapid\Enum\PaymentMethod;
use Eway\Rapid\Enum\TransactionType;
use Eway\Rapid\Exception\MassAssignmentException;
use Eway\Rapid\Exception\MethodNotImplementedException;
use Eway\Rapid\Exception\RequestException;
use Eway\Rapid\Model\Customer;
use Eway\Rapid\Model\Refund;
use Eway\Rapid\Model\Response\AbstractResponse;
use Eway\Rapid\Model\Response\CreateCustomerResponse;
use Eway\Rapid\Model\Response\CreateTransactionResponse;
use Eway\Rapid\Model\Response\QueryAccessCodeResponse;
use Eway\Rapid\Model\Response\QueryCustomerResponse;
use Eway\Rapid\Model\Response\QueryTransactionResponse;
use Eway\Rapid\Model\Response\RefundResponse;
use Eway\Rapid\Model\Transaction;
use Eway\Rapid\Service\Http;
use Eway\Rapid\Validator\ClassValidator;
use Eway\Rapid\Validator\EnumValidator;
use InvalidArgumentException;

/**
 * eWAY Rapid Client
 *
 * Connect to eWAY's Rapid API to process transactions and refunds, create and
 * update customer tokens.
 *
 */
class Client implements ClientContract
{
    /**
     * Rapid API Key
     *
     * @var string
     */
    private $apiKey;

    /**
     * Password for the API Key
     *
     * @var string
     */
    private $apiPassword;

    /**
     * Possible values ("Production", "Sandbox", or a URL) Production and sandbox
     * will default to the Global Rapid API Endpoints.
     *
     * use \Eway\Rapid\Client::MODE_SANDBOX or \Eway\Rapid\Client::MODE_PRODUCTION
     *
     * @var string
     */
    private $endpoint;

    /**
     * True if the Client has a valid API Key, Password and Endpoint Set.
     *
     * @var bool
     */
    private $isValid = false;

    /**
     * Contains the Rapid Error code in the case of initialisation errors.
     *
     * @var array
     */
    private $errors = [];

    /**
     * @var HttpServiceContract
     */
    private $httpService;

    /**
     * @param string $apiKey
     * @param string $apiPassword
     * @param string $endpoint
     */
    public function __construct($apiKey, $apiPassword, $endpoint)
    {
        $this->setHttpService(new Http());
        $this->setCredential($apiKey, $apiPassword);
        $this->setEndpoint($endpoint);
    }

    #region Public Functions

    /**
     * @inheritdoc
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @inheritdoc
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @inheritdoc
     */
    public function setEndpoint($endpoint)
    {
        if (ClientContract::MODE_SANDBOX === strtolower($endpoint)) {
            $endpoint = ClientContract::ENDPOINT_SANDBOX;
        } elseif (ClientContract::MODE_PRODUCTION === strtolower($endpoint)) {
            $endpoint = ClientContract::ENDPOINT_PRODUCTION;
        }

        $this->endpoint = $endpoint;
        $this->getHttpService()->setBaseUrl($endpoint);
        $this->_emptyErrors();
        $this->_validate();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setCredential($apiKey, $apiPassword)
    {
        $this->apiKey = $apiKey;
        $this->apiPassword = $apiPassword;
        $this->getHttpService()->setKey($apiKey);
        $this->getHttpService()->setPassword($apiPassword);
        $this->_emptyErrors();
        $this->_validate();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function createTransaction($apiMethod, $transaction)
    {
        return $this->_invoke(CreateTransactionResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function queryTransaction($reference)
    {
        return $this->_invoke(QueryTransactionResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function queryInvoiceNumber($invoiceNumber)
    {
        return $this->_invoke(QueryTransactionResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function queryInvoiceReference($invoiceReference)
    {
        return $this->_invoke(QueryTransactionResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function createCustomer($apiMethod, $customer)
    {
        return $this->_invoke(CreateCustomerResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function updateCustomer($apiMethod, $customer)
    {
        return $this->_invoke(CreateCustomerResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function queryCustomer($tokenCustomerId)
    {
        return $this->_invoke(QueryCustomerResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function refund($refund)
    {
        return $this->_invoke(RefundResponse::getClass());
    }

    /**
     * @inheritdoc
     */
    public function cancelTransaction($transactionId)
    {
        return $this->_invoke(RefundResponse::getClass());
    }

    /**
     * @param mixed $accessCode
     *
     * @return QueryAccessCodeResponse
     */
    public function queryAccessCode($accessCode)
    {
        return $this->_invoke(QueryAccessCodeResponse::getClass());
    }

    #endregion

    #region Getter/Setter

    /**
     * @param HttpServiceContract $httpService
     *
     * @return Client
     */
    public function setHttpService(HttpServiceContract $httpService)
    {
        $this->httpService = $httpService;
        $this->_emptyErrors();
        $this->_validate();

        return $this;
    }

    /**
     * @return HttpServiceContract
     */
    public function getHttpService()
    {
        return $this->httpService;
    }

    #endregion

    #region Internal logic

    /**
     * @param $apiMethod
     * @param $transaction
     *
     * @return ResponseInterface
     */
    private function _createTransaction($apiMethod, $transaction)
    {
        $apiMethod = EnumValidator::validate('Eway\Rapid\Enum\ApiMethod', 'ApiMethod', $apiMethod);

        /** @var Transaction $transaction */
        $transaction = ClassValidator::getInstance('Eway\Rapid\Model\Transaction', $transaction);

        switch ($apiMethod) {
            case ApiMethod::DIRECT:
            case ApiMethod::WALLET:
                if ($transaction->Capture) {
                    $transaction->Method = PaymentMethod::PROCESS_PAYMENT;
                } else {
                    $transaction->Method = PaymentMethod::AUTHORISE;
                }

                return $this->getHttpService()->postTransaction($transaction->toArray());

            case ApiMethod::RESPONSIVE_SHARED:
                if ($transaction->Capture) {
                    if (isset($transaction->Customer) && isset($transaction->Customer->TokenCustomerID)) {
                        $transaction->Method = PaymentMethod::TOKEN_PAYMENT;
                    } else {
                        $transaction->Method = PaymentMethod::PROCESS_PAYMENT;
                    }
                } else {
                    $transaction->Method = PaymentMethod::AUTHORISE;
                }

                return $this->getHttpService()->postAccessCodeShared($transaction->toArray());

            case ApiMethod::TRANSPARENT_REDIRECT:
                if ($transaction->Capture) {
                    if (isset($transaction->Customer) && isset($transaction->Customer->TokenCustomerID)) {
                        $transaction->Method = PaymentMethod::TOKEN_PAYMENT;
                    } else {
                        $transaction->Method = PaymentMethod::PROCESS_PAYMENT;
                    }
                } else {
                    $transaction->Method = PaymentMethod::AUTHORISE;
                }

                return $this->getHttpService()->postAccessCode($transaction->toArray());

            case ApiMethod::AUTHORISATION:
                return $this->getHttpService()->postCapturePayment($transaction->toArray());

            default:
                // Although right now this code is not reachable, protect against incomplete
                // changes to ApiMethod
                throw new MethodNotImplementedException();
        }
    }

    /**
     * @param $reference
     *
     * @return ResponseInterface
     */
    private function _queryTransaction($reference)
    {
        return $this->getHttpService()->getTransaction($reference);
    }

    /**
     * @param $invoiceNumber
     *
     * @return ResponseInterface
     */
    private function _queryInvoiceNumber($invoiceNumber)
    {
        return $this->getHttpService()->getTransactionInvoiceNumber($invoiceNumber);
    }


    /**
     * @param $invoiceReference
     *
     * @return ResponseInterface
     */
    private function _queryInvoiceReference($invoiceReference)
    {
        return $this->getHttpService()->getTransactionInvoiceReference($invoiceReference);
    }

    /**
     * @param $apiMethod
     * @param $customer
     *
     * @return ResponseInterface
     */
    private function _createCustomer($apiMethod, $customer)
    {
        /** @var Customer $customer */
        $customer = ClassValidator::getInstance('Eway\Rapid\Model\Customer', $customer);

        $apiMethod = EnumValidator::validate('Eway\Rapid\Enum\ApiMethod', 'ApiMethod', $apiMethod);

        $transaction = [
            'Customer' => $customer->toArray(),
            'Method' => PaymentMethod::CREATE_TOKEN_CUSTOMER,
            'TransactionType' => TransactionType::PURCHASE,
        ];
        if (isset($customer->RedirectUrl)) {
            $transaction['RedirectUrl'] = $customer->RedirectUrl;
        }
        if (isset($customer->CancelUrl)) {
            $transaction['CancelUrl'] = $customer->CancelUrl;
        }

        /** @var Transaction $transaction */
        $transaction = ClassValidator::getInstance('Eway\Rapid\Model\Transaction', $transaction);

        switch ($apiMethod) {
            case ApiMethod::DIRECT:
                return $this->getHttpService()->postTransaction($transaction->toArray());

            case ApiMethod::RESPONSIVE_SHARED:
                $transaction->Payment = ['TotalAmount' => 0];

                return $this->getHttpService()->postAccessCodeShared($transaction->toArray());

            case ApiMethod::TRANSPARENT_REDIRECT:
                $transaction->Payment = ['TotalAmount' => 0];

                return $this->getHttpService()->postAccessCode($transaction->toArray());

            default:
                // Although right now this code is not reachable, protect against incomplete
                // changes to ApiMethod
                throw new MethodNotImplementedException();
        }
    }

    /**
     * 
     * @param $apiMethod
     * @param type $customer
     * @return ResponseInterface
     * @throws MethodNotImplementedException
     */
    private function _updateCustomer($apiMethod, $customer)
    {
        /** @var Customer $customer */
        $customer = ClassValidator::getInstance('Eway\Rapid\Model\Customer', $customer);

        $apiMethod = EnumValidator::validate('Eway\Rapid\Enum\ApiMethod', 'ApiMethod', $apiMethod);

        $transaction = [
            'Customer' => $customer->toArray(),
            'Payment' => ['TotalAmount' => 0],
            'Method' => PaymentMethod::UPDATE_TOKEN_CUSTOMER,
            'TransactionType' => TransactionType::PURCHASE,
        ];

        if (isset($customer->RedirectUrl)) {
            $transaction['RedirectUrl'] = $customer->RedirectUrl;
        }
        if (isset($customer->CancelUrl)) {
            $transaction['CancelUrl'] = $customer->CancelUrl;
        }

        /** @var Transaction $transaction */
        $transaction = ClassValidator::getInstance('Eway\Rapid\Model\Transaction', $transaction);

        switch ($apiMethod) {
            case ApiMethod::DIRECT:
                return $this->getHttpService()->postTransaction($transaction->toArray());

            case ApiMethod::RESPONSIVE_SHARED:
                return $this->getHttpService()->postAccessCodeShared($transaction->toArray());

            case ApiMethod::TRANSPARENT_REDIRECT:
                return $this->getHttpService()->postAccessCode($transaction->toArray());

            default:
                // Although right now this code is not reachable, protect against incomplete
                // changes to ApiMethod
                throw new MethodNotImplementedException();
        }
    }

    /**
     * @param $tokenCustomerId
     *
     * @return ResponseInterface
     */
    private function _queryCustomer($tokenCustomerId)
    {
        return $this->getHttpService()->getCustomer($tokenCustomerId);
    }

    /**
     * @param $refund
     *
     * @return ResponseInterface
     */
    private function _refund($refund)
    {
        /** @var Refund $refund */
        $refund = ClassValidator::getInstance('Eway\Rapid\Model\Refund', $refund);

        return $this->getHttpService()->postTransactionRefund($refund->Refund->TransactionID, $refund->toArray());
    }

    /**
     * @param $transactionId
     *
     * @return ResponseInterface
     */
    private function _cancelTransaction($transactionId)
    {
        $refund = [
            'TransactionID' => $transactionId,
        ];

        /** @var Refund $refund */
        $refund = ClassValidator::getInstance('Eway\Rapid\Model\Refund', $refund);

        return $this->getHttpService()->postCancelAuthorisation($refund->toArray());
    }

    /**
     * @param $accessCode
     *
     * @return ResponseInterface
     */
    private function _queryAccessCode($accessCode)
    {
        return $this->getHttpService()->getAccessCode($accessCode);
    }

    #endregion

    #region Internal helpers

    /**
     * @param $responseClass
     *
     * @return AbstractResponse
     */
    private function _invoke($responseClass)
    {
        if (!$this->isValid()) {
            return $this->_getErrorResponse($responseClass);
        }

        try {
            $caller = $this->_getCaller();
            $response = call_user_func_array([$this, '_'.$caller['function']], $caller['args']);

            return $this->_wrapResponse($responseClass, $response);
        } catch (InvalidArgumentException $e) {
            $this->_addError(self::ERROR_INVALID_ARGUMENT);
        } catch (MassAssignmentException $e) {
            $this->_addError(self::ERROR_INVALID_ARGUMENT);
        }


        return $this->_getErrorResponse($responseClass);
    }

    /**
     * @return mixed
     */
    private function _getCaller()
    {
        $callers = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);

        // Index of the caller of this function is 1
        // So caller of that caller is 2
        return $callers[2];
    }

    /**
     * @param string            $class
     * @param ResponseInterface $httpResponse
     *
     * @return mixed
     */
    private function _wrapResponse($class, $httpResponse = null)
    {
        $data = [];
        try {
            if (isset($httpResponse)) {
                $this->_checkResponse($httpResponse);
                $body = (string)$httpResponse->getBody();
                if (!$this->_isJson($body)) {
                    $this->_addError(self::ERROR_INVALID_JSON);
                } else {
                    $data = json_decode($body, true);
                }
            } else {
                $this->_addError(self::ERROR_EMPTY_RESPONSE);
            }
        } catch (RequestException $e) {
            // An error code is already provided by _checkResponse
        }

        /** @var AbstractResponse $response */
        $response = new $class($data);
        foreach ($this->getErrors() as $errorCode) {
            $response->addError($errorCode);
        }

        return $response;
    }

    /**
     * @param $string
     *
     * @return bool
     */
    private function _isJson($string)
    {
        return is_string($string) && is_object(json_decode($string)) && (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @return $this
     */
    private function _emptyErrors()
    {
        $this->errors = [];

        return $this;
    }

    /**
     * @return $this
     */
    private function _validate()
    {
        $this->isValid = true;
        if (empty($this->apiKey) || empty($this->apiPassword)) {
            $this->_addError(self::ERROR_INVALID_CREDENTIAL);
        }
        if (empty($this->endpoint) || strpos($this->endpoint, 'https') !== 0) {
            $this->_addError(self::ERROR_INVALID_ENDPOINT);
        }
        if (count($this->getErrors()) > 0) {
            $this->isValid = false;
        }

        return $this;
    }

    /**
     * @param string $errorCode
     *
     * @return $this
     */
    private function _addError($errorCode)
    {
        $this->isValid = false;
        $this->errors[] = $errorCode;

        return $this;
    }

    /**
     * @param $responseClass
     *
     * @return mixed
     */
    private function _getErrorResponse($responseClass)
    {
        $data = ['Errors' => implode(',', $this->getErrors())];

        return new $responseClass($data);
    }

    /**
     * @param ResponseInterface $response
     *
     * @throws RequestException
     */
    private function _checkResponse($response)
    {
        $hasRequestError = false;
        if (preg_match('/4\d\d/', $response->getStatusCode())) {
            $this->_addError(self::ERROR_HTTP_AUTHENTICATION_ERROR);
            $hasRequestError = true;
        } elseif (preg_match('/5\d\d/', $response->getStatusCode())) {
            $this->_addError(self::ERROR_HTTP_SERVER_ERROR);
            $hasRequestError = true;
        } elseif ($response->getStatusCode() == 0) {
            $this->_addError(self::ERROR_CONNECTION_ERROR);
            $hasRequestError = true;
        }

        if ($hasRequestError) {
            throw new RequestException(sprintf("Last HTTP response status code: %s", $response->getStatusCode()));
        }
    }

    #endregion
}
