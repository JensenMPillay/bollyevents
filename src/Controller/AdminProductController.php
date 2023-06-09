<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Tag;
use App\Service\FileService;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

#[Route(path: '/admin/product', name: 'app_admin_product')]
class AdminProductController extends AbstractController
{
    #[Route(path: '/', name: '')]
    public function product(ProductRepository $productRepo): Response
    {
        $products = $productRepo->findAll();

        // View
        return $this->render("admin/admin_product/product.html.twig", ["products" => $products]);
    }

    #[Route(path: '/create', name: '_create')]
    public function productCreate(Request $request, ManagerRegistry $managerRegistry, SluggerInterface $slugger, FileService $fileService, Product $product = null): Response
    {
        $product = new Product;

        $form = $this->createForm(ProductType::class, $product);

        // Handle Submit
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $directoryName = 'images_products_directory';

            $product->setCreatedAt(new \DateTimeImmutable());

            // Carousel
            $carouselImages = $product->getCarousel();

            for ($i = 0; $i < 5; $i++) {
                $imageFile = $form->get("image" . $i)->getData();
                if ($imageFile) {
                    $carouselImages[] = $fileService->uploadImage($imageFile, $slugger, $directoryName);
                }
            }

            // Update carousel images
            $product->setCarousel($carouselImages);

            // Traitement BDD
            $manager = $managerRegistry->getManager();
            $manager->persist($product);
            $manager->flush();

            // Msg Flash
            $this->addFlash('add_product_success', "Product Created!");

            // Redirection
            return $this->redirectToRoute('app_admin_product');
        }

        // View
        return $this->render("admin/admin_product/product_create_edit.html.twig", [
            'productForm' => $form->createView(),
            'edit' => false
        ]);
    }

    #[Route(path: '/edit/{id}', name: '_edit', requirements: ['id' => '\d+'])]
    public function productEdit(Request $request, ManagerRegistry $managerRegistry, SluggerInterface $slugger, FileService $fileService, Product $product = null): Response
    {
        // is Product ? 
        if (!$product) {
            return $this->redirectToRoute('app_admin_product_create');
        }

        $form = $this->createForm(ProductType::class, $product);

        $directoryName = 'images_products_directory';

        $carouselImages = $product->getCarousel();

        for ($i = 0; $i < 5; $i++) {
            $form->get("image" . $i)->setData(isset($carouselImages[$i]) ? new File($this->getParameter($directoryName) . '/' . $carouselImages[$i]) : null);
        }

        // Handle Submit
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $product->setUpdatedAt(new \DateTimeImmutable());

            // Carousel

            for ($i = 0; $i < 5; $i++) {
                $imageFile = $form->get("image" . $i)->getData();
                if ($imageFile) {
                    if (isset($carouselImages[$i])) {
                        $fileExisting = $this->getParameter($directoryName) . '/' . $carouselImages[$i];
                        if (file_exists($fileExisting)) {
                            unlink($fileExisting);
                        }
                    };
                    $carouselImages[$i] = $fileService->uploadImage($imageFile, $slugger, $directoryName);
                };
            }

            // Update carousel images
            $product->setCarousel($carouselImages);

            // Traitement BDD
            $manager = $managerRegistry->getManager();
            $manager->persist($product);
            $manager->flush();

            // Msg Flash
            $this->addFlash('add_product_success', "Product Updated!");

            // Redirection
            return $this->redirectToRoute('app_admin_product');
        }

        // View
        return $this->render("admin/admin_product/product_create_edit.html.twig", [
            'productForm' => $form->createView(),
            'edit' => true,
        ]);
    }

    #[Route(path: '/delete/{id}', name: '_delete', requirements: ['id' => '\d+'])]
    public function productDelete(ManagerRegistry $managerRegistry, Product $product = null): RedirectResponse
    {
        if ($product) {

            $directoryName = 'images_products_directory';

            // Suppression des fichiers du Carousel
            $fileNames = $product->getCarousel();
            foreach ($fileNames as $fileName) {
                $realFile = $this->getParameter($directoryName) . '/' . $fileName;
                if (file_exists($realFile)) {
                    unlink($realFile);
                };
            }

            // Suppression BDD
            $manager = $managerRegistry->getManager();
            $manager->remove($product);
            $manager->flush();
            $this->addFlash('delete_product_success', "Product Deleted!");

            // Redirection
            return $this->redirectToRoute('app_admin_product');
        }
    }
}
