<?php

namespace AppBundle\Doctrine\EventSubscriber;

use AppBundle\Entity\Sylius\Product;
use AppBundle\Entity\Sylius\ProductTaxon;
use AppBundle\Sylius\Product\ProductInterface;
use AppBundle\Sylius\Product\ProductOptionInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Gedmo\SoftDeleteable\SoftDeleteableListener;

class PostSoftDeleteSubscriber implements EventSubscriber
{
    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            SoftDeleteableListener::POST_SOFT_DELETE,
        );
    }

    public function postSoftDelete(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $objectManager = $args->getObjectManager();
        $unitOfWork = $objectManager->getUnitOfWork();

        if ($entity instanceof ProductInterface) {

            $productTaxons = $objectManager->getRepository(ProductTaxon::class)->findByProduct($entity);

            foreach ($productTaxons as $productTaxon) {
                $unitOfWork->scheduleForDelete($productTaxon);
            }

            $unitOfWork->computeChangeSets();
        }

        if ($entity instanceof ProductOptionInterface) {

            // FIXME Use ProductInterface
            $productRepository = $objectManager->getRepository(Product::class);

            $products = $productRepository->findByOption($entity);
            foreach ($products as $product) {
                $product->removeOption($entity);
            }

            $unitOfWork->computeChangeSets();
        }
    }
}
