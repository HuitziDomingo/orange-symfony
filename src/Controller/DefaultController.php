<?php

namespace App\Controller;

use App\Repository\MembershipRepository;
use App\Entity\Membership;
use App\Service\StripePaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class DefaultController extends AbstractController
{

    /**
     * @var StripePaymentService
     */
    private $stripePaymentService;

    /**
     * @var string
     */
    private $stripeApiKey;

    public function __construct(SessionInterface $session, StripePaymentService $stripePaymentService)
    {
        $this->session = $session;
        $this->stripePaymentService = $stripePaymentService;
        $this->stripeApiKey = $_ENV['STRIPE_API_KEY'];
    }


    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('site/index.html.twig', [
            'title' => 'GoData',
        ]);
    }

    /**
     * @Route("/nosotros", name="nosotros")
     */
    public function nosotros()
    {
        return $this->render('site/nosotros.html.twig', [
            'title' => 'GoData',
        ]);
    }

    /**
     * @Route("/productos", name="productos")
     */
    public function productos()
    {
        return $this->render('site/productos.html.twig', [
            'title' => 'GoData',
        ]);
    }

    /**
     * @Route("/membresias", name="membresias")
     */
    public function membresias(MembershipRepository $membershipRepo)
    {
        $memberships = $membershipRepo->findAll();

        return $this->render('site/membresias.html.twig', [
            'title' => 'GoData',
            'memberships' => $memberships,
        ]);
    }

    /**
     * @Route("/reportes", name="reportes")
     */
    public function reportes()
    {
        return $this->render('site/reportes.html.twig', [
            'title' => 'GoData',
        ]);
    }

    /**
     * @Route("/registrate/{id}", name="registrate")
     */
    public function registrate(Membership $membership, MembershipRepository $membershipRepo)
    {
        $paymentIntent = $this->stripePaymentService->createPaymentIntent(100);
        return $this->render('site/registrate.html.twig', [
            'title' => 'GoData',
            'payment_intent' => $paymentIntent,
            'stripe_api_key' => $this->stripeApiKey,
            'product' => $membership,
            'total' => 100,
        ]);
    }

    /**
     * @Route("/cart/checkout/success", methods={"GET"})
     */
    public function successAction()
    {
        return $this->render('site/cart/checkout_success.html.twig');
    }

    /**
     * @Route("/sesion", name="sesion")
     */
    public function sesion()
    {
        return $this->render('site/sesion.html.twig', [
            'title' => 'GoData',
        ]);
    }

    /**
     * @Route("/contacto", name="contacto")
     */
    public function contacto()
    {
        return $this->render('site/contacto.html.twig', [
            'title' => 'GoData',
        ]);
    }

}