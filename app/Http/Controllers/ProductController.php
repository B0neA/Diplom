<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Отображение списка продуктов (главная страница)
     */
    public function index(Request $request)
    {
        // Базовый запрос - только активные продукты
        $query = Product::query()->where('is_active', true);

        // Поиск по названию
        if ($request->has('search') && $request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Фильтр по категории
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'rating');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Проверяем, что поле для сортировки существует
        if (in_array($sortBy, ['title', 'rating', 'price', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('rating', 'desc');
        }

        // Пагинация (20 товаров на страницу)
        $products = $query->paginate(20);

        // Трансформация данных для фронтенда
        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'rating' => (float) $product->rating,
                'img' => $product->image_url ?? '/images/placeholder.jpg',
                'price' => $product->price ? (float) $product->price : null,
                'slug' => $product->slug,
                'category' => $product->category,
            ];
        });

        // Получаем список уникальных категорий для фильтра
        $categories = Product::where('is_active', true)
            ->select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->toArray();

        // Возвращаем视图 с данными
        return Inertia::render('ProductsView', [
            'products' => $products,
            'filters' => $request->only(['search', 'category', 'sort_by', 'sort_order']),
            'categories' => $categories,
        ]);
    }

    /**
     * Отображение одного продукта
     */
    public function show($id)
    {
        // Ищем по id или по slug
        $product = Product::where('id', $id)
            ->orWhere('slug', $id)
            ->where('is_active', true)
            ->firstOrFail();

        // Формируем данные для передачи
        $productData = [
            'id' => $product->id,
            'title' => $product->title,
            'description' => $product->description,
            'rating' => (float) $product->rating,
            'img' => $product->image_url ?? '/images/placeholder.jpg',
            'price' => $product->price ? (float) $product->price : null,
            'category' => $product->category,
            'created_at' => $product->created_at ? $product->created_at->format('d.m.Y') : null,
        ];

        // Возвращаем视图 продукта
        return Inertia::render('ProductView', [
            'product' => $productData,
        ]);
    }

    /**
     * API метод для получения продуктов (AJAX запросы)
     */
    public function apiIndex(Request $request)
    {
        $products = Product::where('is_active', true)
            ->when($request->search, function ($query, $search) {
                return $query->where('title', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $category) {
                return $query->where('category', $category);
            })
            ->orderBy($request->get('sort_by', 'rating'), $request->get('sort_order', 'desc'))
            ->get();

        // Трансформируем данные
        $transformed = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'rating' => (float) $product->rating,
                'img' => $product->image_url ?? '/images/placeholder.jpg',
                'price' => $product->price ? (float) $product->price : null,
            ];
        });

        return response()->json($transformed);
    }

    /**
     * Поиск продуктов (автокомплит)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        $products = Product::where('is_active', true)
            ->where('title', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'title', 'slug', 'image', 'price']);

        return response()->json($products);
    }

    /**
     * Получение популярных продуктов
     */
    public function popular()
    {
        $products = Product::where('is_active', true)
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        $transformed = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'rating' => (float) $product->rating,
                'img' => $product->image_url ?? '/images/placeholder.jpg',
                'price' => $product->price ? (float) $product->price : null,
            ];
        });

        return response()->json($transformed);
    }

    /**
     * Получение продуктов по категории
     */
    public function byCategory($category, Request $request)
    {
        $products = Product::where('is_active', true)
            ->where('category', $category)
            ->orderBy($request->get('sort_by', 'rating'), 'desc')
            ->paginate(20);

        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'rating' => (float) $product->rating,
                'img' => $product->image_url ?? '/images/placeholder.jpg',
                'price' => $product->price ? (float) $product->price : null,
            ];
        });

        return Inertia::render('ProductsView', [
            'products' => $products,
            'currentCategory' => $category,
        ]);
    }
}