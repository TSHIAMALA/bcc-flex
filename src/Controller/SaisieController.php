<?php

namespace App\Controller;

use App\Entity\ConjonctureJour;
use App\Entity\MarcheChanges;
use App\Entity\ReservesFinancieres;
use App\Entity\FinancesPubliques;
use App\Entity\TresorerieEtat;
use App\Form\ConjonctureType;
use App\Form\MarcheChangesType;
use App\Form\ReservesFinancieresType;
use App\Form\FinancesPubliquesType;
use App\Form\TresorerieEtatType;
use App\Repository\ConjonctureJourRepository;
use App\Repository\MarcheChangesRepository;
use App\Repository\ReservesFinancieresRepository;
use App\Repository\FinancesPubliquesRepository;
use App\Repository\TresorerieEtatRepository;
use App\Event\ConjonctureDataUpdatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SaisieController extends AbstractController
{
    #[Route('/saisie', name: 'app_saisie')]
    public function index(ConjonctureJourRepository $conjonctureRepository): Response
    {
        $conjonctures = $conjonctureRepository->findBy([], ['date_situation' => 'DESC']);

        return $this->render('saisie/index.html.twig', [
            'conjonctures' => $conjonctures,
        ]);
    }

    #[Route('/saisie/new', name: 'app_saisie_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $conjoncture = new ConjonctureJour();
        $form = $this->createForm(ConjonctureType::class, $conjoncture);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($conjoncture);
            $em->flush();

            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId()]);
        }

        return $this->render('saisie/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/saisie/{id}/edit', name: 'app_saisie_edit')]
    public function edit(
        Request $request, 
        ConjonctureJour $conjoncture, 
        EntityManagerInterface $em,
        MarcheChangesRepository $marcheRepo,
        ReservesFinancieresRepository $reservesRepo,
        FinancesPubliquesRepository $financesRepo,
        TresorerieEtatRepository $tresorerieRepo,
        \App\Repository\EncoursBccRepository $encoursRepo,
        \App\Repository\PaieEtatRepository $paieRepo,
        \App\Repository\TransactionsUsdRepository $transacRepo,
        EventDispatcherInterface $eventDispatcher
    ): Response
    {
        // 1. Marche des Changes
        $marche = $marcheRepo->findOneBy(['conjoncture' => $conjoncture]) ?? new MarcheChanges();
        $marche->setConjoncture($conjoncture);
        $formMarche = $this->createForm(MarcheChangesType::class, $marche);
        
        // 2. Reserves
        $reserves = $reservesRepo->findOneBy(['conjoncture' => $conjoncture]) ?? new ReservesFinancieres();
        $reserves->setConjoncture($conjoncture);
        $formReserves = $this->createForm(ReservesFinancieresType::class, $reserves);

        // 3. Finances
        $finances = $financesRepo->findOneBy(['conjoncture' => $conjoncture]) ?? new FinancesPubliques();
        $finances->setConjoncture($conjoncture);
        $formFinances = $this->createForm(FinancesPubliquesType::class, $finances);

        // 4. Tresorerie
        $tresorerie = $tresorerieRepo->findOneBy(['conjoncture' => $conjoncture]) ?? new TresorerieEtat();
        $tresorerie->setConjoncture($conjoncture);
        $formTresorerie = $this->createForm(TresorerieEtatType::class, $tresorerie);

        // 5. Encours BCC
        $encours = $encoursRepo->findOneBy(['conjoncture' => $conjoncture]) ?? new \App\Entity\EncoursBcc();
        $encours->setConjoncture($conjoncture);
        $formEncours = $this->createForm(\App\Form\EncoursBccType::class, $encours);

        // 6. Paie Etat
        $paie = $paieRepo->findOneBy(['conjoncture' => $conjoncture]) ?? new \App\Entity\PaieEtat();
        $paie->setConjoncture($conjoncture);
        $formPaie = $this->createForm(\App\Form\PaieEtatType::class, $paie);

        // 7. Transactions USD
        $newTransaction = new \App\Entity\TransactionsUsd();
        $newTransaction->setConjoncture($conjoncture);
        $formTransaction = $this->createForm(\App\Form\TransactionsUsdType::class, $newTransaction);

        // Handle Requests
        $formMarche->handleRequest($request);
        $formReserves->handleRequest($request);
        $formFinances->handleRequest($request);
        $formTresorerie->handleRequest($request);
        $formEncours->handleRequest($request);
        $formPaie->handleRequest($request);
        $formTransaction->handleRequest($request);

        if ($formMarche->isSubmitted() && $formMarche->isValid()) {
            $em->persist($marche);
            $em->flush();
            $this->addFlash('success', 'Marché des changes enregistré.');
            $eventDispatcher->dispatch(new ConjonctureDataUpdatedEvent($conjoncture, 'saisie_marche'), ConjonctureDataUpdatedEvent::NAME);
            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId(), 'tab' => 'marche']);
        }

        if ($formReserves->isSubmitted() && $formReserves->isValid()) {
            $em->persist($reserves);
            $em->flush();
            $this->addFlash('success', 'Réserves enregistrées.');
            $eventDispatcher->dispatch(new ConjonctureDataUpdatedEvent($conjoncture, 'saisie_reserves'), ConjonctureDataUpdatedEvent::NAME);
            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId(), 'tab' => 'reserves']);
        }

        if ($formFinances->isSubmitted() && $formFinances->isValid()) {
            $em->persist($finances);
            $em->flush();
            $this->addFlash('success', 'Finances publiques enregistrées.');
            $eventDispatcher->dispatch(new ConjonctureDataUpdatedEvent($conjoncture, 'saisie_finances'), ConjonctureDataUpdatedEvent::NAME);
            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId(), 'tab' => 'finances']);
        }

        if ($formTresorerie->isSubmitted() && $formTresorerie->isValid()) {
            $em->persist($tresorerie);
            $em->flush();
            $this->addFlash('success', 'Trésorerie enregistrée.');
            $eventDispatcher->dispatch(new ConjonctureDataUpdatedEvent($conjoncture, 'saisie_tresorerie'), ConjonctureDataUpdatedEvent::NAME);
            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId(), 'tab' => 'tresorerie']);
        }

        if ($formEncours->isSubmitted() && $formEncours->isValid()) {
            $em->persist($encours);
            $em->flush();
            $this->addFlash('success', 'Encours BCC enregistrés.');
            $eventDispatcher->dispatch(new ConjonctureDataUpdatedEvent($conjoncture, 'saisie_encours'), ConjonctureDataUpdatedEvent::NAME);
            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId(), 'tab' => 'encours']);
        }

        if ($formPaie->isSubmitted() && $formPaie->isValid()) {
            $em->persist($paie);
            $em->flush();
            $this->addFlash('success', 'Paie État enregistrée.');
            $eventDispatcher->dispatch(new ConjonctureDataUpdatedEvent($conjoncture, 'saisie_paie'), ConjonctureDataUpdatedEvent::NAME);
            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId(), 'tab' => 'paie']);
        }

        if ($formTransaction->isSubmitted() && $formTransaction->isValid()) {
            $em->persist($newTransaction);
            $em->flush();
            $this->addFlash('success', 'Transaction ajoutée.');
            $eventDispatcher->dispatch(new ConjonctureDataUpdatedEvent($conjoncture, 'saisie_transaction'), ConjonctureDataUpdatedEvent::NAME);
            return $this->redirectToRoute('app_saisie_edit', ['id' => $conjoncture->getId(), 'tab' => 'transactions']);
        }

        // Fetch existing transactions
        $transactions = $transacRepo->findBy(['conjoncture' => $conjoncture], ['id' => 'DESC']);

        return $this->render('saisie/edit.html.twig', [
            'conjoncture' => $conjoncture,
            'formMarche' => $formMarche->createView(),
            'formReserves' => $formReserves->createView(),
            'formFinances' => $formFinances->createView(),
            'formTresorerie' => $formTresorerie->createView(),
            'formEncours' => $formEncours->createView(),
            'formPaie' => $formPaie->createView(),
            'formTransaction' => $formTransaction->createView(),
            'transactions' => $transactions,
        ]);
    }

    #[Route('/saisie/transaction/{id}/delete', name: 'app_saisie_transaction_delete', methods: ['POST'])]
    public function deleteTransaction(Request $request, \App\Entity\TransactionsUsd $transaction, EntityManagerInterface $em): Response
    {
        $conjonctureId = $transaction->getConjoncture()->getId();
        if ($this->isCsrfTokenValid('delete'.$transaction->getId(), $request->request->get('_token'))) {
            $em->remove($transaction);
            $em->flush();
            $this->addFlash('success', 'Transaction supprimée.');
        }

        return $this->redirectToRoute('app_saisie_edit', ['id' => $conjonctureId, 'tab' => 'transactions']);
    }
}
