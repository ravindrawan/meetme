<?php
/**
 * Navbar Configuration
 * This file allows you to easily change the theme and appearance of the top navigation bar.
 */

return [
    // Background Color Class (e.g., 'bg-primary', 'bg-dark', 'bg-success', or custom style)
    // You can use Bootstrap classes or your own custom CSS classes.
    'bg_class' => 'bg-primary', 
    
    // Navbar Theme ('navbar-dark' for dark backgrounds, 'navbar-light' for light backgrounds)
    'navbar_theme' => 'navbar-dark',
    
    // Brand Text Color (Optional: Leave null to use default theme color)
    'brand_color' => null,
    
    // Show Office Name in Navbar (true/false)
    'show_office_name' => true,
    
    // Custom Styles (Inline CSS for specific overrides)
    'custom_style' => 'background: linear-gradient(45deg, #007bff, #0056b3);', // Example gradient
];
?>
