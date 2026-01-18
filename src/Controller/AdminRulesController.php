<?php

namespace App\Controller;

use App\Entity\RegleIntervention;
use App\Form\RegleInterventionType;
use App\Repository\RegleInterventionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminRulesController extends AbstractController
{
    #[Route('/admin/rules', name: 'app_admin_rules')]
    public function index(RegleInterventionRepository $regleRepository): Response
    {
        $regles = $regleRepository->findAllWithIndicateurs();

        return $this->render('admin_rules/index.html.twig', [
            'regles' => $regles,
        ]);
    }

    #[Route('/admin/rules/{id}/edit', name: 'app_admin_rules_edit')]
    public function edit(Request $request, RegleIntervention $regle, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RegleInterventionType::class, $regle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Règle mise à jour avec succès');

            return $this->redirectToRoute('app_admin_rules', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_rules/edit.html.twig', [
            'regle' => $regle,
            'form' => $form,
        ]);
    }
}
