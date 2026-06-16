<?php

declare(strict_types=1);

namespace App;

use App\Config\ComponentPostTypes;
use App\Services\ComponentCache;
use App\Validators\FieldValidator;
use App\Exceptions\ComponentException;
use App\Exceptions\FieldException;
use App\Constants\ComponentTypes;
use App\Helpers\TailwindColors;
use StoutLogic\AcfBuilder\FieldsBuilder;
use StoutLogic\AcfBuilder\FlexibleContentBuilder;

/**
 * Registers `app/Components/*.php` flexible-content layout configs as ACF Flexible
 * Content layouts. Every layout is rendered with the same chrome:
 *
 * - **Main** — Theme-controlled grid/surface note, then **Content** (component fields).
 * - **Typography** — only when a layout declares fields (e.g. hero title colour);
 *                    block body tone is fixed in code per layout.
 * - **Items** — only rendered when the component declares a top-level repeater.
 * - **Mobile** — overrides below `md` (768px) only; tab is registered only when
 *                a layout declares a non-empty `mobile` section.
 *
 * Components express this via four optional keys on the returned array:
 * `main`, `typography`, `items`, `mobile`. Each is a flat field map of the same
 * shape consumed by {@see self::addField()}. The component file no longer declares
 * its own ACF tabs.
 *
 * Section schema is required: there is no legacy top-level `fields` fallback —
 * `app/Components/*.php` files that don't conform are rejected by the validator.
 */
final class ComponentRegistry
{
    private const COMPONENTS_PATH = '/app/Components/';

    private const SECTION_KEYS = ['main', 'typography', 'items', 'mobile'];

    /** wp_options key where component load/register errors are persisted for the admin notice. */
    private const LOAD_ERRORS_OPTION = 'culvers_component_load_errors';

    /** @var array<string, array<string, mixed>> Registered components */
    private array $components = [];

    /** @var list<string> Errors captured during the current request to be persisted for the admin notice. */
    private array $loadErrors = [];

    /** @var ComponentCache Component cache service */
    private ComponentCache $cache;

    /** @var FieldValidator Field validator service */
    private FieldValidator $validator;

    public function __construct()
    {
        $this->cache = new ComponentCache();
        $this->validator = new FieldValidator();
        $this->loadComponents();
        self::registerAdminNoticeOnce();
    }

    private function loadComponents(): void
    {
        $cached = $this->cache->get();
        if ($cached !== null) {
            $this->components = $cached;
            return;
        }

        $this->loadFromFiles();

        if (! empty($this->components)) {
            $this->cache->set($this->components);
        }

        $this->flushLoadErrors();
    }

    /**
     * @throws ComponentException
     */
    private function loadFromFiles(): void
    {
        $componentsPath = get_template_directory() . self::COMPONENTS_PATH;
        if (! is_dir($componentsPath)) {
            return;
        }

        $files = glob($componentsPath . '*.php');
        if (! $files) {
            return;
        }

        $realComponentsPath = realpath($componentsPath);
        if (! $realComponentsPath) {
            return;
        }

        foreach ($files as $file) {
            try {
                $realPath = realpath($file);
                if (! $realPath || ! str_starts_with($realPath, $realComponentsPath)) {
                    $this->logError("Invalid component file path: {$file}");
                    continue;
                }

                $componentName = basename($file, '.php');
                $config = include $file;

                if (! is_array($config)) {
                    throw ComponentException::invalid(
                        $componentName,
                        ['component file must return an array, got ' . get_debug_type($config)]
                    );
                }

                $merged = $this->collectFields($config);
                if ($merged === []) {
                    continue;
                }

                $errors = $this->validator->validateComponent($merged);
                if (! empty($errors)) {
                    throw ComponentException::invalid($componentName, $errors);
                }

                $this->components[$componentName] = $config;
            } catch (\Exception $e) {
                $this->logError("Error loading component from {$file}: " . $e->getMessage());
            }
        }
    }

    /**
     * Register one ACF field group per post-type allowlist ({@see ComponentPostTypes}).
     *
     * @return list<FieldsBuilder>
     */
    public function registerFlexibleContentGroups(): array
    {
        $builders = [];

        foreach (ComponentPostTypes::fieldGroupDefinitions() as $definition) {
            $builders[] = $this->buildFlexibleContentGroup(
                (string) $definition['group_key'],
                $definition['post_types'],
                $definition['layouts'],
            );
        }

        $this->assertAllComponentsAssigned();
        $this->flushLoadErrors();

        return $builders;
    }

    /**
     * @param list<string> $postTypes
     * @param list<string> $layoutKeys
     */
    private function buildFlexibleContentGroup(string $groupKey, array $postTypes, array $layoutKeys): FieldsBuilder
    {
        $components = new FieldsBuilder('page_components_' . $groupKey);

        $flexibleContent = $components->addFlexibleContent('components', [
            'label' => __('Page Components', 'culvers'),
            'instructions' => __('Add and arrange components for this page', 'culvers'),
            'button_label' => __('Add Component', 'culvers'),
        ]);

        foreach ($layoutKeys as $componentName) {
            $config = $this->components[$componentName] ?? null;
            if (! is_array($config)) {
                $this->logError("Layout '{$componentName}' is listed for '{$groupKey}' but no component file exists.");
                continue;
            }

            try {
                $this->addComponentLayout($flexibleContent, $componentName, $config);
            } catch (\Throwable $e) {
                $this->logError("Error registering component '{$componentName}': " . $e->getMessage());
            }
        }

        $firstPostType = array_shift($postTypes);
        if (! is_string($firstPostType) || $firstPostType === '') {
            return $components;
        }

        $location = $components->setLocation('post_type', '==', $firstPostType);

        foreach ($postTypes as $postType) {
            $location->or('post_type', '==', $postType);
        }

        return $components;
    }

    private function assertAllComponentsAssigned(): void
    {
        $assigned = array_fill_keys(ComponentPostTypes::allAssignedLayouts(), true);
        $missing = array_diff(array_keys($this->components), array_keys($assigned));

        foreach ($missing as $componentName) {
            $this->logError(
                "Component '{$componentName}' is loaded but missing from " . ComponentPostTypes::class . ' allowlists.'
            );
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addComponentLayout(FlexibleContentBuilder $flexibleContent, string $componentName, array $config): void
    {
        $layout = $flexibleContent->addLayout($componentName, [
            'label' => $config['label'] ?? ucwords(str_replace('_', ' ', $componentName)),
            'display' => $config['display'] ?? ComponentTypes::DISPLAY_BLOCK,
            'collapsed' => $config['collapsed'] ?? '',
        ]);

        $this->addMainTab($layout, $componentName, $config);
        $this->addTypographyTab($layout, $componentName, $config);
        $this->addItemsTab($layout, $componentName, $config);
        $this->addMobileTab($layout, $componentName, $config);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addMainTab(FieldsBuilder $layout, string $componentName, array $config): void
    {
        $layout->addTab(__('Main', 'culvers'));

        $layout->addField(sprintf('chrome_%s_chrome_note', $componentName), 'message', [
            'label' => '',
            'message' => __(
                'Grid span, outer band colour, and default body text colour are fixed in the theme for each '
                . 'block type — edit the content fields below.',
                'culvers'
            ),
            'esc_html' => 0,
            'wrapper' => ['class' => 'culvers-acf-help'],
        ]);

        $mainFields = $this->fieldsForSection($config, 'main');
        if ($mainFields !== []) {
            $contentLabel = isset($config['main_label']) && is_string($config['main_label']) && $config['main_label'] !== ''
                ? $config['main_label']
                : __('Content', 'culvers');
            $this->addSectionHeading($layout, $componentName, 'main_content', $contentLabel);
            $this->emitFields($layout, $componentName, $mainFields);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addTypographyTab(FieldsBuilder $layout, string $componentName, array $config): void
    {
        $typographyFields = $this->fieldsForSection($config, 'typography');
        if ($typographyFields === []) {
            return;
        }

        $layout->addTab(__('Typography', 'culvers'));
        $this->emitFields($layout, $componentName, $typographyFields);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addItemsTab(FieldsBuilder $layout, string $componentName, array $config): void
    {
        $itemsFields = $this->fieldsForSection($config, 'items');
        if ($itemsFields === []) {
            return;
        }

        $layout->addTab(__('Items', 'culvers'));
        $this->emitFields($layout, $componentName, $itemsFields);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addMobileTab(FieldsBuilder $layout, string $componentName, array $config): void
    {
        $mobileFields = $this->fieldsForSection($config, 'mobile');

        if ($mobileFields === []) {
            return;
        }

        $layout->addTab(__('Mobile', 'culvers'));

        $layout->addField(sprintf('chrome_%s_mobile_help', $componentName), 'message', [
            'label' => '',
            'message' => __(
                '<strong>Mobile overrides apply only below the <code>md</code> breakpoint (768px).</strong> '
                . 'Leave any field blank to inherit the desktop value.',
                'culvers'
            ),
            'esc_html' => 0,
            'wrapper' => ['class' => 'culvers-acf-help'],
        ]);
        $this->emitFields($layout, $componentName, $mobileFields);
    }

    /**
     * @param FieldsBuilder|\StoutLogic\AcfBuilder\GroupBuilder $layout
     * @param array<string, array<string, mixed>> $fields
     */
    private function emitFields($layout, string $componentName, array $fields): void
    {
        foreach ($fields as $fieldName => $fieldConfig) {
            $fieldName = (string) $fieldName;
            try {
                $this->addField($layout, $fieldName, $fieldConfig);
            } catch (FieldException $e) {
                $this->logError(
                    "Error adding field '{$fieldName}' to component '{$componentName}': " . $e->getMessage()
                );
            }
        }
    }

    /**
     * Render a section divider as a `message` field with a stable wrapper class for CSS.
     *
     * Each section heading gets a unique field name (`{component}_chrome_{key}`) so
     * AcfBuilder's FieldManager doesn't reject them as collisions. The label is
     * upper-cased at the PHP level so the divider reads as a section break even
     * when admin CSS overrides interfere with `text-transform`.
     */
    private function addSectionHeading(FieldsBuilder $layout, string $componentName, string $key, string $label): void
    {
        $name = sprintf('chrome_%s_%s', $componentName, $key);
        $layout->addField($name, 'message', [
            'label' => '',
            'message' => '<span class="culvers-acf-section-head__label">'
                . esc_html(mb_strtoupper($label))
                . '</span>',
            'esc_html' => 0,
            'wrapper' => ['class' => 'culvers-acf-section-head'],
        ]);
    }

    /**
     * Pull a section's flat field map out of the component config.
     *
     * @param array<string, mixed> $config
     * @return array<string, array<string, mixed>>
     */
    private function fieldsForSection(array $config, string $section): array
    {
        if (isset($config[$section]) && is_array($config[$section])) {
            return $config[$section];
        }

        return [];
    }

    /**
     * Flatten a component config to all field configs for validation purposes.
     *
     * @param array<string, mixed> $config
     * @return array<string, array<string, mixed>>
     */
    private function collectFields(array $config): array
    {
        $merged = [];

        foreach (self::SECTION_KEYS as $section) {
            if (isset($config[$section]) && is_array($config[$section])) {
                foreach ($config[$section] as $name => $field) {
                    if (is_array($field)) {
                        $merged[(string) $name] = $field;
                    }
                }
            }
        }

        return $merged;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function getColorPickerOptions(array $options = []): array
    {
        return array_merge([
            'show_custom_palette' => true,
            'palette_colors' => TailwindColors::getPaletteString(),
        ], $options);
    }

    /**
     * @param FieldsBuilder|\StoutLogic\AcfBuilder\GroupBuilder $layout
     * @param array<string, mixed> $config
     * @throws FieldException
     */
    private function addField($layout, string $fieldName, array $config): void
    {
        $type = $config['type'] ?? 'text';
        $options = $config['options'] ?? [];

        match ($type) {
            'text' => $layout->addText($fieldName, $options),
            'textarea' => $layout->addTextarea($fieldName, $options),
            'number' => $layout->addNumber($fieldName, $options),
            'range' => $layout->addRange($fieldName, $options),
            'wysiwyg' => $layout->addWysiwyg($fieldName, $options),
            'url' => $layout->addUrl($fieldName, $options),
            'email' => $layout->addEmail($fieldName, $options),
            'taxonomy' => $layout->addTaxonomy($fieldName, $options),
            'image' => $layout->addImage($fieldName, array_merge([
                'return_format' => 'array',
                'preview_size' => 'medium'
            ], $options)),
            'select' => $layout->addSelect($fieldName, $options),
            'radio' => $layout->addRadio($fieldName, $options),
            'checkbox' => $layout->addCheckbox($fieldName, $options),
            'button_group' => $layout->addButtonGroup($fieldName, $options),
            'true_false' => $layout->addTrueFalse($fieldName, $options),
            'link' => $layout->addLink($fieldName, array_merge([
                'return_format' => 'array'
            ], $options)),
            'post_object' => $layout->addPostObject($fieldName, array_merge([
                'return_format' => 'object',
            ], $options)),
            'file' => $layout->addFile($fieldName, array_merge([
                'return_format' => 'array'
            ], $options)),
            'gallery' => $layout->addGallery($fieldName, array_merge([
                'return_format' => 'array',
                'preview_size' => 'medium',
            ], $options)),
            'color_picker' => $layout->addColorPicker($fieldName, $this->getColorPickerOptions($options)),
            'repeater' => $this->addRepeaterFields($layout, $fieldName, $options),
            'group' => $this->addGroupFields($layout, $fieldName, $options),
            'tab' => $layout->addTab($options['label'] ?? ucwords(str_replace('_', ' ', $fieldName)), $options),
            'accordion' => $this->addAccordionField($layout, $options['label'] ?? ucwords(str_replace('_', ' ', $fieldName)), $options),
            'message' => $layout->addField($fieldName, 'message', array_merge(['label' => ''], $options)),
            default => throw FieldException::invalidType($fieldName, $type),
        };
    }

    /**
     * @param FieldsBuilder|\StoutLogic\AcfBuilder\GroupBuilder $layout
     * @param array<string, mixed> $options
     */
    private function addRepeaterFields($layout, string $fieldName, array $options): void
    {
        $repeater = $layout->addRepeater($fieldName, $options);
        if (isset($options['sub_fields']) && is_array($options['sub_fields'])) {
            foreach ($options['sub_fields'] as $subFieldName => $subFieldConfig) {
                if (! is_array($subFieldConfig)) {
                    continue;
                }
                $this->addField($repeater, (string) $subFieldName, $subFieldConfig);
            }
        }
    }

    /**
     * @param FieldsBuilder|\StoutLogic\AcfBuilder\GroupBuilder $layout
     * @param array<string, mixed> $options
     */
    private function addGroupFields($layout, string $fieldName, array $options): void
    {
        $group = $layout->addGroup($fieldName, $options);
        if (isset($options['sub_fields']) && is_array($options['sub_fields'])) {
            foreach ($options['sub_fields'] as $subFieldName => $subFieldConfig) {
                if (! is_array($subFieldConfig)) {
                    continue;
                }
                $this->addField($group, (string) $subFieldName, $subFieldConfig);
            }
        }
    }

    /**
     * @param FieldsBuilder|\StoutLogic\AcfBuilder\GroupBuilder $layout
     * @param array<string, mixed> $options
     */
    private function addAccordionField($layout, string $label, array $options): mixed
    {
        $endpoint = ! empty($options['endpoint']);
        $payload = array_diff_key($options, ['label' => true, 'endpoint' => true]);
        $accordion = $layout->addAccordion($label, $payload);
        if ($endpoint) {
            $accordion->endpoint();
        }

        return $layout;
    }

    private function logError(string $message): void
    {
        if (function_exists('error_log')) {
            error_log('[ComponentRegistry] ' . $message);
        }

        $this->loadErrors[] = $message;
    }

    /**
     * Persist this request's collected errors (or clear the option if none). Called at the end of
     * each phase (file load, layout register) so a re-run that succeeds removes the admin notice.
     */
    private function flushLoadErrors(): void
    {
        if (! function_exists('update_option')) {
            return;
        }

        if ($this->loadErrors === []) {
            if (function_exists('get_option') && get_option(self::LOAD_ERRORS_OPTION) !== false) {
                delete_option(self::LOAD_ERRORS_OPTION);
            }

            return;
        }

        $existing = function_exists('get_option') ? get_option(self::LOAD_ERRORS_OPTION, []) : [];
        if (! is_array($existing)) {
            $existing = [];
        }
        $merged = array_values(array_unique(array_merge($existing, $this->loadErrors)));
        update_option(self::LOAD_ERRORS_OPTION, $merged, false);

        $this->loadErrors = [];
    }

    /**
     * Hook the admin notice exactly once per request. Gated on `manage_options` so the warning
     * always reaches site admins — not just when WP_DEBUG happens to be on in production.
     */
    private static function registerAdminNoticeOnce(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        if (! function_exists('add_action')) {
            return;
        }

        add_action('admin_notices', static function (): void {
            if (! function_exists('current_user_can') || ! current_user_can('manage_options')) {
                return;
            }
            $errors = function_exists('get_option') ? get_option(self::LOAD_ERRORS_OPTION, []) : [];
            if (! is_array($errors) || $errors === []) {
                return;
            }
            echo '<div class="notice notice-error"><p><strong>'
                . esc_html__('Culvers component registry — load errors', 'culvers')
                . '</strong></p><ul style="margin-left:1.25em;list-style:disc;">';
            foreach ($errors as $msg) {
                echo '<li>' . esc_html((string) $msg) . '</li>';
            }
            echo '</ul></div>';
        });
    }
}
