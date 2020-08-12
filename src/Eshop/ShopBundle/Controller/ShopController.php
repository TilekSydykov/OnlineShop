<?php


namespace Eshop\ShopBundle\Controller;

use Eshop\ShopBundle\Entity\Manufacturer;
use Eshop\ShopBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ShopController extends Controller
{
    /**
     * Lists all Category entities.
     *
     * @Route("/my-shop", name="index-shop")
     * @Method("GET")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $paginator = $this->get('knp_paginator');

        $shopRepository = $em->getRepository('ShopBundle:Manufacturer');
        $productRepository = $em->getRepository('ShopBundle:Product');
        $shop = $shopRepository->getByUser($this->getUser());
        if($shop == null){
            $shop = new Manufacturer();
            $shop->setUser($this->getUser());
            $shop->setSlug($this->getUser()->getUsername() . "'s-shop". rand ( 1 , 255 ));
            $shop->setName($this->getUser()->getUsername() . "'s-shop");
            $shop->setDescription(' ');
            $shop->setMetaDescription(' ');
            $shop->setMetaKeys(' ');
            $em->persist($shop);
            $em->flush();
            return $this->redirectToRoute('edit-shop', ['id'=> $shop->getId()]);
        }
        $productsQuery = $productRepository->findByManufacturerQB($shop, $this->getUser());

        $limit = $this->getParameter('category_products_pagination_count');

        $products = $paginator->paginate(
            $productsQuery,
            $request->query->getInt('page', 1),
            $limit
        );

        return ['shop' => $shop,
            'owner' => true,
            'products' => $products,
            'sortedby' => $this->get('app.page_utilities')->getSortingParamName($request)
        ];
    }

    /**
     * Lists all Category entities.
     *
     * @Route("/my-shop/{id}/edit", name="edit-shop")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editAction(Request $request, Manufacturer $shop)
    {
        // $deleteForm = $this->createDeleteForm($shop);
        $editForm = $this->createForm('Eshop\ShopBundle\Form\Type\ManufacturerType', $shop);
        $editForm->handleRequest($request);

        if($shop->getUser()->getId() != $this->getUser()->getId()) {
            return $this->redirectToRoute('index-shop');
        }

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            if ($editForm->get('file')->getData() !== null) { // if any file was updated
                $file = $editForm->get('file')->getData();
                $shop->removeUpload(); // remove old file, see this at the bottom
                $shop->setPath(($file->getClientOriginalName())); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($shop);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice',
                'Your changes were saved!'
            );

            return $this->redirectToRoute('edit-shop', ['id' => $shop->getId()]);
        }

        return ['shop' => $shop,
            'edit_form' => $editForm->createView(),
            // 'delete_form' => $deleteForm->createView()
        ];
    }

    /**
     * Add product.
     *
     * @Route("/my-shop/{id}/add", name="add-shop-product")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function addProductAction(Request $request, Manufacturer $shop)
    {
        if($shop->getUser()->getId() != $this->getUser()->getId()) {
            return $this->redirectToRoute('index-shop');
        }

        $product = new Product();
        $form = $this->createForm('Eshop\ShopBundle\Form\Type\ProductType', $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //update uploaded images entities
            $imageIdString = $request->request->get('filenames');
            if ($imageIdString != '') {
                $imageIdArray = explode(',', $imageIdString);
                array_pop($imageIdArray);

                $em = $this->getDoctrine()->getManager();
                $imageRepository = $em->getRepository('ShopBundle:Image');
                foreach ($imageIdArray as $imageId) {
                    $image = $imageRepository->find($imageId);
                    $image->setProduct($product);
                    $product->addImage($image);
                    $em->persist($image);
                }
            }

            $em = $this->getDoctrine()->getManager();
            $shopRepository = $em ->getRepository('ShopBundle:Manufacturer');
            $shop = $shopRepository->getByUser($this->getUser());
            $product->setManufacturer($shop);
            $em->persist($product);
            $em->flush();

            return $this->redirectToRoute('show_product', ['slug' => $product->getSlug()]);
        }

        return ['entity' => $product,
            'form' => $form->createView()
        ];
    }

    /**
     * Displays a form to edit an existing Product entity.
     *
     * @Route("/product/{slug}/edit", name="edit_product")
     * @Method({"GET", "POST"})
     * @Template()
     */
    public function editShopProductAction(Request $request, Product $product)
    {
        $deleteForm = $this->createDeleteForm($product);
        $editForm = $this->createForm('Eshop\ShopBundle\Form\Type\ProductType', $product);
        $editForm->handleRequest($request);

        if($product->getManufacturer()->getUser()->getId() != $this->getUser()->getId()){
            return $this->redirectToRoute('index-shop');
        }

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            //update uploaded images entities
            $imageIdString = $request->request->get('filenames');
            if ($imageIdString != '') {
                $imageIdArray = explode(',', $imageIdString);
                array_pop($imageIdArray);

                $em = $this->getDoctrine()->getManager();
                $imageRepository = $em->getRepository('ShopBundle:Image');
                foreach ($imageIdArray as $imageId) {
                    $image = $imageRepository->find($imageId);
                    $image->setProduct($product);
                    $product->addImage($image);
                    $em->persist($image);
                }
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($product);
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice',
                'Your changes were saved!'
            );

            return $this->redirectToRoute('show_product', ['slug' => $product->getSlug()]);
        }

        return ['product' => $product,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView()
        ];
    }

    /**
     * Deletes a Manufacturer entity.
     *
     * @Route("/{id}", name="delete_shop_product")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Product $product)
    {
        $form = $this->createDeleteForm($product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($product);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('index-shop'));
    }

    /**
     * Creates a form to delete a Manufacturer entity by id.
     *
     *
     * @param Product $product
     * @return FormInterface
     */
    private function createDeleteForm(Product $product)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('delete_shop_product', ['id' => $product->getId()]))
            ->setMethod('DELETE')
            ->getForm();
    }
}