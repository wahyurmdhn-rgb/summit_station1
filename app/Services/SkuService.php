<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;

class SkuService
{
    /**
     * Generate unique 3-character (or fallback alphanumeric) SKU for a Category.
     * Examples:
     * - "Tenda" -> "TND"
     * - "Kursi" -> "KRS"
     * - "Meja"  -> "MEJ"
     * - "Sleeping Bag" -> "SLB"
     */
    public static function generateCategorySku(string $name, ?int $ignoreCategoryId = null): string
    {
        $base = self::extractCategoryCodeBase($name);
        $candidate = $base;
        $counter = 2;

        while (self::isCategorySkuTaken($candidate, $ignoreCategoryId)) {
            $candidate = $base.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Generate unique Product SKU for a given category with format:
     * {CATEGORY_SKU}-{001, 002, 003...}
     *
     * Protected by database transaction lock.
     */
    public static function generateProductSku(Category|int $category, ?int $ignoreProductId = null): string
    {
        $categoryModel = $category instanceof Category ? $category : Category::findOrFail($category);

        if (empty($categoryModel->sku)) {
            $categoryModel->sku = self::generateCategorySku($categoryModel->name, $categoryModel->id);
            $categoryModel->save();
        }

        $categorySku = strtoupper(trim($categoryModel->sku));

        // Find existing products under this category to determine next sequence
        $existingSkus = Product::query()
            ->where('category_id', $categoryModel->id)
            ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
            ->lockForUpdate()
            ->pluck('sku');

        $maxSeq = 0;
        $pattern = '/^'.preg_quote($categorySku, '/').'-(\d+)$/i';

        foreach ($existingSkus as $sku) {
            if (preg_match($pattern, (string) $sku, $matches)) {
                $seq = (int) $matches[1];
                if ($seq > $maxSeq) {
                    $maxSeq = $seq;
                }
            }
        }

        $nextSeq = $maxSeq + 1;
        $candidate = sprintf('%s-%03d', $categorySku, $nextSeq);

        // In case candidate already exists globally on another product
        while (Product::query()
            ->where('sku', $candidate)
            ->when($ignoreProductId, fn ($q) => $q->where('id', '!=', $ignoreProductId))
            ->exists()) {
            $nextSeq++;
            $candidate = sprintf('%s-%03d', $categorySku, $nextSeq);
        }

        return $candidate;
    }

    /**
     * Extract a 3-character base uppercase code from category name.
     */
    public static function extractCategoryCodeBase(string $name): string
    {
        $name = trim(preg_replace('/[^A-Za-z0-9\s]/', '', $name));
        $words = array_values(array_filter(preg_split('/\s+/', $name)));

        if (empty($words)) {
            return 'CAT';
        }

        // 3 or more words: take 1st letter of first 3 words (e.g. "Tenda Dome Gunung" -> "TDG")
        if (count($words) >= 3) {
            return strtoupper(substr($words[0], 0, 1).substr($words[1], 0, 1).substr($words[2], 0, 1));
        }

        // 2 words: e.g. "Sleeping Bag" -> take 1st letter of word 1 + 1st consonant of word 1 + 1st letter of word 2 -> "SLB"
        if (count($words) === 2) {
            $w1 = strtoupper($words[0]);
            $w2 = strtoupper($words[1]);
            $w1Consonants = preg_replace('/[^BCDFGHJKLMNPQRSTVWXYZ0-9]/', '', substr($w1, 1));
            $char2 = $w1Consonants !== '' ? substr($w1Consonants, 0, 1) : (strlen($w1) > 1 ? substr($w1, 1, 1) : 'X');
            $char3 = substr($w2, 0, 1);

            return strtoupper(substr($w1, 0, 1).$char2.$char3);
        }

        // 1 word: e.g. "Tenda" -> "TND", "Kursi" -> "KRS", "Meja" -> "MEJ"
        $w = strtoupper($words[0]);
        $firstChar = substr($w, 0, 1);
        $remaining = substr($w, 1);
        $consonants = preg_replace('/[^BCDFGHJKLMNPQRSTVWXYZ0-9]/', '', $remaining);

        $combined = $firstChar.$consonants;

        if (strlen($combined) >= 3) {
            return substr($combined, 0, 3);
        }

        // If not enough consonants (e.g. "Meja" has M, J -> length 2), take first 3 chars of the word
        $cleanWord = preg_replace('/[^A-Z0-9]/', '', $w);
        if (strlen($cleanWord) >= 3) {
            return substr($cleanWord, 0, 3);
        }

        // If word is very short (e.g. "Go" -> "GOX")
        return str_pad(substr($cleanWord, 0, 3), 3, 'X');
    }

    private static function isCategorySkuTaken(string $sku, ?int $ignoreCategoryId = null): bool
    {
        return Category::query()
            ->where('sku', $sku)
            ->when($ignoreCategoryId, fn ($q) => $q->where('id', '!=', $ignoreCategoryId))
            ->exists();
    }
}
