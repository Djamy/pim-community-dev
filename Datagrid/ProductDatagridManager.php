<?php
namespace Pim\Bundle\ProductBundle\Datagrid;

use Pim\Bundle\ConfigBundle\Manager\LocaleManager;

use Oro\Bundle\GridBundle\Datagrid\ParametersInterface;

use Oro\Bundle\FlexibleEntityBundle\Manager\FlexibleManager;

use Oro\Bundle\GridBundle\Property\FieldProperty;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Oro\Bundle\GridBundle\Datagrid\FlexibleDatagridManager;
use Oro\Bundle\GridBundle\Field\FieldDescription;
use Oro\Bundle\GridBundle\Field\FieldDescriptionCollection;
use Oro\Bundle\GridBundle\Field\FieldDescriptionInterface;
use Oro\Bundle\GridBundle\Filter\FilterInterface;
use Oro\Bundle\GridBundle\Action\ActionInterface;
use Oro\Bundle\GridBundle\Property\UrlProperty;
use Oro\Bundle\FlexibleEntityBundle\AttributeType\AbstractAttributeType;

/**
 * Grid manager
 *
 * @author    Nicolas Dupont <nicolas@akeneo.com>
 * @copyright 2013 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */
class ProductDatagridManager extends FlexibleDatagridManager
{

    /**
     * @var LocaleManager
     */
    protected $localeManager;

    /**
     * get properties
     * @return array
     */
    protected function getProperties()
    {
        $fieldId = new FieldDescription();
        $fieldId->setName('id');
        $fieldId->setOptions(
            array(
                'type'     => FieldDescriptionInterface::TYPE_INTEGER,
                'required' => true,
            )
        );

        return array(
            new FieldProperty($fieldId),
            new UrlProperty(
                'edit_link',
                $this->router,
                'pim_product_product_edit',
                array('id', 'dataLocale' => 'locale')
            ),
            new UrlProperty('delete_link', $this->router, 'pim_product_product_remove', array('id')),
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function configureFields(FieldDescriptionCollection $fieldsCollection)
    {
        $fieldSku = new FieldDescription();
        $fieldSku->setName('sku');
        $fieldSku->setOptions(
            array(
                'type'        => FieldDescriptionInterface::TYPE_TEXT,
                'label'       => $this->translator->trans('Sku'),
                'field_name'  => 'sku',
                'filter_type' => FilterInterface::TYPE_STRING,
                'required'    => false,
                'sortable'    => true,
                'filterable'  => true,
                'show_filter' => true,
            )
        );
        $fieldsCollection->add($fieldSku);

        // TODO : until we'll have related backend type in grid bundle
        $excludedBackend = array(
            AbstractAttributeType::BACKEND_TYPE_MEDIA,
            AbstractAttributeType::BACKEND_TYPE_METRIC,
            AbstractAttributeType::BACKEND_TYPE_PRICE,
            'prices'
        );

        foreach ($this->getFlexibleAttributes() as $attribute) {

            $backendType   = $attribute->getBackendType();
            if (in_array($backendType, $excludedBackend)) {
                continue;
            }

            if (!$attribute->getUseableAsGridColumn()) {
                continue;
            }

            $attributeType = $this->convertFlexibleTypeToFieldType($backendType);
            $filterType    = $this->convertFlexibleTypeToFilterType($backendType);

            $field = new FieldDescription();
            $field->setName($attribute->getCode());
            $field->setOptions(
                array(
                    'type'          => $attributeType,
                    'label'         => $attribute->getLabel(),
                    'field_name'    => $attribute->getCode(),
                    'filter_type'   => $filterType,
                    'required'      => false,
                    'sortable'      => true,
                    'filterable'    => $attribute->getUseableAsGridFilter(),
                    'flexible_name' => $this->flexibleManager->getFlexibleName(),
                    'show_filter'   => $attribute->getUseableAsGridFilter()
                )
            );

            if ($attributeType == FieldDescriptionInterface::TYPE_OPTIONS) {
                $field->setOption('multiple', true);
            }

            if (!$attribute->getUseableAsGridFilter()) {
                $field->setOption('filter_type', false);
                $field->setOption('filterable', false);
            }

            $fieldsCollection->add($field);
        }

        // add locale filter
        $activeLocaleCodes = $this->localeManager->getActiveCodes();

        $field = new FieldDescription();
        $field->setName('locale');
        $field->setOptions(
            array(
                'type'        => FieldDescriptionInterface::TYPE_OPTIONS,
                'label'       => $this->translator->trans('Data locale'),
                'field_name'  => 'data_locale',
                'filter_type' => FilterInterface::TYPE_CHOICE,
                'required'    => false,
                'sortable'    => true,
                'filterable'  => true,
                'show_filter' => true, //TODO : Must be false
                'field_options' => array(
                    'choices' => array_combine($activeLocaleCodes, $activeLocaleCodes)
                ),
            )
        );

        $fieldsCollection->add($field);

        // add scopable filter
        $field = new FieldDescription();
        $field->setName('scope');
        $field->setOptions(
            array(
                'type'        => FieldDescriptionInterface::TYPE_OPTIONS,
                'label'       => $this->translator->trans('Scope'),
                'field_name'  => 'scope',
                'filter_type' => FilterInterface::TYPE_CHOICE,
                'required'    => false,
                'sortable'    => true,
                'filterable'  => true,
                'show_filter' => true, //TODO : Must be false
                'field_options' => array(
                    'choices' => array('ecommerce', 'mobile')
                ),
            )
        );

        $fieldsCollection->add($field);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRowActions()
    {
        $clickAction = array(
            'name'         => 'rowClick',
            'type'         => ActionInterface::TYPE_REDIRECT,
            'acl_resource' => 'root',
            'options'      => array(
                'label'         => $this->translator->trans('Edit'),
                'icon'          => 'edit',
                'link'          => 'edit_link',
                'backUrl'       => true,
                'runOnRowClick' => true
            )
        );

        $editAction = array(
            'name'         => 'edit',
            'type'         => ActionInterface::TYPE_REDIRECT,
            'acl_resource' => 'root',
            'options'      => array(
                'label'   => $this->translator->trans('Edit'),
                'icon'    => 'edit',
                'link'    => 'edit_link',
                'backUrl' => true
            )
        );

        $deleteAction = array(
            'name'         => 'delete',
            'type'         => ActionInterface::TYPE_DELETE,
            'acl_resource' => 'root',
            'options'      => array(
                'label'   => $this->translator->trans('Delete'),
                'icon'    => 'trash',
                'link'    => 'delete_link'
            )
        );

        return array($clickAction, $editAction, $deleteAction);
    }

    /**
     * {@inheritdoc}
     */
    public function setFlexibleManager(FlexibleManager $flexibleManager)
    {
        $this->flexibleManager = $flexibleManager;

        $this->flexibleManager->setLocale($this->getDataLocale());
        $this->flexibleManager->setScope('ecommerce');
    }

    /**
     * Get data locale value from parameters
     *
     * @return string
     */
    public function getDataLocale()
    {
        $filtersArray = $this->parameters->get(ParametersInterface::FILTER_PARAMETERS);
        if (isset($filtersArray['Locale']) && isset($filtersArray['Locale']['value'])) {
            $dataLocale = $filtersArray['Locale']['value'];
        } else {
            $dataLocale = $this->flexibleManager->getLocale();
        }

        return $dataLocale;
    }

    /**
     * Set locale manager
     *
     * @param LocaleManager $localeManager
     *
     * @return \Pim\Bundle\ProductBundle\Datagrid\ProductDatagridManager
     */
    public function setLocaleManager(LocaleManager $localeManager)
    {
        $this->localeManager = $localeManager;

        return $this;
    }
}
