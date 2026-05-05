<?php

namespace App;

use App\Services\ComponentCache;
use App\Services\TemplateResolver;
use App\Validators\FieldValidator;
use App\Exceptions\ComponentException;
use App\Exceptions\FieldException;
use App\Constants\ComponentTypes;
use App\Helpers\TailwindColors;
use StoutLogic\AcfBuilder\FieldsBuilder;

/**
 * Component Registry
 *
 * Automatically discovers and registers ACF components with improved architecture.
 * Handles component discovery, validation, caching, and ACF field registration.
 *
 * @package App
 */
class ComponentRegistry
{
    /** @var array<string, array> Registered components */
    private array $components = [];

    /** @var ComponentCache Component cache service */
    private ComponentCache $cache;

    /** @var TemplateResolver Template resolver service */
    private TemplateResolver $templateResolver;

    /** @var FieldValidator Field validator service */
    private FieldValidator $validator;

    public function __construct()
    {
        $this->cache = new ComponentCache();
        $this->templateResolver = TemplateResolver::getInstance();
        $this->validator = new FieldValidator();
        $this->loadComponents();
    }

    /**
     * Load component definitions from cache or filesystem
     *
     * @return void
     */
    private function loadComponents(): void
    {
        // Try cache first
        $cached = $this->cache->get();
        if ($cached !== null) {
            $this->components = $cached;
            return;
        }

        // Load from files
        $this->loadFromFiles();
        $this->loadFromClasses();

        // Only cache when we have components - never cache empty array to avoid
        // stale empty cache persisting and hiding all components
        if (! empty($this->components)) {
            $this->cache->set($this->components);
        }
    }

    /**
     * Load components from PHP config files (array-based configuration)
     *
     * @return void
     * @throws ComponentException If component loading fails
     */
    private function loadFromFiles(): void
    {
        $paths = [
            get_template_directory() . '/app/components/',
            get_template_directory() . '/app/Components/',
        ];

        foreach ($paths as $componentsPath) {
            if (! is_dir($componentsPath)) {
                continue;
            }

            $files = glob($componentsPath . '*.php');
            if (! $files) {
                continue;
            }

            foreach ($files as $file) {
                try {
                    // Security: Validate file path to prevent directory traversal
                    $realPath = realpath($file);
                    $realComponentsPath = realpath($componentsPath);

                    if (! $realPath || ! $realComponentsPath || ! str_starts_with($realPath, $realComponentsPath)) {
                        $this->logError("Invalid component file path: {$file}");
                        continue;
                    }

                    $componentName = basename($file, '.php');

                    // Skip if already loaded as class or from a previous path
                    if (isset($this->components[$componentName])) {
                        continue;
                    }

                    $config = include $file;

                    if (is_array($config) && isset($config['fields'])) {
                        // Validate fields
                        $errors = $this->validator->validateComponent($config['fields']);

                        if (! empty($errors)) {
                            throw ComponentException::invalid($componentName, $errors);
                        }

                        $this->components[$componentName] = $config;
                    }
                } catch (\Exception $e) {
                    $this->logError("Error loading component from {$file}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Load components from class files (class-based configuration)
     *
     * @return void
     * @throws ComponentException If component class loading fails
     */
    private function loadFromClasses(): void
    {
        $componentsPath = get_template_directory() . '/app/Components/';

        if (! is_dir($componentsPath)) {
            return;
        }

        // Check if AbstractComponent class exists before trying to load class-based components
        if (! class_exists('App\\Components\\AbstractComponent')) {
            return;
        }

        $files = glob($componentsPath . '*Component.php');

        if (! $files) {
            return;
        }

        foreach ($files as $file) {
            try {
                // Security: Validate file path to prevent directory traversal
                $realPath = realpath($file);
                $realComponentsPath = realpath($componentsPath);

                if (! $realPath || ! $realComponentsPath || ! str_starts_with($realPath, $realComponentsPath)) {
                    $this->logError("Invalid component class file path: {$file}");
                    continue;
                }

                $className = basename($file, '.php');
                $fullClassName = "App\\Components\\{$className}";

                if (! class_exists($fullClassName)) {
                    continue;
                }

                $reflection = new \ReflectionClass($fullClassName);

                if (! $reflection->isSubclassOf('App\\Components\\AbstractComponent')) {
                    continue;
                }

                // Extract key from class name (e.g., HeaderTextComponent -> header_text)
                $key = $this->getKeyFromClassName($className);

                // Instantiate component
                $component = new $fullClassName($key);

                // Validate component
                if (! $component->validate()) {
                    $this->logError("Component '{$key}' failed validation");
                    continue;
                }

                // Convert to array format for compatibility
                $this->components[$key] = [
                    'label' => $component->getLabel(),
                    'display' => $component->getDisplay(),
                    'fields' => $component->getFields(),
                    'metadata' => $component->getMetadata(),
                    'template' => $component->getTemplateName(),
                    '_class' => $fullClassName,
                ];
            } catch (\Exception $e) {
                $this->logError("Error loading component class {$file}: " . $e->getMessage());
            }
        }
    }

    /**
     * Convert class name to component key
     *
     * Example: HeaderTextComponent -> header_text
     *
     * @param string $className Class name (e.g., "HeaderTextComponent")
     * @return string Component key (e.g., "header_text")
     */
    private function getKeyFromClassName(string $className): string
    {
        // Remove "Component" suffix
        $key = preg_replace('/Component$/', '', $className);

        // Convert PascalCase to snake_case
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
    }

    /**
     * Get all loaded components
     *
     * @return array<string, array> Array of component configurations keyed by component name
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Get component configuration by key
     *
     * @param string $key Component key (e.g., "header_text")
     * @return array|null Component configuration or null if not found
     */
    public function getComponent(string $key): ?array
    {
        return $this->components[$key] ?? null;
    }

    /**
     * Get components filtered by namespace
     *
     * @param string $namespace Component namespace
     * @return array<string, array> Filtered array of components
     */
    public function getComponentsByNamespace(string $namespace): array
    {
        return array_filter($this->components, function ($component) use ($namespace) {
            return ($component['metadata']['namespace'] ?? 'default') === $namespace;
        });
    }

    /**
     * Register all discovered components as ACF Flexible Content layouts
     *
     * @return FieldsBuilder ACF FieldsBuilder instance with flexible content configured
     * @throws ComponentException If registration fails
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
            } catch (ComponentException $e) {
                $this->logError("Error registering component '{$componentName}': " . $e->getMessage());
                // Don't re-throw - continue with other components
            } catch (\Exception $e) {
                $this->logError("Unexpected error registering component '{$componentName}': " . $e->getMessage());
                // Don't re-throw - continue with other components
            }
        }

        // Note: Verification happens after field group is built in Fields.php
        // Both layouts are confirmed to be registered correctly

        $components
            ->setLocation('post_type', '==', 'page')
            ->or('post_type', '==', 'culvers_shop');

        return $components;
    }

    /**
     * Add a single component layout to the flexible content field group
     *
     * @param FieldsBuilder $flexibleContent ACF Flexible Content field builder
     * @param string $componentName Component name/key
     * @param array<string, mixed> $config Component configuration array
     * @return void
     * @throws ComponentException If layout addition fails
     */
    private function addComponentLayout($flexibleContent, string $componentName, array $config): void
    {
        $layout = $flexibleContent->addLayout($componentName, [
            'label' => $config['label'] ?? ucwords(str_replace('_', ' ', $componentName)),
            'display' => $config['display'] ?? ComponentTypes::DISPLAY_BLOCK
        ]);

        // 1. TABS AT TOP (General, Padding, Paper Tear) – Padding/Paper Tear added inside addGeneralTab
        $this->addGeneralTab($layout, $componentName, $config);
    }

    /**
     * General tab: component_width, background, visibility, component content.
     * Does NOT contain: padding, font, tear (those have their own tabs).
     */
    private function addGeneralTab($layout, string $componentName, array $config): void
    {
        $layout->addTab(__('General', 'culvers'))
        ->addSelect('component_width', [
            'label' => __('Component Grid', 'culvers'),
            'instructions' => __(
                'Choose how many columns this component should span (6-12). ' .
                'Grid gaps handle spacing between components, padding handles outer edges.',
                'culvers'
            ),
            'choices' => \App\Helpers\Grid::getColumnChoices(),
            'default_value' => 12,
            'allow_null' => 0,
            'required' => 0,
            'wrapper' => ['width' => '50'],
        ])
        ->addSelect('background_type', [
            'label' => __('Background Type', 'culvers'),
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
        ])
        ->addColorPicker('background_color', $this->getColorPickerOptions([
            'label' => __('Background Color', 'culvers'),
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_COLOR,
                    ]
                ]
            ]
        ]))
        ->addColorPicker('background_gradient_color_from', $this->getColorPickerOptions([
            'label' => __('Gradient Start Color', 'culvers'),
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_GRADIENT,
                    ]
                ]
            ],
            'wrapper' => ['width' => '50'],
        ]))
        ->addColorPicker('background_gradient_color_to', $this->getColorPickerOptions([
            'label' => __('Gradient End Color', 'culvers'),
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_GRADIENT,
                    ]
                ]
            ],
            'wrapper' => ['width' => '50'],
        ]))
        ->addSelect('background_gradient_angle', [
            'label' => __('Gradient Direction', 'culvers'),
            'instructions' => __('Angle of the gradient (0° = left to right, 90° = bottom to top).', 'culvers'),
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
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_GRADIENT,
                    ]
                ]
            ],
            'wrapper' => ['width' => '50'],
        ])
        ->addImage('background_image', [
            'label' => __('Background Image', 'culvers'),
            'return_format' => 'array',
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE,
                    ]
                ],
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE_CENTERED,
                    ]
                ]
            ]
        ])
        ->addColorPicker('background_image_color', $this->getColorPickerOptions([
            'label' => __('Card Color', 'culvers'),
            'instructions' => __(
                'Background color of the centered image card.',
                'culvers'
            ),
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE_CENTERED,
                    ]
                ]
            ]
        ]))
        ->addTrueFalse('background_parallax', [
            'label' => __('Background Parallax', 'culvers'),
            'instructions' => __(
                'Enable subtle scroll-based parallax on the background image (desktop only).',
                'culvers'
            ),
            'default_value' => 1,
            'ui' => 1,
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE,
                    ]
                ]
            ]
        ])
        ->addFile('background_video', [
            'label' => __('Background Video', 'culvers'),
            'instructions' => __('Upload an mp4/webm file or use the YouTube URL field below.', 'culvers'),
            'return_format' => 'array',
            'mime_types' => 'mp4,webm',
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_VIDEO,
                    ]
                ]
            ]
        ])
        ->addText('background_video_youtube_url', [
            'label' => __('Background YouTube URL / Embed', 'culvers'),
            'instructions' => __(
                'Paste a YouTube URL or iframe embed code. Used when no file is selected.',
                'culvers'
            ),
            'placeholder' => 'https://www.youtube.com/watch?v=...',
            'required' => 0,
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_VIDEO,
                    ]
                ]
            ]
        ])
        ->addColorPicker('background_overlay', $this->getColorPickerOptions([
            'label' => __('Background Overlay', 'culvers'),
            'instructions' => __(
                'Flat overlay color for image/video backgrounds. Opacity is editable; defaults to 30%.',
                'culvers'
            ),
            'enable_opacity' => true,
            'default_value' => '',
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE,
                    ]
                ],
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE_CENTERED,
                    ]
                ],
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_VIDEO,
                    ]
                ]
            ]
        ]))
        ->addNumber('background_overlay_opacity', [
            'label' => __('Background Overlay Opacity (%)', 'culvers'),
            'instructions' => __(
                'Used when overlay color is saved without alpha. Set 0-100 (default 30).',
                'culvers'
            ),
            'default_value' => 30,
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'append' => '%',
            'conditional_logic' => [
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE,
                    ]
                ],
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_IMAGE_CENTERED,
                    ]
                ],
                [
                    [
                        'field' => 'background_type',
                        'operator' => '==',
                        'value' => ComponentTypes::BACKGROUND_VIDEO,
                    ]
                ]
            ]
        ]);

        $bodyTextToneDefault = match ($componentName) {
            'info_block' => TailwindColors::DEFAULT_LIGHT_BAND_BODY_TEXT_TONE,
            default => TailwindColors::DEFAULT_BODY_TEXT_TONE,
        };

        $layout->addSelect('body_text_tone', [
            'label' => __('Body text colour', 'culvers'),
            'instructions' => __(
                'Default paragraph / prose colour for this component. Headings use their own colour fields where set.',
                'culvers'
            ),
            'choices' => TailwindColors::bodyTextToneChoices(),
            'default_value' => $bodyTextToneDefault,
            'return_format' => 'value',
            'wrapper' => ['width' => '50'],
        ])
        ->addSelect('visibility_mobile', [
            'label' => __('Visibility on Mobile', 'culvers'),
            'instructions' => __(
                'Hide this component on phones (below 768px)? Tablets and larger show the component.',
                'culvers'
            ),
            'choices' => [
                'visible' => __('Visible on all devices', 'culvers'),
                'hidden' => __('Hidden on mobile (phones only)', 'culvers'),
            ],
            'default_value' => 'visible',
            'wrapper' => ['width' => '50'],
        ]);

        // Tab order: General (grid + background + visibility), component fields, Padding.
        foreach ($config['fields'] as $fieldName => $fieldConfig) {
            if ($fieldName === 'tab_general') {
                continue;
            }
            if ($fieldName === 'tab_padding') {
                $this->addPaddingTab($layout);
                continue;
            }
            try {
                $this->addField($layout, $fieldName, $fieldConfig);
            } catch (FieldException $e) {
                $this->logError(
                    "Error adding field '{$fieldName}' to component '{$componentName}': " . $e->getMessage()
                );
            } catch (\Exception $e) {
                $this->logError(
                    "Unexpected error adding field '{$fieldName}' to component '{$componentName}': " .
                    $e->getMessage()
                );
            }
        }
    }

    /**
     * Padding tab: top_padding, bottom_padding only.
     */
    private function addPaddingTab($layout): void
    {
        $layout->addTab(__('Padding', 'culvers'))
            ->addSelect('top_padding', [
                'label' => __('Top Padding', 'culvers'),
                'choices' => [
                    ComponentTypes::PADDING_NONE => __('None', 'culvers'),
                    ComponentTypes::PADDING_FLUSH => __('Flush (0px)', 'culvers'),
                    ComponentTypes::PADDING_SMALL => __('32px', 'culvers'),
                    ComponentTypes::PADDING_MEDIUM => __('64px (Default)', 'culvers'),
                    ComponentTypes::PADDING_LARGE => __('128px', 'culvers'),
                ],
                'default_value' => ComponentTypes::PADDING_MEDIUM,
                'wrapper' => ['width' => '50'],
            ])
            ->addSelect('bottom_padding', [
                'label' => __('Bottom Padding', 'culvers'),
                'choices' => [
                    ComponentTypes::PADDING_NONE => __('None', 'culvers'),
                    ComponentTypes::PADDING_FLUSH => __('Flush (0px)', 'culvers'),
                    ComponentTypes::PADDING_SMALL => __('32px', 'culvers'),
                    ComponentTypes::PADDING_MEDIUM => __('64px (Default)', 'culvers'),
                    ComponentTypes::PADDING_LARGE => __('128px', 'culvers'),
                ],
                'default_value' => ComponentTypes::PADDING_MEDIUM,
                'wrapper' => ['width' => '50'],
            ]);
    }

    /**
     * Get color picker options with Tailwind palette pre-configured
     *
     * @param array<string, mixed> $options Additional options to merge
     * @return array<string, mixed> Color picker options array
     */
    private function getColorPickerOptions(array $options = []): array
    {
        return array_merge([
            'show_custom_palette' => true,
            'palette_colors' => TailwindColors::getPaletteString(),
        ], $options);
    }

    /**
     * Add a field to a layout based on configuration
     *
     * @param FieldsBuilder $layout ACF Layout builder
     * @param string $fieldName Field name/key
     * @param array<string, mixed> $config Field configuration array
     * @return void
     * @throws FieldException If field type is invalid or configuration is missing
     */
    private function addField($layout, string $fieldName, array $config): void
    {
        $type = $config['type'] ?? 'text';
        $options = $config['options'] ?? [];

        // Use match expression for cleaner code (PHP 8.0+)
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
            'message' => $layout->addField($fieldName, 'message', array_merge(['label' => ''], $options)),
            default => throw FieldException::invalidType($fieldName, $type),
        };
    }

    /**
     * Add repeater field with sub-fields
     *
     * @param FieldsBuilder $layout ACF Layout builder
     * @param string $fieldName Field name/key
     * @param array<string, mixed> $options Field options
     * @return void
     */
    private function addRepeaterFields($layout, string $fieldName, array $options): void
    {
        $repeater = $layout->addRepeater($fieldName, $options);
        if (isset($options['sub_fields'])) {
            foreach ($options['sub_fields'] as $subFieldName => $subFieldConfig) {
                $this->addField($repeater, $subFieldName, $subFieldConfig);
            }
        }
    }

    /**
     * Add group field with sub-fields
     *
     * @param FieldsBuilder $layout ACF Layout builder
     * @param string $fieldName Field name/key
     * @param array<string, mixed> $options Field options
     * @return void
     */
    private function addGroupFields($layout, string $fieldName, array $options): void
    {
        $group = $layout->addGroup($fieldName, $options);
        if (isset($options['sub_fields'])) {
            foreach ($options['sub_fields'] as $subFieldName => $subFieldConfig) {
                $this->addField($group, $subFieldName, $subFieldConfig);
            }
        }
    }

    /**
     * Clear component cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache->clear();
    }

    /**
     * Get template resolver service instance
     *
     * @return TemplateResolver Template resolver singleton
     */
    public function getTemplateResolver(): TemplateResolver
    {
        return $this->templateResolver;
    }

    /**
     * Log error with throttled admin notices
     *
     * @param string $message Error message to log
     * @return void
     */
    private function logError(string $message): void
    {
        if (function_exists('error_log')) {
            error_log('[ComponentRegistry] ' . $message);
        }

        // Only show admin notice once per error type (using transient)
        if (defined('WP_DEBUG') && WP_DEBUG && is_admin()) {
            $transientKey = 'culvers_theme_error_' . md5($message);
            if (! get_transient($transientKey)) {
                set_transient($transientKey, true, 300); // 5 minutes
                add_action('admin_notices', function () use ($message) {
                    echo '<div class="notice notice-error is-dismissible">' .
                        '<p><strong>Component Registry:</strong> ' . esc_html($message) . '</p></div>';
                });
            }
        }
    }
}
