<?php

namespace App\Service;

use DateTime;
use stdClass;
use App\Entity\User;
use Stripe\AlipayAccount;
use Stripe\BankAccount;
use Stripe\BitcoinReceiver;
use Stripe\Card;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\Source;
use Symfony\Component\Asset\Packages;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;


class Stripe
{

    private $container;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    private $assetsManager;

    /**
     * Stripe constructor.
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Packages $assetsManager)
    {
        $this->container = $container;
        $this->em = $em;
        $this->assetsManager = $assetsManager;
    }

    /**
     * Save new card.
     */
    public function saveCard($user, $post)
    {
        if ($user->getCustomerStripeId() == null) {

            $customer = Customer::create([
                'email' => $user->getEmail()
            ]);

            $user->setCustomerStripeId($customer->id);
            $this->em->persist($user);
            $this->em->flush();
        }
        $customer = Customer::retrieve($user->getCustomerStripeId());

        try {
            $card = Customer::createSource(
                $user->getCustomerStripeId(),
                ['source' => $post['token_id'],]
            );
        } catch (CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            /*echo 'Status is:' . $e->getHttpStatus() . '\n';
            echo 'Type is:' . $e->getError()->type . '\n';
            echo 'Code is:' . $e->getError()->code . '\n';
            // param is '' in this case
            echo 'Param is:' . $e->getError()->param . '\n';
            echo 'Message is:' . $e->getError()->message . '\n';*/
            return $e->getError()->message;
        } catch (RateLimitException $e) {
            // Too many requests made to the API too quickly
            return $e->getError()->message;
        } catch (InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            return $e->getError()->message;
        } catch (AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return $e->getError()->message;
        } catch (ApiConnectionException $e) {
            // Network communication with Stripe failed
            return $e->getError()->message;
        } catch (ApiErrorException $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            return $e->getError()->message;
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            return $e->getError()->message;
        }


        return $card;

    }

    /**
     * Delete a card.
     */
    public function deleteCard($user, $card)
    {
        $result = Customer::deleteSource(
            $user->getCustomerStripeId(),
            $card
        );

        return $result->deleted;
    }

    /**
     * Get user card list.
     */
    public function getUserCardList($user)
    {
        $cards = [];

        if ($user->getCustomerStripeId() == NULL) {
            return '';
        }

        $cardList = Customer::allSources(
            $user->getCustomerStripeId()
        );

        foreach ($cardList as $card) {
            $cards[] = ['id' => $card->id, 'type' => $card->funding, 'brand' => $card->brand, 'data' => array('card_number' => 'XXXXXXXXXXXX' . $card->last4, 'expiration_month' => $card->exp_month, 'expiration_year' => $card->exp_year)];
        }

        return $cards;
    }

    /**
     * Calculate Amount based in stripe comitions.
     */
    public function getAmount($subTotal)
    {
        $amount = new stdClass();
        $amount->subTotal = $subTotal;
        //Fee Stripe
        $amount->paymentFeeIva = ($amount->subTotal * $_ENV['STRIPE_COMITION_PERCENT'] + $_ENV['STRIPE_COMITION_COST']) * MyOffice::TAX;
        $amount->paymentFee = $amount->subTotal * $_ENV['STRIPE_COMITION_PERCENT'] + $_ENV['STRIPE_COMITION_COST'] + $amount->paymentFeeIva;

        $amount->tax = ($amount->subTotal + $amount->paymentFee) * MyOffice::TAX;
        $amount->total = $amount->subTotal + $amount->paymentFee + $amount->tax;

        $amount->taxNoFee = ($amount->subTotal) * MyOffice::TAX;
        $amount->totalNoFee = $amount->subTotal + $amount->taxNoFee;

        $amount->paymentFee = number_format($amount->paymentFee, 2);
        $amount->tax = number_format($amount->tax, 2);
        $amount->total = number_format($amount->total, 2);

        return $amount;
    }

    public function validatePaymentIntent($membership)
    {
        $intent = PaymentIntent::retrieve($membership->getPaymentIntent());


        if($intent->status == "requires_payment_method" && $intent->last_payment_error != null){

            $membership->setStatus("canceled");
            $this->em->persist($membership);
            $this->em->flush();

            return array(
                'msg' => "Payment has not been authorized by the user",
                'status' => $intent->status
            );
        }
        
        $charge = $intent->confirm();
    
        if ($charge->status == 'succeeded' || $charge->status == 'pending') {

            $status = $charge->status;

            if ($charge->status == 'succeeded') {
                $status = 'completed';
            }

            if ($charge->status == 'pending') {
                $status = 'charge_pending';
            }

            $membership->setChargeId($charge->id);
            $membership->setStatus($status);
            $this->em->persist($membership);
            $this->em->flush();

            return array(
                'id' => $charge->id,
                'msg' => $charge->status
            );
        } else {
            return $charge->status;
        }
    }

    public function checkoutApiProcess($user, $url, $membership, $sourceId, $cdfi)
    {
        try {
            $charge = Charge::create([
                'amount' => str_replace(".", "", $membership->getTotal()),
                'currency' => 'mxn',
                'customer' => $user->getCustomerStripeId(),
                'source' => $sourceId,
                'description' => 'Cargo GoData - ' . $membership->getId(),
            ]);
        } catch (CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            return $e->getError()->message;
        } catch (RateLimitException $e) {
            // Too many requests made to the API too quickly
            return $e->getError()->message;
        } catch (InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            return $e->getError()->message;
        } catch (AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return $e->getError()->message;
        } catch (ApiConnectionException $e) {
            // Network communication with Stripe failed
            return $e->getError()->message;
        } catch (ApiErrorException $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            return $e->getError()->message;
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            return $e->getError()->message;
        }

        if ($charge->status == 'succeeded' || $charge->status == 'pending') {

            $status = $charge->status;

            if ($charge->status == 'succeeded') {
                $status = 'completed';
            }

            if ($charge->status == 'pending') {
                $status = 'charge_pending';
            }

            $membership->setChargeId($charge->id);
            $membership->setStatus($status);
            $membership->setCdfi($cdfi);
            $this->em->persist($membership);
            $this->em->flush();

            return array(
                'id' => $charge->id,
                'msg' => $charge->status
            );
        } else {
            return $charge->status;
        }


    }

    public function checkoutApi3dProcess($user, $url, $membership, $sourceId, $cdfi)
    {
        try {
            /*$charge = Charge::create([
                'amount' => str_replace(".", "", $membership->getTotal()),
                'currency' => 'mxn',
                'customer' => $user->getCustomerStripeId(),
                'source' => $sourceId,
                'description' => 'Cargo GoData - ' . $membership->getId(),
            ]);*/
            $intentCharge = PaymentIntent::create([
                'amount' => str_replace(".", "", $membership->getTotal()),
                'currency' => 'mxn',
                'customer' => $user->getCustomerStripeId(),
                'source' => $sourceId,
                'description' => 'Cargo GoData - ' . $membership->getId(),
                "confirmation_method" => "manual",
                'confirm' => true,
                //'return_url' => 'https://google.com'
              ]);
    
            $charge = PaymentIntent::retrieve($intentCharge->id);
            //$charge = $intent->confirm();

        } catch (CardException $e) {
            // Since it's a decline, \Stripe\Exception\CardException will be caught
            return $e->getError()->message;
        } catch (RateLimitException $e) {
            // Too many requests made to the API too quickly
            return $e->getError()->message;
        } catch (InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            return $e->getError()->message;
        } catch (AuthenticationException $e) {
            // Authentication with Stripe's API failed
            // (maybe you changed API keys recently)
            return $e->getError()->message;
        } catch (ApiConnectionException $e) {
            // Network communication with Stripe failed
            return $e->getError()->message;
        } catch (ApiErrorException $e) {
            // Display a very generic error to the user, and maybe send
            // yourself an email
            return $e->getError()->message;
        } catch (Exception $e) {
            // Something else happened, completely unrelated to Stripe
            return $e->getError()->message;
        }
        # Note that if your API version is before 2019-02-11, 'requires_action'
        # appears as 'requires_source_action'.
        /*if ($charge->status == 'requires_action' && $charge->next_action->type == 'use_stripe_sdk') {
            $membership->setPaymentIntent($charge->id);
            $this->em->persist($membership);
            $this->em->flush();
            # Tell the client to handle the action
            $url = $charge->next_action->use_stripe_sdk->stripe_js;

            return array(
                'id' => '1',
                'msg' => '2',
                'url' => '3'
            );
            /*return array(
                'requires_action' => true,
                'payment_intent_client_secret' => $charge->client_secret,

            );*/
        /*} /*else if ($intent->status == 'succeeded') {
            # The payment didn’t need any additional actions and completed!
            # Handle post-payment fulfillment
            return $charge->status;
        } else {
              # Invalid status
            return $charge->status;
        }*/

        /*if($charge->status == 'requires_action'){
            return $charge->next_action;
        }*/

        if (!is_null($charge->status)) {

            $status = $charge->status;
            $url = null;

            if ($charge->status == 'succeeded') {
                $status = 'completed';
            }

            if ($charge->status == 'pending') {
                $status = 'charge_pending';
            }

            if ($charge->status == 'requires_action' && $charge->next_action->type == 'use_stripe_sdk') {
                $url = $charge->next_action->use_stripe_sdk->stripe_js;
                $membership->setPaymentIntent($charge->id);
            }

            $membership->setChargeId($charge->id);
            $membership->setStatus($status);
            $membership->setCdfi($cdfi);
            $this->em->persist($membership);
            $this->em->flush();

            return array(
                'id' => $charge->id,
                'msg' => $charge->status,
                'url' => $url
            );
        } else {
            return $charge->status;
        }


    }

    public function validatePaymentIntentCredits($item)
    {
        $intent = PaymentIntent::retrieve($item->getCharge());


        if($intent->status == "requires_payment_method" && $intent->last_payment_error != null){

            $item->setStatus("canceled");
            $this->em->persist($item);
            $this->em->flush();

            return array(
                'msg' => "Payment has not been authorized by the user",
                'status' => $intent->status
            );
        }
        
        $charge = $intent->confirm();
    
        if ($charge->status == 'succeeded' || $charge->status == 'pending') {

            $status = $charge->status;

            if ($charge->status == 'succeeded') {
                $status = 'completed';
            }

            if ($charge->status == 'pending') {
                $status = 'charge_pending';
            }

            $item->setCharge($charge->id);
            $item->setStatus($status);
            $this->em->persist($item);
            $this->em->flush();

            return array(
                'id' => $charge->id,
                'status' => $status
            );
        } else {
            return $charge->status;
        }
    }

    public function getUserPayments(User $user, $limit = 5)
    {
        $this->customer = $this->openPay->customers->get($user->getCustomerId());
        $chargesList = $this->customer->charges->getList(['limit' => $limit]);

        return $chargesList;
    }

    public function getCharge(User $user, $chargeId)
    {
        if ($chargeId == null) {
            return null;
        }

        if(strpos($chargeId, "pi_") === 0) {
            $charge = PaymentIntent::retrieve($chargeId);
        }else{
            $charge = Charge::retrieve(
                $chargeId
            );
        }

        return $charge;
    }

}
