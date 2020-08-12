<?php

namespace Eshop\ShopBundle\Controller;

use Doctrine\ORM\EntityManager;
use Eshop\ShopBundle\Entity\Category;
use Eshop\ShopBundle\Entity\Manufacturer;
use Eshop\ShopBundle\Entity\Product;
use Eshop\ShopBundle\Entity\StaticPage;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class CatalogController extends Controller
{
    /**
     * Lists all Category entities.
     *
     * @Route("/", name="index_main")
     * @Method("GET")
     * @Template()
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $productRepository = $em->getRepository('ShopBundle:Product');

        //sorted by order number
        $latestProducts = $productRepository->getLatest(12, $this->getUser());
        $featuredProducts = $productRepository->getFeatured(12, $this->getUser());

        return ['featured_products' => $featuredProducts,
                'latest_products' => $latestProducts,
        ];
    }

    /**
     * @Route("/category/{slug}", name="category")
     * @Method("GET")
     * @Template()
     */
    public function categoryAction(Request $request, Category $category)
    {
        /**
         * @var $em EntityManager
         */
        $em = $this->getDoctrine()->getManager();
        $paginator = $this->get('knp_paginator');
        $productRepository = $em->getRepository('ShopBundle:Product');

        $productsQuery = $productRepository->findByCategoryQB($category, $this->getUser());
        $limit = $this->getParameter('category_products_pagination_count');
        $products = $paginator->paginate(
            $productsQuery,
            $request->query->getInt('page', 1),
            $limit
        );

        return ['category' => $category,
                'products' => $products,
                'sortedby' => $this->get('app.page_utilities')->getSortingParamName($request)
        ];
    }

    /**
     * @Route("/manufacturer/{slug}", name="manufacturer")
     * @Method("GET")
     * @Template()
     */
    public function manufacturerAction(Request $request, Manufacturer $manufacturer)
    {
        /**
         * @var $em EntityManager
         */
        $em = $this->getDoctrine()->getManager();
        $paginator = $this->get('knp_paginator');
        $productRepository = $em->getRepository('ShopBundle:Product');

        $productsQuery = $productRepository->findByManufacturerQB($manufacturer, $this->getUser());
        $limit = $this->getParameter('category_products_pagination_count');
        $products = $paginator->paginate(
            $productsQuery,
            $request->query->getInt('page', 1),
            $limit
        );

        return ['manufacturer' => $manufacturer,
                'products' => $products,
                'sortedby' => $this->get('app.page_utilities')->getSortingParamName($request)
        ];
    }

    /**
     * @Route("/product/{slug}", name="show_product")
     * @Method("GET")
     * @Template()
     */
    public function showProductAction(Product $product)
    {
        $em = $this->getDoctrine()->getManager();

        $manufacturerRepository = $em->getRepository('ShopBundle:Manufacturer');


        return ['product' => $product];
    }

    /**
     * Lists news entities.
     *
     * @Route("/news", name="news")
     * @Method("GET")
     * @Template()
     */
    public function newsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $paginator = $this->get('knp_paginator');
        $newsRepository = $em->getRepository('ShopBundle:News');
        $limit = $this->getParameter('products_pagination_count');

        $query = $newsRepository->getNewsQB();

        $news = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            $limit
        );

        return ['news' => $news];
    }

    /**
     * search product by title or description
     *
     * @Route("/search", name="search")
     * @Method("GET")
     * @Template()
     */
    public function searchProductAction(Request $request)
    {
        $searchResults = [];

        $em = $this->getDoctrine()->getManager();
        $paginator = $this->get('knp_paginator');
        $productRepository = $em->getRepository('ShopBundle:Product');

        $search_phrase = 'search';
        if ($request->getMethod() == 'GET') {
            $search_phrase = trim($request->get('search_phrase'));
            $searchWords = explode(' ', $search_phrase);

            $qb = $productRepository->getSearchQB($searchWords, $this->getUser());

            $limit = $this->getParameter('search_pagination_count');
            $searchResults = $paginator->paginate(
                $qb,
                $request->query->getInt('page', 1),
                $limit
            );
        }
        return ['products' => $searchResults,
                'search_phrase' => $search_phrase,
                'sortedby' => $this->get('app.page_utilities')->getSortingParamName($request)
        ];
    }

    /**
     * Shows static page.
     *
     * @Route("/{slug}.html",name="show_static_page")
     * @Method("GET")
     * @Template()
     */
    public function showStaticPageAction(StaticPage $page)
    {
        return ['page' => $page];
    }

    /**
     *
     * @Route("/categories",name="list_categories")
     * @Method("GET")
     * @Template()
     */
    public function listCategoriesAction()
    {
        $em = $this->getDoctrine()->getManager();
        $categoryRepository = $em->getRepository('ShopBundle:Category');

        $settings = $this->get('app.site_settings');
        $showEmpty = $settings->getShowEmptyCategories();

        $categories = $categoryRepository->getAllCategories($showEmpty);
        return ['categories' => $categories];
    }

    /**
     *
     * @Route("/shops",name="list_shops")
     * @Method("GET")
     * @Template()
     */
    public function listShopsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $manufacturerRepository = $em->getRepository('ShopBundle:Manufacturer');

        $settings = $this->get('app.site_settings');
        $showEmpty = $settings->getShowEmptyManufacturers();

        $manufacturers = $manufacturerRepository->getAllManufacturers($showEmpty);

        return ['shops' => $manufacturers];
    }
}
