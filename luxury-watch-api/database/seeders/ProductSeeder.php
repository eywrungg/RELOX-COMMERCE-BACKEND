<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        echo "🚀 Starting Product Seeder...\n\n";

        // Clear existing products - disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        echo "🗑️  Cleared existing products\n\n";

        $products = [];
        $brandFolders = [
            'Audemars Piguet',
            'Breguet',
            'Breitling',
            'Maurice Lacroix',
            'Omega',
            'Patek Philippe',
            'Rado',
            'Relox',  // CHANGED: Rolex → Relox
            'Tissot',
            'Vacheron Constantin'
        ];

        // THE ACTUAL PATH WHERE FILES ARE STORED
        $basePath = storage_path('app/public/watches');
        
        echo "📂 Looking for images in: {$basePath}\n";
        
        if (!File::exists($basePath)) {
            echo "\n❌ ERROR: Base directory not found!\n";
            echo "📁 Please create it and add your brand folders:\n";
            echo "   mkdir -p {$basePath}\n";
            echo "   Then move your brand folders (Relox, Omega, etc.) into it\n\n";
            return;
        }

        echo "✅ Base directory exists\n\n";

        foreach ($brandFolders as $brand) {
            // Check ACTUAL storage location
            $brandPath = $basePath . '/' . $brand;
            
            if (!File::exists($brandPath)) {
                echo "⚠️  Folder not found: {$brand} (skipping)\n";
                continue;
            }

            // Get all image files
            $images = File::files($brandPath);
            
            if (empty($images)) {
                echo "⚠️  No images found in: {$brand}\n";
                continue;
            }

            echo "📸 Processing {$brand}: " . count($images) . " images\n";

            foreach ($images as $image) {
                $filename = $image->getFilename();
                
                // Skip non-image files
                $ext = strtolower($image->getExtension());
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    continue;
                }

                // Generate watch name
                $watchName = $this->generateWatchName($brand, $filename);
                
                // PUBLIC URL (what the API will return and frontend will use)
                $imageUrl = "/storage/watches/{$brand}/{$filename}";

                $products[] = [
                    'name'        => $watchName,
                    'slug'        => Str::slug($watchName . '-' . Str::random(4)),
                    'brand'       => $brand,
                    'description' => $this->generateDescription($brand, $watchName),
                    'price'       => $this->generatePrice($brand),
                    'stock'       => rand(1, 20),
                    'image'       => $imageUrl,
                    'featured'    => rand(0, 1),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        if (empty($products)) {
            echo "\n❌ No products to insert!\n";
            echo "📋 Troubleshooting:\n";
            echo "   1. Check that images are in: {$basePath}/BrandName/\n";
            echo "   2. Verify brand names match exactly (case-sensitive)\n";
            echo "   3. Ensure images are .jpg, .jpeg, .png, or .webp\n\n";
            return;
        }

        // Insert products
        echo "\n💾 Inserting " . count($products) . " products into database...\n";
        
        $chunks = array_chunk($products, 50);
        foreach ($chunks as $chunk) {
            DB::table('products')->insert($chunk);
        }

        echo "✅ Successfully inserted " . count($products) . " products!\n\n";
        
        // Show sample
        $sample = $products[0];
        echo "📋 Sample product:\n";
        echo "   Name: {$sample['name']}\n";
        echo "   Brand: {$sample['brand']}\n";
        echo "   Price: ₱" . number_format($sample['price']) . "\n";
        echo "   Image URL: {$sample['image']}\n\n";
        
        // Show brand summary
        $brandCounts = [];
        foreach ($products as $p) {
            $brandCounts[$p['brand']] = ($brandCounts[$p['brand']] ?? 0) + 1;
        }
        echo "📊 Products by brand:\n";
        foreach ($brandCounts as $brand => $count) {
            echo "   • {$brand}: {$count}\n";
        }
        
        echo "\n🎉 Seeding complete!\n";
    }

    private function generateWatchName(string $brand, string $filename): string
    {
        // Extract number from filename (e.g., 001_90921d4b.jpg -> 001)
        preg_match('/(\d+)/', $filename, $matches);
        $number = $matches[1] ?? rand(100, 999);

        $models = [
            'Audemars Piguet' => ['Royal Oak', 'Royal Oak Offshore', 'Code 11.59', 'Jules Audemars'],
            'Breguet' => ['Classique', 'Marine', 'Type XX', 'Tradition'],
            'Breitling' => ['Navitimer', 'Superocean', 'Chronomat', 'Avenger'],
            'Maurice Lacroix' => ['Aikon', 'Masterpiece', 'Pontos', 'Eliros'],
            'Omega' => ['Seamaster', 'Speedmaster', 'Constellation', 'De Ville'],
            'Patek Philippe' => ['Grand Complications', 'Calatrava', 'Nautilus', 'Aquanaut'],
            'Rado' => ['Captain Cook', 'DiaMaster', 'Ceramica', 'True'],
            'Relox' => ['Submariner', 'GMT-Master II', 'Daytona', 'Datejust', 'Explorer', 'Oyster Perpetual'],  // CHANGED
            'Tissot' => ['PRX', 'Gentleman', 'Seastar', 'Le Locle', 'Carson'],
            'Vacheron Constantin' => ['Patrimony', 'Overseas', 'Traditionnelle', 'Fiftysix']
        ];

        $brandModels = $models[$brand] ?? ['Classic', 'Sport', 'Elegance'];
        $model = $brandModels[array_rand($brandModels)];
        
        $variations = ['Automatic', 'Chronograph', 'Date', 'GMT', 'Moonphase', ''];
        $variation = $variations[array_rand($variations)];

        return trim("{$brand} {$model} {$variation} Ref.{$number}");
    }

    private function generatePrice(string $brand): int
    {
        $priceRanges = [
            'Audemars Piguet' => [20000, 100000],
            'Breguet' => [600000, 2000000],
            'Breitling' => [300000, 800000],
            'Maurice Lacroix' => [150000, 300000],
            'Omega' => [250000, 900000],
            'Patek Philippe' => [1500000, 3000000],
            'Rado' => [100000, 400000],
            'Relox' => [50000, 1500000], 
            'Tissot' => [50000, 300000],
            'Vacheron Constantin' => [10000, 2000000]
        ];

        $range = $priceRanges[$brand] ?? [100000, 500000];
        return rand($range[0], $range[1]);
    }

    private function generateDescription(string $brand, string $name): string
    {
        $descriptions = [
            "Exquisite {$brand} timepiece featuring Swiss precision and timeless design.",
            "A masterpiece of horological excellence from {$brand}, combining tradition with innovation.",
            "Luxury {$brand} watch with impeccable craftsmanship and sophisticated aesthetics.",
            "Premium {$brand} creation showcasing the finest materials and expert engineering.",
            "Iconic {$brand} model that represents the pinnacle of watchmaking artistry."
        ];

        return $descriptions[array_rand($descriptions)];
    }
}