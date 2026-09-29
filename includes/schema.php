<?php
/*
 * Describes every editable part of the site. The admin editor builds its forms
 * from this, and saved content is sanitized against it.
 *
 * Field types: text, textarea, email, link, color, number, checkbox,
 *              lines (one entry per line), list (repeatable group of fields).
 * Sections with 'toggle' => true get a "Show this section" checkbox.
 */

return [
    'settings' => [
        'label' => 'Site settings',
        'icon' => '⚙️',
        'fields' => [
            'site_name' => ['type' => 'text', 'label' => 'Site name', 'max' => 60],
            'page_title' => ['type' => 'text', 'label' => 'Browser tab title', 'max' => 120],
            'meta_description' => ['type' => 'textarea', 'label' => 'Search engine description', 'max' => 300, 'rows' => 2],
            'primary_color' => ['type' => 'color', 'label' => 'Primary color', 'default' => '#5b5bf6'],
            'accent_color' => ['type' => 'color', 'label' => 'Accent color', 'default' => '#14b8a6'],
            'currency' => ['type' => 'text', 'label' => 'Currency symbol', 'max' => 5],
            'footer_tagline' => ['type' => 'text', 'label' => 'Footer tagline'],
            'copyright_name' => ['type' => 'text', 'label' => 'Copyright holder'],
            'notify_email' => ['type' => 'email', 'label' => 'Email new messages to', 'help' => 'Optional. Requires PHP mail() to be configured on your host.'],
        ],
    ],

    'hero' => [
        'label' => 'Hero',
        'icon' => '✨',
        'fields' => [
            'eyebrow' => ['type' => 'text', 'label' => 'Small label above headline'],
            'title' => ['type' => 'text', 'label' => 'Headline'],
            'title_highlight' => ['type' => 'text', 'label' => 'Highlighted end of headline', 'help' => 'Shown in the gradient color after the headline.'],
            'lead' => ['type' => 'textarea', 'label' => 'Intro paragraph', 'rows' => 3],
            'primary_label' => ['type' => 'text', 'label' => 'Main button text'],
            'primary_link' => ['type' => 'link', 'label' => 'Main button link'],
            'secondary_label' => ['type' => 'text', 'label' => 'Second button text'],
            'secondary_link' => ['type' => 'link', 'label' => 'Second button link'],
            'fine_print' => ['type' => 'text', 'label' => 'Fine print'],
        ],
    ],

    'logos' => [
        'label' => 'Logo strip',
        'icon' => '🏷️',
        'toggle' => true,
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'names' => ['type' => 'lines', 'label' => 'Company names', 'help' => 'One per line.', 'max' => 12],
        ],
    ],

    'features' => [
        'label' => 'Features',
        'icon' => '⚡',
        'toggle' => true,
        'fields' => [
            'nav_label' => ['type' => 'text', 'label' => 'Menu label', 'help' => 'Leave blank to hide from the menu.'],
            'eyebrow' => ['type' => 'text', 'label' => 'Small label'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'subheading' => ['type' => 'text', 'label' => 'Subheading'],
            'items' => ['type' => 'list', 'label' => 'Features', 'item_label' => 'feature', 'max' => 12, 'fields' => [
                'icon' => ['type' => 'text', 'label' => 'Icon (emoji)', 'max' => 8],
                'title' => ['type' => 'text', 'label' => 'Title'],
                'text' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 2, 'max' => 400],
            ]],
        ],
    ],

    'about' => [
        'label' => 'About',
        'icon' => '👋',
        'toggle' => true,
        'fields' => [
            'nav_label' => ['type' => 'text', 'label' => 'Menu label', 'help' => 'Leave blank to hide from the menu.'],
            'eyebrow' => ['type' => 'text', 'label' => 'Small label'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'body' => ['type' => 'textarea', 'label' => 'Text', 'rows' => 4],
            'checklist' => ['type' => 'lines', 'label' => 'Checklist', 'help' => 'One item per line.', 'max' => 8],
            'stats' => ['type' => 'list', 'label' => 'Stats', 'item_label' => 'stat', 'max' => 6, 'fields' => [
                'value' => ['type' => 'number', 'label' => 'Number', 'max' => 12, 'help' => 'Decimals are kept, e.g. 99.9'],
                'suffix' => ['type' => 'text', 'label' => 'Suffix (e.g. +, %)', 'max' => 6],
                'label' => ['type' => 'text', 'label' => 'Label'],
            ]],
        ],
    ],

    'testimonials' => [
        'label' => 'Testimonials',
        'icon' => '💬',
        'toggle' => true,
        'fields' => [
            'nav_label' => ['type' => 'text', 'label' => 'Menu label', 'help' => 'Leave blank to hide from the menu.'],
            'eyebrow' => ['type' => 'text', 'label' => 'Small label'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'items' => ['type' => 'list', 'label' => 'Testimonials', 'item_label' => 'testimonial', 'max' => 12, 'fields' => [
                'quote' => ['type' => 'textarea', 'label' => 'Quote', 'rows' => 3, 'max' => 600],
                'name' => ['type' => 'text', 'label' => 'Name'],
                'role' => ['type' => 'text', 'label' => 'Role & company'],
            ]],
        ],
    ],

    'pricing' => [
        'label' => 'Pricing',
        'icon' => '💳',
        'toggle' => true,
        'fields' => [
            'nav_label' => ['type' => 'text', 'label' => 'Menu label', 'help' => 'Leave blank to hide from the menu.'],
            'eyebrow' => ['type' => 'text', 'label' => 'Small label'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'yearly_badge' => ['type' => 'text', 'label' => 'Yearly discount badge', 'max' => 30],
            'plans' => ['type' => 'list', 'label' => 'Plans', 'item_label' => 'plan', 'max' => 4, 'fields' => [
                'name' => ['type' => 'text', 'label' => 'Plan name'],
                'description' => ['type' => 'text', 'label' => 'Short description'],
                'monthly' => ['type' => 'number', 'label' => 'Monthly price', 'max' => 10],
                'yearly' => ['type' => 'number', 'label' => 'Monthly price when billed yearly', 'max' => 10],
                'features' => ['type' => 'lines', 'label' => 'Included', 'help' => 'One per line.', 'max' => 12],
                'cta_label' => ['type' => 'text', 'label' => 'Button text'],
                'cta_link' => ['type' => 'link', 'label' => 'Button link'],
                'featured' => ['type' => 'checkbox', 'label' => 'Highlight this plan'],
                'tag' => ['type' => 'text', 'label' => 'Highlight tag', 'max' => 30],
            ]],
        ],
    ],

    'faq' => [
        'label' => 'FAQ',
        'icon' => '❓',
        'toggle' => true,
        'fields' => [
            'nav_label' => ['type' => 'text', 'label' => 'Menu label', 'help' => 'Leave blank to hide from the menu.'],
            'eyebrow' => ['type' => 'text', 'label' => 'Small label'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'items' => ['type' => 'list', 'label' => 'Questions', 'item_label' => 'question', 'max' => 30, 'fields' => [
                'question' => ['type' => 'text', 'label' => 'Question'],
                'answer' => ['type' => 'textarea', 'label' => 'Answer', 'rows' => 3],
            ]],
        ],
    ],

    'contact' => [
        'label' => 'Contact',
        'icon' => '✉️',
        'toggle' => true,
        'fields' => [
            'nav_label' => ['type' => 'text', 'label' => 'Menu button label', 'help' => 'Leave blank to hide the header button.'],
            'eyebrow' => ['type' => 'text', 'label' => 'Small label'],
            'heading' => ['type' => 'text', 'label' => 'Heading'],
            'body' => ['type' => 'textarea', 'label' => 'Text', 'rows' => 3],
            'email' => ['type' => 'text', 'label' => 'Displayed email'],
            'phone' => ['type' => 'text', 'label' => 'Displayed phone'],
            'address' => ['type' => 'text', 'label' => 'Displayed address'],
            'button_label' => ['type' => 'text', 'label' => 'Form button text'],
            'success_message' => ['type' => 'text', 'label' => 'Message shown after sending'],
        ],
    ],

    'newsletter' => [
        'label' => 'Newsletter',
        'icon' => '📰',
        'toggle' => true,
        'fields' => [
            'heading' => ['type' => 'text', 'label' => 'Footer heading'],
            'placeholder' => ['type' => 'text', 'label' => 'Email placeholder'],
            'button_label' => ['type' => 'text', 'label' => 'Button text'],
        ],
    ],
];
