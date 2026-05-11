<?php

declare(strict_types=1);

namespace App;

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
 * - **Main** — Layout (column span), Background (type + conditional fields),
 *             Content (component-specific fields), Visibility (hide on phones / desktop).
 * - **Typography** — body text tone + component-specific typography fields
 *                    (colour, size, weight, intra-element padding).
 * - **Items** — only rendered when the component declares a top-level repeater.
 * - **Mobile** — overrides that apply below `md` (768px) only. Always present so
 *                authors know where to look; shows an explanatory message when
 *                a component has no block-level mobile overrides.
 *
 * Components express this via four optional keys on the returned array:
 * `main`, `typography`, `items`, `mobile`. Each is a flat field map of the same
 * shape consumed by {@see self::addField()}. The component file no longer declares
 * its own ACF tabs.
 */
class ComponentRegistry
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
     * @return array<string, array<string, mixed>> Component configurations keyed by layout name.
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getComponent(string $key): ?array
    {
        return $this->components[$key] ?? null;
    }

    /**
     * @throws ComponentException
     */
    public function registerFlexibleContent(): FieldsBuilder
    {
        $components = new FieldsBuilder('page_components');

        $components->addTrueFalse('full_screen_scrolling', [
            'label' => __('Full Screen Scrolling', 'culvers'),
            'instructions' => __(
                'Snap between components like slides on desktop while preserving scroll-hijack behavior ' .
                'inside pinned components.',
                'culvers'
            ),
            'default_value' => 0,
            'ui' => 1,
        ]);

        $flexibleContent = $components->addFlexibleContent('components', [
            'label' => __('Page Components', 'culvers'),
            'instructions' => __('Add and arrange components for this page', 'culvers'),
            'button_label' => __('Add Component', 'culvers')
        ]);

        foreach ($this->components as $componentName => $config) {
            try {
                $this->addComponentLayout($flexibleContent, $componentName, $config);
            } catch (\Throwable $e) {
                $this->logError("Error registering component '{$componentName}': " . $e->getMessage());
            }
        }

        $this->flushLoadErrors();

        $components
            ->setLocation('post_type', '==', 'page')
            ->or('post_type', '==', 'culvers_shop')
            ->or('post_type', '==', 'culvers_eat_drink')
            ->or('post_type', '==', 'culvers_event')
            ->or('post_type', '==', 'culvers_career');

        return $components;
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

        $this->addSectionHeading($layout, $componentName, 'main_layout', __('Layout', 'culvers'));
        $layout->addSelect('component_width', [
            'label' => __('Component grid', 'culvers'),
            'instructions' => __(
                'How many columns this block spans (6–12). Grid gaps separate blocks; inner padding follows design defaults.',
                'culvers'
            ),
            'instructions_placement' => 'field',
            'choices' => \App\Helpers\Grid::getColumnChoices(),
            'default_value' => 12,
            'allow_null' => 0,
            'required' => 0,
        ]);

        $this->addSectionHeading($layout, $componentName, 'main_background', __('Background', 'culvers'));
        $this->addBackgroundFields($layout);

        $mainFields = $this->fieldsForSection($config, 'main');
        if ($mainFields !== []) {
            $contentLabel = isset($config['main_label']) && is_string($config['main_label']) && $config['main_label'] !== ''
                ? $config['main_label']
                : __('Content', 'culvers');
            $this->addSectionHeading($layout, $componentName, 'main_content', $contentLabel);
            $this->emitFields($layout, $componentName, $mainFields);
        }

        $this->addSectionHeading($layout, $componentName, 'main_visibility', __('Visibility', 'culvers'));
        $layout->addField(sprintf('chrome_%s_visibility_help', $componentName), 'message', [
            'label' => '',
            'message' => __(
                '<strong>Phones</strong>: below the <code>md</code> breakpoint (&lt;768px). '
                . '<strong>Tablet + desktop</strong>: from <code>md</code> upward share one band. '
                . 'Mobile content overrides live on the <em>Mobile</em> tab.',
                'culvers'
            ),
            'esc_html' => 0,
            'wrapper' => ['class' => 'culvers-acf-help'],
        ]);
        $layout->addTrueFalse('visibility_hide_phone', [
            'label' => __('Hide on phones', 'culvers'),
            'instructions' => __('Below the md breakpoint (&lt;768px). Block stays visible from md upward.', 'culvers'),
            'default_value' => 0,
            'ui' => 1,
            'wrapper' => ['width' => '50'],
        ]);
        $layout->addTrueFalse('visibility_hide_desktop', [
            'label' => __('Hide from tablet / desktop up', 'culvers'),
            'instructions' => __('From md breakpoint upward (768px+). Phones still see the block.', 'culvers'),
            'default_value' => 0,
            'ui' => 1,
            'wrapper' => ['width' => '50'],
        ]);
    }

    private function addBackgroundFields(FieldsBuilder $layout): void
    {
        $layout->addSelect('background_type', [
            'label' => __('Background type', 'culvers'),
            'instructions' => __(
                'Surface behind this block. Related fields appear below when you pick something other than None.',
                'culvers'
            ),
            'instructions_placement' => 'field',
            'choices' => [
                ComponentTypes::BACKGROUND_NONE => __('None', 'culvers'),
                ComponentTypes::BACKGROUND_COLOR => __('Color', 'culvers'),
                ComponentTypes::BACKGROUND_GRADIENT => __('Gradient', 'culvers'),
                ComponentTypes::BACKGROUND_IMAGE => __('Image', 'culvers'),
                ComponentTypes::BACKGROUND_IMAGE_CENTERED => __('Centered Image Card', 'culvers'),
                ComponentTypes::BACKGROUND_VIDEO => __('Video', 'culvers'),
            ],
            'default_value' => ComponentTypes::BACKGROUND_NONE,
            'return_format' => 'value',
        ]);

        $layout->addColorPicker('background_color', $this->getColorPickerOptions([
            'label' => __('Background colour', 'culvers'),
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_COLOR]),
        ]));

        $layout->addColorPicker('background_gradient_color_from', $this->getColorPickerOptions([
            'label' => __('Gradient start colour', 'culvers'),
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_GRADIENT]),
            'wrapper' => ['width' => '33'],
        ]));
        $layout->addColorPicker('background_gradient_color_to', $this->getColorPickerOptions([
            'label' => __('Gradient end colour', 'culvers'),
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_GRADIENT]),
            'wrapper' => ['width' => '33'],
        ]));
        $layout->addSelect('background_gradient_angle', [
            'label' => __('Gradient direction', 'culvers'),
            'instructions' => __('0° = left to right; 90° = bottom to top.', 'culvers'),
            'choices' => [
                '0' => __('0° (left → right)', 'culvers'),
                '45' => __('45° (bottom-left → top-right)', 'culvers'),
                '90' => __('90° (bottom → top)', 'culvers'),
                '135' => __('135° (bottom-right → top-left)', 'culvers'),
                '180' => __('180° (right → left)', 'culvers'),
                '225' => __('225° (top-right → bottom-left)', 'culvers'),
                '270' => __('270° (top → bottom)', 'culvers'),
                '315' => __('315° (top-left → bottom-right)', 'culvers'),
            ],
            'default_value' => '90',
            'allow_null' => 0,
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_GRADIENT]),
            'wrapper' => ['width' => '34'],
        ]);

        $layout->addImage('background_image', [
            'label' => __('Background image', 'culvers'),
            'return_format' => 'array',
            'conditional_logic' => $this->bgWhen([
                ComponentTypes::BACKGROUND_IMAGE,
                ComponentTypes::BACKGROUND_IMAGE_CENTERED,
            ]),
        ]);
        $layout->addColorPicker('background_image_color', $this->getColorPickerOptions([
            'label' => __('Card colour', 'culvers'),
            'instructions' => __('Background behind the centred image card.', 'culvers'),
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_IMAGE_CENTERED]),
        ]));
        $layout->addTrueFalse('background_parallax', [
            'label' => __('Background parallax', 'culvers'),
            'instructions' => __('Subtle scroll parallax on the background image (desktop only).', 'culvers'),
            'default_value' => 1,
            'ui' => 1,
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_IMAGE]),
        ]);

        $layout->addFile('background_video', [
            'label' => __('Background video file', 'culvers'),
            'instructions' => __('MP4 or WebM; or use YouTube below.', 'culvers'),
            'return_format' => 'array',
            'mime_types' => 'mp4,webm',
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_VIDEO]),
        ]);
        $layout->addText('background_video_youtube_url', [
            'label' => __('Background YouTube URL / embed', 'culvers'),
            'instructions' => __('Used when no file is selected.', 'culvers'),
            'placeholder' => 'https://www.youtube.com/watch?v=...',
            'required' => 0,
            'conditional_logic' => $this->bgWhen([ComponentTypes::BACKGROUND_VIDEO]),
        ]);

        $layout->addColorPicker('background_overlay', $this->getColorPickerOptions([
            'label' => __('Background overlay', 'culvers'),
            'instructions' => __('Flat overlay on image/video; opacity defaults to 30%.', 'culvers'),
            'enable_opacity' => true,
            'default_value' => '',
            'conditional_logic' => $this->bgWhen([
                ComponentTypes::BACKGROUND_IMAGE,
                ComponentTypes::BACKGROUND_IMAGE_CENTERED,
                ComponentTypes::BACKGROUND_VIDEO,
            ]),
        ]));
        $layout->addNumber('background_overlay_opacity', [
            'label' => __('Overlay opacity (%)', 'culvers'),
            'instructions' => __('When the overlay colour has no alpha, use 0–100 (default 30).', 'culvers'),
            'default_value' => 30,
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'append' => '%',
            'conditional_logic' => $this->bgWhen([
                ComponentTypes::BACKGROUND_IMAGE,
                ComponentTypes::BACKGROUND_IMAGE_CENTERED,
                ComponentTypes::BACKGROUND_VIDEO,
            ]),
        ]);
    }

    /**
     * Build ACF conditional_logic groups (OR list) so a field appears for any of the listed background types.
     *
     * @param list<string> $values
     * @return list<list<array{field: string, operator: string, value: string}>>
     */
    private function bgWhen(array $values): array
    {
        $groups = [];
        foreach ($values as $value) {
            $groups[] = [[
                'field' => 'background_type',
                'operator' => '==',
                'value' => $value,
            ]];
        }

        return $groups;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function addTypographyTab(FieldsBuilder $layout, string $componentName, array $config): void
    {
        $layout->addTab(__('Typography', 'culvers'));

        $bodyTextToneDefault = match ($componentName) {
            'info_block' => TailwindColors::DEFAULT_LIGHT_BAND_BODY_TEXT_TONE,
            default => TailwindColors::DEFAULT_BODY_TEXT_TONE,
        };

        $this->addSectionHeading($layout, $componentName, 'typography_block', __('Block defaults', 'culvers'));
        $layout->addSelect('body_text_tone', [
            'label' => __('Body text colour', 'culvers'),
            'instructions' => __(
                'Default paragraph / prose colour for this block. Component-specific text styles override this below.',
                'culvers'
            ),
            'instructions_placement' => 'field',
            'choices' => TailwindColors::bodyTextToneChoices(),
            'default_value' => $bodyTextToneDefault,
            'return_format' => 'value',
        ]);

        $typographyFields = $this->fieldsForSection($config, 'typography');
        if ($typographyFields !== []) {
            $this->emitFields($layout, $componentName, $typographyFields);
        }
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
        $layout->addTab(__('Mobile', 'culvers'));

        $mobileFields = $this->fieldsForSection($config, 'mobile');

        if ($mobileFields !== []) {
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

            return;
        }

        $emptyMessage = isset($config['mobile_empty_message']) && is_string($config['mobile_empty_message'])
            ? $config['mobile_empty_message']
            : __(
                'This block renders the same content on mobile and desktop — there are no block-level mobile overrides. '
                . 'If individual rows on the <em>Items</em> tab expose a per-row mobile asset, set those there.',
                'culvers'
            );

        $layout->addField(sprintf('chrome_%s_mobile_empty', $componentName), 'message', [
            'label' => '',
            'message' => $emptyMessage,
            'esc_html' => 0,
            'wrapper' => ['class' => 'culvers-acf-help'],
        ]);
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
     * Pull a section's flat field map out of the component config, supporting
     * the new section schema and the legacy single `fields` key for any not-yet-migrated layout.
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
     * Supports both the new section schema and the legacy `fields` key.
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

        if (isset($config['fields']) && is_array($config['fields'])) {
            foreach ($config['fields'] as $name => $field) {
                if (is_array($field)) {
                    $merged[(string) $name] = $field;
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
