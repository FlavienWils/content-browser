<?php

namespace Netgen\Bundle\ContentBrowserBundle\Form\Type;

use Netgen\Bundle\ContentBrowserBundle\Exceptions\NotFoundException;
use Netgen\Bundle\ContentBrowserBundle\Item\ItemRepositoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContentBrowserType extends AbstractType
{
    /**
     * @var \Netgen\Bundle\ContentBrowserBundle\Item\ItemRepositoryInterface
     */
    protected $itemRepository;

    /**
     * Configures the options for this type.
     *
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(array('item_type', 'config_name'));

        $resolver->setAllowedTypes('item_type', 'string');
        $resolver->setAllowedTypes('config_name', 'string');

        $resolver->setDefault('config_name', function (Options $options) {
            return $options['item_type'];
        });
    }

    /**
     * Constructor.
     *
     * @param \Netgen\Bundle\ContentBrowserBundle\Item\ItemRepositoryInterface $itemRepository
     */
    public function __construct(ItemRepositoryInterface $itemRepository)
    {
        $this->itemRepository = $itemRepository;
    }

    /**
     * Builds the form view.
     *
     * @param \Symfony\Component\Form\FormView $view
     * @param \Symfony\Component\Form\FormInterface $form
     * @param array $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $itemNames = array();
        if ($form->getData() !== null) {
            $itemNames = $this->getItemNames($form->getData(), $options['item_type']);
        }

        $view->vars = array(
            'item_type' => $options['item_type'],
            'config_name' => $options['config_name'],
            'item_names' => $itemNames,
        ) + $view->vars;
    }

    /**
     * Returns the prefix of the template block name for this type.
     *
     * The block prefixes default to the underscored short class name with
     * the "Type" suffix removed (e.g. "UserProfileType" => "user_profile").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return 'ng_content_browser';
    }

    /**
     * Returns the array of names for all items in the form.
     *
     * @param mixed $formData
     * @param string $itemType
     *
     * @return array
     */
    protected function getItemNames($formData, $itemType)
    {
        $itemNames = array();

        foreach ((array)$formData as $itemId) {
            $itemName = null;
            try {
                $itemName = $this->itemRepository->loadItem(
                    $itemId,
                    $itemType
                )->getName();
            } catch (NotFoundException $e) {
                // Do nothing
            }

            $itemNames[$itemId] = $itemName;
        }

        return $itemNames;
    }
}
