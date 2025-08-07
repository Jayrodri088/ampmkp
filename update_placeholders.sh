#!/bin/bash

# List of PHP files with placeholder images
FILES=(
    "index.php"
    "404.php"
    "product.php"
    "about.php"
    "order-success.php"
    "cart.php"
    "search.php"
    "checkout.php"
    "category.php"
    "shop.php"
)

# Update each file
for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        # Make a backup of the original file
        cp "$file" "${file}.bak"
        
        # Update the onerror handler to prevent infinite loops
        perl -i -pe 's/onerror="this\.src=\'\/ampmkp\/assets\/images\/general\/placeholder\.jpg\'"/onerror="this.onerror=null;this.src=\'\/ampmkp\/assets\/images\/general\/placeholder.jpg\'"/g' "$file"
        
        echo "Updated: $file"
    else
        echo "File not found: $file"
    fi
done

echo "All files have been updated. Original files have been backed up with .bak extension."
