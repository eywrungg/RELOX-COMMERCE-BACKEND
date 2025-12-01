<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * GET /api/products
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Search by name or description
        if ($request->filled('q')) {
            $q = $request->get('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%");
            });
        }

        // Brand filter
        if ($request->filled('brand')) {
            $query->where('brand', $request->get('brand'));
        }

        // Price range filters
        if ($request->filled('price_min')) {
            $query->where('price', '>=', (float) $request->get('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', (float) $request->get('price_max'));
        }

        // Sorting
        if ($request->filled('sort')) {
            $sort = $request->get('sort');
            if ($sort === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($sort === 'price_desc') {
                $query->orderBy('price', 'desc');
            } elseif ($sort === 'newest') {
                $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = (int) $request->get('per_page', 20);
        $products = $query->paginate($perPage);

        // Transform with full image URLs
        $products->getCollection()->transform(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand,
                'description' => $p->description,
                'price' => (float) $p->price,
                'stock' => (int) $p->stock,
                'image' => $this->getFullImageUrl($p->image),
                'created_at' => $p->created_at,
                'updated_at' => $p->updated_at,
            ];
        });

        return response()->json($products);
    }

    /**
     * GET /api/products/{id_or_slug}
     */
    public function show($idOrSlug)
    {
        $product = Product::where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand,
            'description' => $product->description,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'image' => $this->getFullImageUrl($product->image),
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ]);
    }

    /**
     * GET /api/products/brand/{brand}
     */
    public function productsByBrand($brand, Request $request)
    {
        $query = Product::where('brand', $brand);

        if ($request->filled('per_page')) {
            $perPage = (int) $request->get('per_page', 20);
            $paged = $query->paginate($perPage);
            $paged->getCollection()->transform(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'brand' => $p->brand,
                    'price' => (float) $p->price,
                    'stock' => (int) $p->stock,
                    'image' => $this->getFullImageUrl($p->image),
                ];
            });
            return response()->json($paged);
        }

        $items = $query->orderBy('created_at', 'desc')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand,
                'price' => (float) $p->price,
                'stock' => (int) $p->stock,
                'image' => $this->getFullImageUrl($p->image),
            ];
        });

        return response()->json(['data' => $items]);
    }

    /**
     * GET /api/products/featured
     */
    public function featured(Request $request)
    {
        $limit = (int) $request->get('limit', 6);
        $items = Product::inRandomOrder()->limit($limit)->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'brand' => $p->brand,
                'price' => (float) $p->price,
                'stock' => (int) $p->stock,
                'image' => $this->getFullImageUrl($p->image),
            ];
        });

        return response()->json(['data' => $items]);
    }

    /**
     * Helper: Convert relative image path to full URL
     */
    private function getFullImageUrl($imagePath)
    {
        if (!$imagePath) {
            return null;
        }

        // If already a full URL, return as-is
        if (str_starts_with($imagePath, 'http')) {
            return $imagePath;
        }

        // Convert to full URL with proper encoding
        return url($imagePath);
    }
}