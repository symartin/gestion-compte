<?php

namespace AppBundle\Controller;
use AppBundle\Entity\Beneficiary;
use AppBundle\Entity\Shift;
use AppBundle\Entity\ShiftExchange;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


/**
 * ShiftExchange controller.
 *
 * @Route("shift_exchange")
 */
class ShiftExchangeController extends Controller
{

    /**
     * Lists all exchanges.
     *
     * @Route("/", name="shift_exchange_list")
     * @Method("GET")
     * @Security("has_role('ROLE_SHIFT_MANAGER')")
     */
    public function listExchangesAction(Request $request){

        $em = $this->getDoctrine()->getManager();
        $shift_exchanges = $em->getRepository('AppBundle:ShiftExchange')->findAll();
        $delete_forms = array();
        foreach ($shift_exchanges as $shift_exchange){
            $delete_forms[$shift_exchange->getId()] = $this->getShiftExchangeDeleteForm($shift_exchange)->createView();
        }
        return $this->render('admin/shift_exchange/list.html.twig', array(
            'shift_exchanges' => $shift_exchanges,
            'delete_forms' => $delete_forms,
        ));
    }

    /**
     * Lists all exchanges for one beneficiary.
     *
     * @Route("/{id}/proxies_list", name="shift_exchange_beneficiary_list")
     * @Method("GET")
     * @param Request $request
     * @param Beneficiary $beneficiary
     */
    public function listExchangesBeneficiaryAction(Request $request, Beneficiary $beneficiary){

        $member = $beneficiary->getMembership();
        $this->denyAccessUnlessGranted('get', $member);
        $em = $this->getDoctrine()->getManager();
        $shift_exchanges = $em->getRepository('AppBundle:ShiftExchange')->findAll();
        return $this->render('admin/event/proxy/list.html.twig', array(
            'shift_exchanges' => $shift_exchanges,
        ));
    }

    /**
     * Shift exchange delete
     *
     * @Route("/{id}", name="shift_exchange_delete")
     * @Method({"DELETE"})
     * @Security("has_role('ROLE_SHIFT_MANAGER')")
     */
    public function removeShiftExchangeAction(Request $request, ShiftExchange $shift_exchange)
    {
        $session = new Session();
        $form = $this->getShiftExchangeDeleteForm($shift_exchange);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->remove($shift_exchange);
            $em->flush();
            $session->getFlashBag()->add('success', 'L\'échange de créneau a bien été supprimé !');
        }
        return $this->redirectToRoute('shift_exchange_list');
    }

    /**
     * @param ShiftExchange $shiftExchange
     * @return \Symfony\Component\Form\FormInterface
     */
    protected function getShiftExchangeDeleteForm(ShiftExchange $shift_exchange){
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('shift_exchange_delete', array('id' => $shift_exchange->getId())))
            ->setMethod('DELETE')
            ->getForm();
    }

    /**
     * Shift Exchange new
     *
     * @Route("/{id}/shift_exchange/", name="shift_exchange_new")
     * @Method({"GET", "POST"})
     */
    public function ShiftExchangeNewAction(Request $request, Shift $shift, \Swift_Mailer $mailer){
        $session = new Session();
        $em = $this->getDoctrine()->getManager();
        $current_app_user = $this->get('security.token_storage')->getToken()->getUser();
        $current_beneficiary = $current_app_user->getBeneficiary();
        if ($shift->getStart() < new \DateTime('now')){
            $session->getFlashBag()->add('error', 'Oups, ce créneau est dans la passé, impossible de faire l\'échange');
            return $this->redirectToRoute('homepage');
        }
        if ($shift->getIsDismissed()){
            $session->getFlashBag()->add('error', 'Oups, ce créneau est verrouillé, impossible de faire l\'échange. Merci de contacter le bureau des membres.');
            return $this->redirectToRoute('homepage');
        }
        if ($shift->getShifter() != $current_beneficiary){
            $session->getFlashBag()->add('error', 'Oups, tu n\'es pas inscrit sur ce créneau, impossible de faire l\'échange. Merci de contacter le bureau des membres.');
            return $this->redirectToRoute('homepage');
        }
        $shift_exchange = $em->getRepository('AppBundle:ShiftExchange')->findOneBy(array("shift"=>$shift,"giver"=>$current_beneficiary));
        if ($shift_exchange){
            $session->getFlashBag()->add('error', 'Oups, tu as déjà une demande d\'échange en cours pour ce créneau');
            return $this->redirectToRoute('homepage');
        }

        if ($request->get("beneficiary") > 0){
            $em = $this->getDoctrine()->getManager();
            $beneficiary = $em->getRepository('AppBundle:Beneficiary')->find($request->get("beneficiary"));
            if ($beneficiary){
                $shift_exchange = new ShiftExchange();
                $shift_exchange->setShift($shift);
                $shift_exchange->setCreatedAt(new \DateTime());
                $shift_exchange->setReceiver($beneficiary);
                $shift_exchange->setGiver($current_beneficiary);
                $shift_exchange->setStatus("En attente");

                $em->persist($shift_exchange);
                $em->flush();
                $session = new Session();
                $session->getFlashBag()->add('success', 'Votre demande a bien été enregistrée !');

                $this->sendShiftExchangeMail($shift_exchange, $mailer);
            } else {
                $session->getFlashBag()->add('error',"oups, quelque chose c'est mal passé");
            }
            return $this->redirectToRoute('homepage');
        }

        $search_form = $this->createFormBuilder()
            ->setAction($this->generateUrl('shift_exchange_find_beneficiary', array('id' => $shift->getId())))
            ->add('firstname', TextType::class, array('label' => 'le prénom'))
            ->setMethod('POST')
            ->getForm();

        return $this->render('default/shiftExchange/give.html.twig', array(
            'shift' => $shift,
            'search_form' => $search_form->createView()
        ));
    }

    /**
     * @Route("/{id}/find_beneficiary", name="shift_exchange_find_beneficiary")
     * @Method({"POST"})
     */
    public function findBeneficiaryAction(Request $request, Shift $shift){
        $search_form = $this->createFormBuilder()
            ->setAction($this->generateUrl('shift_exchange_find_beneficiary', array('id' => $shift->getId())))
            ->add('firstname', TextType::class, array('label' => 'le prénom'))
            ->setMethod('POST')
            ->getForm();

        $session = new Session();

        if ($search_form->handleRequest($request)->isValid()) {
            $firstname = $search_form->get('firstname')->getData();
            $em = $this->getDoctrine()->getManager();
            $qb = $em->createQueryBuilder();
            $beneficiaries = $qb->select('b')->from('AppBundle\Entity\Beneficiary', 'b')
                ->join('b.user', 'u')
                ->join('b.membership', 'm')
                ->where( $qb->expr()->like('b.firstname', $qb->expr()->literal('%'.$firstname.'%')))
                ->andWhere("m.withdrawn != 1 or m.withdrawn is NULL" )
                ->orderBy("m.member_number", 'ASC')
                ->getQuery()
                ->getResult();
            return $this->render('beneficiary/find_member_number.html.twig', array(
                'form' => null,
                'beneficiaries' => $beneficiaries,
                'return_path' => 'shift_exchange_new',
                'routeParam' => 'beneficiary',
                'params' => ['id' => $shift->getId()]
            ));
        }
        $session->getFlashBag()->add('error',"oups, quelque chose c'est mal passé");
        return $this->redirectToRoute("shift_exchange_new");
    }

    /**
     * Accept a shift exchange
     *
     * @Route("/{id}/accept/", name="shift_exchange_accept")
     * @Method("GET")
     */
    public function acceptShiftExchangeAction(Request $request, ShiftExchange $shift_exchange, \Swift_Mailer $mailer)
    {
        $session = new Session();
        $this->checkShiftExchangeAction($session, $shift_exchange);
        $em = $this->getDoctrine()->getManager();
        $shift = $shift_exchange->getShift();
        $shift->setShifter($shift_exchange->getReceiver());
        $shift_exchange->setStatus("Accepté");
        $em->persist($shift);
        $em->persist($shift_exchange);
        $em->flush();
        $session->getFlashBag()->add('success', 'L\'échange de créneaux a bien été accepté !');
        $this->sendShiftExchangeMail($shift_exchange, $mailer);
        return $this->redirectToRoute('homepage');
    }

    /**
     * Refuse a shift exchange
     *
     * @Route("/{id}/reject/", name="shift_exchange_reject")
     * @Method("GET")
     */
    public function rejectShiftExchangeAction(Request $request, ShiftExchange $shift_exchange, \Swift_Mailer $mailer)
    {
        $session = new Session();
        $this->checkShiftExchangeAction($session, $shift_exchange);
        $em = $this->getDoctrine()->getManager();
        $shift_exchange->setStatus("Refusé");
        $em->persist($shift_exchange);
        $em->flush();
        $session->getFlashBag()->add('success', 'L\'échange de créneaux a bien été refusé !');
        $this->sendShiftExchangeMail($shift_exchange, $mailer);
        return $this->redirectToRoute('homepage');
    }

    public function checkShiftExchangeAction(Session $session, ShiftExchange $shift_exchange){
        if (!$shift_exchange){
            $session->getFlashBag()->add('error',"Oups, quelque chose c'est mal passé. Merci de contacter le bureau des membres");
            return $this->redirectToRoute('homepage');
        }
        if ($shift_exchange->getStatus() == "Validé"){
            $session->getFlashBag()->add('error',"Oups, l'échange a déjà été validé");
            return $this->redirectToRoute('homepage');
        }
        if ($shift_exchange->getStatus() == "Refusé"){
            $session->getFlashBag()->add('error',"Oups, l'échange a déjà été refusé");
            return $this->redirectToRoute('homepage');
        }
    }

    public function sendShiftExchangeMail(ShiftExchange $shift_exchange,\Swift_Mailer $mailer){

        $receiver = $shift_exchange->getReceiver();
        $giver = $shift_exchange->getGiver();

        $shiftEmail = $this->getParameter('emails.shift');
        $receiverEmail = (new \Swift_Message('[ESPACE MEMBRE] '.$shift_exchange->getGiver()->getFirstname().' veut échanger son créneau du '.$shift_exchange->getShift()->getStart()->format('l j F').' de '.$shift_exchange->getShift()->getStart()->format('G\hi').' à '.$shift_exchange->getShift()->getEnd()->format('G\hi').' avec toi'))
            ->setFrom($shiftEmail['address'], $shiftEmail['from_name'])
            ->setTo([$shift_exchange->getReceiver()->getEmail() => $shift_exchange->getReceiver()->getFirstname() . ' ' . $shift_exchange->getReceiver()->getLastname()])
            ->setBody(
                $this->renderView(
                    'emails/shift_exchange_receiver.html.twig',
                    array(
                        'shift_exchange' => $shift_exchange,
                        'accept_url' => $this->generateUrl('shift_exchange_accept',array('id' => $shift_exchange->getId(), UrlGeneratorInterface::ABSOLUTE_URL)),
                        'reject_url' => $this->generateUrl('shift_exchange_reject',array('id' => $shift_exchange->getId(), UrlGeneratorInterface::ABSOLUTE_URL)),
                    )
                ),
                'text/html'
            );
        $giverEmail = (new \Swift_Message('[ESPACE MEMBRE] Ta demande d\'échange pour le créneau du '.$shift_exchange->getShift()->getStart()->format('l j F').' de '.$shift_exchange->getShift()->getStart()->format('G\hi').' à '.$shift_exchange->getShift()->getEnd()->format('G\hi').' a bien été envoyée à '.$shift_exchange->getReceiver()->getFirstname()))
            ->setFrom($shiftEmail['address'], $shiftEmail['from_name'])
            ->setTo([$shift_exchange->getGiver()->getEmail() => $shift_exchange->getGiver()->getFirstname() . ' ' . $shift_exchange->getGiver()->getLastname()])
            ->setBody(
                $this->renderView(
                    'emails/shift_exchange_giver.html.twig',
                    array(
                        'shift_exchange' => $shift_exchange,
                    )
                ),
                'text/html'
            );
        $mailer->send($receiverEmail);
        $mailer->send($giverEmail);

    }

}
