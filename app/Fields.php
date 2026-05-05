<?php

namespace App;

use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Custom Fields
 *
 * This class contains all ACF field group definitions using ACF Builder.
 */
class Fields
{
    private ComponentRegistry $componentRegistry;

    public function __construct()
    {
        $this->componentRegistry = new ComponentRegistry();
        $this->registerFieldGroups();
    }

    /**
     * Register all field groups
     */
    public function registerFieldGroups(): void
    {
        $this->registerComponentFields();
    }

    /**
     * Register component fields (Flexible Content)
     */
    private function registerComponentFields(): void
    {
        $flexibleContent = $this->componentRegistry->registerFlexibleContent();
        acf_add_local_field_group($flexibleContent->build());
        Directory\ShopFields::register();
        Directory\ShopArchiveFields::register();
    }
}
