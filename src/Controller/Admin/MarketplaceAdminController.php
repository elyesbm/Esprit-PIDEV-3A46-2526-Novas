<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Repository\UserRepository;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/marketplace', name: 'app_admin_marketplace_')]
class MarketplaceAdminController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function list(
        Request $request,
        ArticleRepository $articleRepository,
        CategorieRepository $categorieRepository
    ): Response {
        // Filtres & tri ARTICLES depuis la query string
        $q = trim((string) $request->query->get('q', ''));
        $statutFilter = $request->query->get('statut');
        $typeFilter = $request->query->get('type');
        $categorieFilter = $request->query->get('categorie');
        $sort = $request->query->get('sort', 'date_desc');

        $criteria = [];
        if ($statutFilter) {
            $criteria['statut_article'] = $statutFilter;
        }
        if ($typeFilter) {
            $criteria['type_article'] = $typeFilter;
        }
        if ($categorieFilter) {
            $catEntity = $categorieRepository->find((int) $categorieFilter);
            if ($catEntity instanceof Categorie) {
                $criteria['categorie'] = $catEntity;
            }
        }

        $order = ['id' => 'DESC'];
        switch ($sort) {
            case 'price_asc':
                $order = ['prix_article' => 'ASC'];
                break;
            case 'price_desc':
                $order = ['prix_article' => 'DESC'];
                break;
            case 'date_asc':
                $order = ['id' => 'ASC'];
                break;
            case 'date_desc':
            default:
                $order = ['id' => 'DESC'];
                break;
        }

        // Utilise la méthode search() existante pour gérer q + critères + tri
        $perPage = 4;
        $currentPage = max(1, (int) $request->query->get('page', 1));
        $offset = ($currentPage - 1) * $perPage;

        $totalArticles = $articleRepository->countSearch($q, $criteria);
        $totalPages = max(1, (int) ceil($totalArticles / $perPage));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
            $offset = ($currentPage - 1) * $perPage;
        }

        $articles = $articleRepository->searchPaginated($q, $criteria, $order, $perPage, $offset);

        // Catégories pour le filtre dans la vue (liste complète)
        $categoriesEntities = $categorieRepository->findAll();

        // Stats: nombre d'articles par categorie
        $categoryStatsRows = $categorieRepository->createQueryBuilder('c')
            ->leftJoin('c.articles', 'a')
            ->select('c.id AS id', 'c.nom_categorie AS nom', 'COUNT(a.id) AS article_count')
            ->groupBy('c.id')
            ->orderBy('article_count', 'DESC')
            ->addOrderBy('c.nom_categorie', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $categoryArticleStats = array_map(static function (array $row): array {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'nom' => (string) ($row['nom'] ?? 'Sans nom'),
                'article_count' => (int) ($row['article_count'] ?? 0),
            ];
        }, $categoryStatsRows);

        $categoryMostFilled = $categoryArticleStats[0] ?? null;
        $categoryEmptyCount = count(array_filter(
            $categoryArticleStats,
            static fn (array $item): bool => (int) $item['article_count'] === 0
        ));

        $popularArticlesRows = $articleRepository->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c')
            ->orderBy('a.vues_article', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $popularArticles = array_map(static function (Article $article): array {
            return [
                'id' => (int) ($article->getId() ?? 0),
                'titre' => (string) ($article->getTitreArticle() ?? 'Sans titre'),
                'vues' => (int) ($article->getVuesArticle() ?? 0),
                'categorie' => $article->getCategorie() instanceof Categorie ? (string) ($article->getCategorie()->getNomCategorie() ?? 'Autre') : 'Autre',
            ];
        }, $popularArticlesRows);

        $mostPopularArticle = $popularArticles[0] ?? null;
        // RECHERCHE / TRI CATEGORIES pour l'onglet catégories
        $qCat = trim((string) $request->query->get('q_cat', ''));
        $sortCat = $request->query->get('sort_cat', 'date_desc');

        $qb = $categorieRepository->createQueryBuilder('c');

        if ($qCat !== '') {
            $isNumeric = ctype_digit($qCat);
            $expr = 'c.nom_categorie LIKE :q OR c.description_categorie LIKE :q';
            if ($isNumeric) {
                $expr .= ' OR c.id = :qid';
            }

            $qb->andWhere('(' . $expr . ')')
               ->setParameter('q', '%' . $qCat . '%');

            if ($isNumeric) {
                $qb->setParameter('qid', (int) $qCat);
            }
        }

        switch ($sortCat) {
            case 'name_asc':
                $qb->orderBy('c.nom_categorie', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('c.nom_categorie', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('c.id', 'ASC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('c.id', 'DESC');
                break;
        }

        $perPageCat = 3;
        $currentPageCat = max(1, (int) $request->query->get('page_cat', 1));
        $offsetCat = ($currentPageCat - 1) * $perPageCat;

        $countQb = clone $qb;
        $categoriesTotal = (int) $countQb
            ->resetDQLPart('orderBy')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPagesCat = max(1, (int) ceil($categoriesTotal / $perPageCat));
        if ($currentPageCat > $totalPagesCat) {
            $currentPageCat = $totalPagesCat;
            $offsetCat = ($currentPageCat - 1) * $perPageCat;
        }

        $categoriesList = $qb
            ->setFirstResult($offsetCat)
            ->setMaxResults($perPageCat)
            ->getQuery()
            ->getResult();

        return $this->render('admin/marketplace/list.html.twig', [
            'articles' => $articles,
            'articles_total' => $totalArticles,
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'categories' => $categoriesEntities,
            'categories_list' => $categoriesList,
            'categories_total' => $categoriesTotal,
            'current_page_cat' => $currentPageCat,
            'total_pages_cat' => $totalPagesCat,
            'category_article_stats' => $categoryArticleStats,
            'category_most_filled' => $categoryMostFilled,
            'category_empty_count' => $categoryEmptyCount,
            'popular_articles' => $popularArticles,
            'most_popular_article' => $mostPopularArticle,
        ]);
    }

    #[Route('/export/pdf', name: 'export_pdf', methods: ['GET'])]
    public function exportPdfDisponibles(ArticleRepository $articleRepository): Response
    {
        $articlesEntities = $articleRepository->createQueryBuilder('a')
            ->leftJoin('a.categorie', 'c')
            ->addSelect('c')
            ->where('a.statut_article IS NULL OR LOWER(TRIM(a.statut_article)) = :statut')
            ->setParameter('statut', 'disponible')
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();

        $articles = array_map(static function (Article $article): array {
            $categorie = $article->getCategorie();
            return [
                'titre' => (string) ($article->getTitreArticle() ?? 'Sans titre'),
                'categorie' => $categorie instanceof Categorie ? (string) ($categorie->getNomCategorie() ?? 'Autre') : 'Autre',
                'prix' => (float) ($article->getPrixArticle() ?? 0),
                'description' => trim((string) ($article->getContenueArticle() ?? '')),
                'statut' => (string) ($article->getStatutArticle() ?? 'disponible'),
            ];
        }, $articlesEntities);

        $html = $this->renderView('front/marketplace/pdf_disponibles.html.twig', [
            'articles' => $articles,
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isFontSubsettingEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="admin-marketplace-produits-disponibles-' . (new \DateTimeImmutable())->format('Ymd-His') . '.pdf"',
        ]);
    }

    #[Route('/categorie/export/pdf', name: 'categorie_export_pdf', methods: ['GET'])]
    public function exportCategoriesPdf(Request $request, CategorieRepository $categorieRepository): Response
    {
        $qCat = trim((string) $request->query->get('q_cat', ''));
        $sortCat = $request->query->get('sort_cat', 'date_desc');

        $qb = $categorieRepository->createQueryBuilder('c');
        if ($qCat !== '') {
            $isNumeric = ctype_digit($qCat);
            $expr = 'c.nom_categorie LIKE :q OR c.description_categorie LIKE :q';
            if ($isNumeric) {
                $expr .= ' OR c.id = :qid';
            }
            $qb->andWhere('(' . $expr . ')')
                ->setParameter('q', '%' . $qCat . '%');
            if ($isNumeric) {
                $qb->setParameter('qid', (int) $qCat);
            }
        }

        switch ($sortCat) {
            case 'name_asc':
                $qb->orderBy('c.nom_categorie', 'ASC');
                break;
            case 'name_desc':
                $qb->orderBy('c.nom_categorie', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('c.id', 'ASC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('c.id', 'DESC');
                break;
        }

        $categories = $qb->getQuery()->getResult();

        $html = $this->renderView('admin/marketplace/pdf_categories.html.twig', [
            'categories' => $categories,
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $options->set('isFontSubsettingEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="admin-marketplace-categories-' . (new \DateTimeImmutable())->format('Ymd-His') . '.pdf"',
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        CategorieRepository $categorieRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserRepository $userRepository
    ): Response {
        $categoriesEntities = $categorieRepository->findAll();

        $categories = array_map(static function (Categorie $categorie): array {
            return [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNomCategorie(),
            ];
        }, $categoriesEntities);

        $article = new Article();

        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('image');
            $data = [
                'titre' => trim((string) $request->request->get('titre', '')),
                'contenu' => trim((string) $request->request->get('contenu', '')),
                'prix' => $request->request->get('prix', ''),
                'categorie' => $request->request->get('categorie'),
                'type' => trim((string) $request->request->get('type', 'academic')),
                'statut' => trim((string) $request->request->get('statut', 'disponible')),
                'image_article' => trim((string) $request->request->get('image_article', '')) ?: 'skills-learning.jpg',
            ];

            $constraints = new Assert\Collection([
                'fields' => [
                    'titre' => [
                        new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                        new Assert\Length([
                            'min' => 2,
                            'max' => 255,
                            'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
                            'maxMessage' => 'Le titre ne doit pas dépasser {{ limit }} caractères.',
                        ]),
                    ],
                    'contenu' => [
                        new Assert\NotBlank(['message' => 'Le contenu est obligatoire.']),
                        new Assert\Length([
                            'min' => 10,
                            'max' => 5000,
                            'minMessage' => 'Le contenu doit contenir au moins {{ limit }} caractères.',
                            'maxMessage' => 'Le contenu est trop long (max {{ limit }} caractères).',
                        ]),
                    ],
                    'prix' => [
                        new Assert\NotBlank(['message' => 'Le prix est obligatoire.']),
                        new Assert\Regex([
                            'pattern' => '/^\d+(?:[\.,]\d+)?$/',
                            'message' => 'Le prix doit être un nombre.',
                        ]),
                        new Assert\Range([
                            'min' => 0,
                            'max' => 1000000,
                            'notInRangeMessage' => 'Le prix doit être compris entre {{ min }} et {{ max }}.',
                        ]),
                    ],
                    'categorie' => [
                        new Assert\NotBlank(['message' => 'La catégorie est obligatoire.']),
                        new Assert\Regex([
                            'pattern' => '/^\d+$/',
                            'message' => 'La catégorie sélectionnée est invalide.',
                        ]),
                    ],
                    'type' => new Assert\Choice([
                        'choices' => ['academic', 'commercial', 'service', 'other'],
                        'message' => 'Type invalide.',
                    ]),
                    'statut' => new Assert\Choice([
                        'choices' => ['disponible', 'vendu', 'reserve'],
                        'message' => 'Statut invalide.',
                    ]),
                    'image_article' => new Assert\Optional(),
                ],
                'allowExtraFields' => true,
            ]);

            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
            } else {
                // Gestion upload image si présente (sans utiliser FileValidator pour éviter fileinfo)
                if ($uploadedFile instanceof UploadedFile) {
                    $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: '');
                    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $allowedExt, true)) {
                        $this->addFlash('error', 'Le format de l\'image est invalide (jpg, png, gif, webp uniquement).');
                        return $this->redirectToRoute('app_admin_marketplace_new');
                    }

                    $uploadsDir = $this->projectDir() . '/public/images';
                    $newFilename = uniqid('article_', true) . '.' . $ext;
                    try {
                        $uploadedFile->move($uploadsDir, $newFilename);
                        $data['image_article'] = $newFilename;
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                        return $this->redirectToRoute('app_admin_marketplace_new');
                    }
                }

                $categorieId = (int) $data['categorie'];
                $categorie = $categorieRepository->find($categorieId);

                if (!$categorie instanceof Categorie) {
                    $this->addFlash('error', 'La catégorie sélectionnée est invalide.');
                } else {
                    $prix = (float) str_replace(',', '.', (string) $data['prix']);

                    $article
                        ->setTitreArticle($data['titre'])
                        ->setContenueArticle($data['contenu'])
                        ->setImageArticle($data['image_article'])
                        ->setTypeArticle($data['type'] ?: 'academic')
                        ->setPrixArticle($prix)
                        ->setStatutArticle($data['statut'] ?: 'disponible')
                        ->setCategorie($categorie);

                    // Associer un auteur existant même si personne n'est connecté
                    $author = $this->getUser();
                    if ($author instanceof User) {
                        $article->setAuteur($author);
                    } else {
                        $fallbackAuthor = $userRepository->findOneBy([]);
                        if ($fallbackAuthor instanceof User) {
                            $article->setAuteur($fallbackAuthor);
                        } else {
                            $this->addFlash('error', 'Aucun utilisateur trouvé pour associer l\'article. Créez au moins un utilisateur.');
                            return $this->redirectToRoute('app_admin_marketplace_list');
                        }
                    }

                    $entityManager->persist($article);
                    $entityManager->flush();

                    $this->addFlash('success', 'Article créé avec succès.');
                    return $this->redirectToRoute('app_admin_marketplace_list');
                }
            }
        }

        return $this->render('admin/marketplace/form.html.twig', [
            'mode' => 'create',
            'article' => $article,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        ArticleRepository $articleRepository,
        CategorieRepository $categorieRepository,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $article = $articleRepository->find($id);
        if (!$article instanceof Article) {
            throw $this->createNotFoundException('Article introuvable.');
        }

        $categoriesEntities = $categorieRepository->findAll();

        $categories = array_map(static function (Categorie $categorie): array {
            return [
                'id' => $categorie->getId(),
                'nom' => $categorie->getNomCategorie(),
            ];
        }, $categoriesEntities);

        if ($request->isMethod('POST')) {
            /** @var UploadedFile|null $uploadedFile */
            $uploadedFile = $request->files->get('image');
            $data = [
                'titre' => trim((string) $request->request->get('titre', '')),
                'contenu' => trim((string) $request->request->get('contenu', '')),
                'prix' => $request->request->get('prix', ''),
                'categorie' => $request->request->get('categorie'),
                'type' => trim((string) $request->request->get('type', 'academic')),
                'statut' => trim((string) $request->request->get('statut', 'disponible')),
                'image_article' => trim((string) $request->request->get('image_article', '')) ?: $article->getImageArticle() ?: 'skills-learning.jpg',
            ];

            $constraints = new Assert\Collection([
                'fields' => [
                    'titre' => [
                        new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                        new Assert\Length(['min' => 2, 'max' => 255]),
                    ],
                    'contenu' => [
                        new Assert\NotBlank(['message' => 'Le contenu est obligatoire.']),
                        new Assert\Length(['min' => 10, 'max' => 5000]),
                    ],
                    'prix' => [
                        new Assert\NotBlank(['message' => 'Le prix est obligatoire.']),
                        new Assert\Regex(['pattern' => '/^\d+(?:[\.,]\d+)?$/', 'message' => 'Le prix doit être un nombre.']),
                        new Assert\Range(['min' => 0, 'max' => 1000000]),
                    ],
                    'categorie' => [
                        new Assert\NotBlank(['message' => 'La catégorie est obligatoire.']),
                        new Assert\Regex(['pattern' => '/^\d+$/', 'message' => 'La catégorie sélectionnée est invalide.']),
                    ],
                    'type' => new Assert\Choice(['choices' => ['academic', 'commercial', 'service', 'other']]),
                    'statut' => new Assert\Choice(['choices' => ['disponible', 'vendu', 'reserve']]),
                    'image_article' => new Assert\Optional(),
                ],
                'allowExtraFields' => true,
            ]);

            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
            } else {
                // Gestion upload image si présente (sans FileValidator)
                if ($uploadedFile instanceof UploadedFile) {
                    $ext = strtolower($uploadedFile->getClientOriginalExtension() ?: '');
                    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $allowedExt, true)) {
                        $this->addFlash('error', 'Le format de l\'image est invalide (jpg, png, gif, webp uniquement).');
                        return $this->redirectToRoute('app_admin_marketplace_edit', ['id' => $id]);
                    }

                    $uploadsDir = $this->projectDir() . '/public/images';
                    $newFilename = uniqid('article_', true) . '.' . $ext;
                    try {
                        $uploadedFile->move($uploadsDir, $newFilename);
                        $data['image_article'] = $newFilename;
                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                        return $this->redirectToRoute('app_admin_marketplace_edit', ['id' => $id]);
                    }
                }

                $categorieId = (int) $data['categorie'];
                $categorie = $categorieRepository->find($categorieId);

                if (!$categorie instanceof Categorie) {
                    $this->addFlash('error', 'La catégorie sélectionnée est invalide.');
                } else {
                    $prix = (float) str_replace(',', '.', (string) $data['prix']);

                    $article
                        ->setTitreArticle($data['titre'])
                        ->setContenueArticle($data['contenu'])
                        ->setImageArticle($data['image_article'])
                        ->setTypeArticle($data['type'] ?: 'academic')
                        ->setPrixArticle($prix)
                        ->setStatutArticle($data['statut'] ?: 'disponible')
                        ->setCategorie($categorie);

                    $entityManager->flush();

                    $this->addFlash('success', 'Article modifié avec succès.');
                    return $this->redirectToRoute('app_admin_marketplace_list');
                }
            }
        }

        return $this->render('admin/marketplace/form.html.twig', [
            'mode' => 'edit',
            'article' => $article,
            'categories' => $categories,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        ArticleRepository $articleRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_article_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_marketplace_list');
        }

        $article = $articleRepository->find($id);
        if ($article instanceof Article) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('success', 'Article supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_marketplace_list');
    }

    #[Route('/categorie/new', name: 'categorie_new', methods: ['GET', 'POST'])]
    public function categorieNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $categorie = new Categorie();

        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom', ''));
            $description = trim((string) $request->request->get('description', ''));

            $errors = [];
            if ($nom === '') {
                $errors[] = 'Le nom de la catégorie est obligatoire.';
            } else {
                $len = mb_strlen($nom);
                if ($len < 2) {
                    $errors[] = 'Le nom doit contenir au moins 2 caractères.';
                }
                if ($len > 255) {
                    $errors[] = 'Le nom ne doit pas dépasser 255 caractères.';
                }
            }
            if (mb_strlen($description) > 1000) {
                $errors[] = 'La description est trop longue (max 1000 caractères).';
            }

            if (!empty($errors)) {
                foreach ($errors as $err) {
                    $this->addFlash('error', $err);
                }
            } else {
                $categorie
                    ->setNomCategorie($nom)
                    ->setDescriptionCategorie($description);

                $entityManager->persist($categorie);
                $entityManager->flush();

                $this->addFlash('success', 'Catégorie créée avec succès.');
                return $this->redirectToRoute('app_admin_marketplace_list', ['tab' => 'categories']);
            }
        }

        return $this->render('admin/marketplace/categorie_form.html.twig', [
            'mode' => 'create',
            'categorie' => $categorie,
        ]);
    }

    #[Route('/categorie/{id}/edit', name: 'categorie_edit', methods: ['GET', 'POST'])]
    public function categorieEdit(Request $request, int $id, CategorieRepository $categorieRepository, EntityManagerInterface $entityManager): Response
    {
        $categorie = $categorieRepository->find($id);
        if (!$categorie instanceof Categorie) {
            throw $this->createNotFoundException('Catégorie introuvable.');
        }

        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom', ''));
            $description = trim((string) $request->request->get('description', ''));

            if ($nom === '') {
                $this->addFlash('error', 'Le nom de la catégorie est obligatoire.');
            } else {
                $len = mb_strlen($nom);
                if ($len < 2) {
                    $this->addFlash('error', 'Le nom doit contenir au moins 2 caractères.');
                } elseif ($len > 255) {
                    $this->addFlash('error', 'Le nom ne doit pas dépasser 255 caractères.');
                } elseif (mb_strlen($description) > 1000) {
                    $this->addFlash('error', 'La description est trop longue (max 1000 caractères).');
                } else {
                    $categorie
                        ->setNomCategorie($nom)
                        ->setDescriptionCategorie($description);
                    $entityManager->flush();

                    $this->addFlash('success', 'Catégorie modifiée avec succès.');
                    return $this->redirectToRoute('app_admin_marketplace_list', ['tab' => 'categories']);
                }
            }
        }

        return $this->render('admin/marketplace/categorie_form.html.twig', [
            'mode' => 'edit',
            'categorie' => $categorie,
        ]);
    }

    #[Route('/categorie/{id}/delete', name: 'categorie_delete', methods: ['POST'])]
    public function categorieDelete(Request $request, int $id, CategorieRepository $categorieRepository, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_categorie_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_marketplace_list', ['tab' => 'categories']);
        }

        $categorie = $categorieRepository->find($id);
        if ($categorie instanceof Categorie) {
            $entityManager->remove($categorie);
            $entityManager->flush();
            $this->addFlash('success', 'Catégorie supprimée avec succès.');
        }

        return $this->redirectToRoute('app_admin_marketplace_list', ['tab' => 'categories']);
    }

    private function projectDir(): string
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        if (!is_string($projectDir)) {
            throw new \RuntimeException('Invalid kernel.project_dir parameter.');
        }

        return $projectDir;
    }
}
